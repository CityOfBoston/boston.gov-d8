<?php

namespace Drupal\slackposter\Integrate;

use Masterminds\HTML5\Exception;

/**
 * Class to actually post to Slack.
 */
class SlackPost {

  /**
   * A url to include which is clickable in the post.
   *
   * @var string
   */
  public $url;

  /**
   * The text to be posted.
   *
   * This could be an attachment array.
   *
   * @var string
   */
  public $comment;

  /**
   * Channel to post to.
   *
   * Should be prefixed with '#'.
   *
   * @var string
   */
  public $channel;

  /**
   * Username for posting credit.
   *
   * A random username to attribute the slack post to.
   *
   * @var string
   */
  public $username;

  /**
   * An icon to be placed next to the post.
   *
   * Needs to be a path relative to the module or absolute and complete.
   *
   * @var string
   */
  private $iconUrl;

  /**
   * Default set of Images to use as posting icons.
   *
   * The outside key is a module name, its value is a key/value pair which is
   * a default image for posting severities.
   *
   * Array of relative path images.
   *
   * @var array
   */
  private $arrImages = [
    "default" => [
      "default" => "images/drupal.png",
      "info" => "images/drupal.png",
      "warning" => "images/drupal.png",
      "error" => "images/drupal.png",
    ],
  ];

  /**
   * The referring module so that an icon set and attribution can be set.
   *
   * @var null|string
   *   This is set during config.
   */
  private $referringModule;

  /**
   * Collection of SlackAttachments.
   *
   * @var array
   *   An array collection of SlackAttachment objects.
   */
  private $attachments;

  /**
   * Class settings which have been set by slackposter admin/config pages.
   *
   * @var array
   *   Settings from slack poster settings.
   */
  private $settings = [];

  /**
   * Suffix to be added to username when posting.
   *
   * @var string
   */
  private $usernameSuffix = "";

  /**
   * SlackPost constructor.
   *
   * @param string $referring_module
   *   String.
   * @param string $_webhook_url
   *   The slack webhook endpoint.
   */
  public function __construct(string $referring_module = "default", string $_webhook_url = NULL) {

    // Get the admin settings.
    $config = \Drupal::config('slackposter.settings');
    $this->settings = $config->getRawData();
    $config = \Drupal::config('system.site');
    $this->settings['general']['app_id'] = $config->get('name');

    $this->channel = $this->settings['channels']['default'];
    $this->usernameSuffix = $this->settings['general']['app_id'];

    // Process arguments.
    $this->referringModule = $referring_module;
    // Allow the slack webhook URL to be set during initalisation.
    $this->url = (isset($_webhook_url)) ? $_webhook_url : $this->settings['integration'];

    // Initialise variables.
    $this->username = "Website User";
    $this->iconUrl = $this->selectIcon($referring_module, 'default');

  }

  /**
   * Set or get the icon to be used for the posting.
   *
   * @param string $iconURL
   *   The icon URL.
   *
   * @return string
   *   The current icon Url.
   */
  public function icon(string $iconURL = "") {
    if (!empty($iconURL)) {
      $this->iconUrl = $iconURL;
    }
    return $this->iconUrl;
  }

  /**
   * Utility which reformats a string that will work for Slack.
   *
   * (This is intended mainly to parse HTML into slack markup/(down?)).
   *
   * @param string $string
   *   String to be reformatted.
   *
   * @return string
   *   String thast has been reformatted.
   */
  private static function reformat($string) {

    // Replace HTML line breaks.
    $string = str_ireplace(["<br>", "<br />", "<br/>"], "\n", $string);

    // Replace HTML bolds and add slack bold formats.
    $patterns = [
    // Finds bold tags.
      '/<(\/?)b\b((?:[^>"\']|"[^"]*"|\'[^\']*\')*)>/',
    // Finds bold tags.
      '/<(\/?)i((?:[^>"\']|"[^"]*"|\'[^\']*\')*)>/',
    ];
    $replace = [
      '*',
      "_",
    ];
    $string = preg_replace($patterns, $replace, $string);

    // Replace any tabbed columns, keep only the first tab.
    $string = preg_replace("/(\t)\\1+/", "\t", $string);

    // Strip any tags out, leave only anchors.
    $string = strip_tags($string, "<a>");

    // Replace HTML anchors.
    $whatbits = explode("<", $string);
    foreach ($whatbits as $key => &$tag) {
      if (stripos($tag, "a href") === 0) {
        $tag = str_ireplace(['a href="', "a href='"], "<", $tag);
        $tag = str_ireplace(["'>", '">'], "|", $tag);
        $tag .= ">";
      }
      elseif (substr($tag, 0, 3) == "/a>") {
        $tag = str_ireplace("/a>", "", $tag);
        if (trim($tag == "")) {
          unset($whatbits[$key]);
        }
      }
      elseif ($key != 0) {
        $tag = '<' . $tag;
      }
    }
    $string = implode("", $whatbits);

    // Encode to UTF8 for slack.
    $string = mb_convert_encoding($string, "UTF-8", "HTML-ENTITIES");

    return $string;
  }

  /**
   * Add an attachment to this class.
   *
   * @param \Drupal\slackposter\Integrate\SlackAttachment|null $attachment
   *   Formatted attachment for slack posting.
   *
   * @return bool|\Drupal\slackposter\Integrate\SlackAttachment
   *   True if added, else a new SlackAttachment object.
   */
  public function attachment(SlackAttachment $attachment = NULL) {

    if (!$attachment) {
      return new SlackAttachment();
    }

    // Make sure the message elements of attachment are slack markup compliant.
    if ($attachment->text) {
      $attachment->text = $this->reformat($attachment->text);
    }
    if ($attachment->pretext) {
      $attachment->pretext = $this->reformat($attachment->pretext);
    }
    if (isset($attachment->fields)) {
      foreach ($attachment->fields as &$field) {
        $field->value = $this->reformat($field->value);
      }
    }

    // Now save the attahment to this class.
    $this->attachments[] = $attachment;

    return TRUE;
  }

  /**
   * Add a new module and icon-set at runtime.
   *
   * @param string $modulename
   *   The modulename.
   * @param array $defaultIcons
   *   Any default icons.
   */
  public function addModule(string $modulename, array $defaultIcons) {
    $this->referringModule[$modulename] = $defaultIcons;
  }

  /**
   * Return the requested icon, or the default icon.
   *
   * @param string $referring_module
   *   Note on who requested this post.
   * @param string $type
   *   Icon type.
   *
   * @return string
   *   Icon URL.
   */
  protected function selectIcon($referring_module = "", $type = "") {

    global $base_url;

    // Use the default modules default icon so we are sure to have something.
    $icon_url = $this->arrImages['default']['default'];

    // Logic tree to find the correct icon to use.
    // Have requested a specific module and type,.
    if (!empty($referring_module) && !empty($type)) {
      // So get that icon.
      if (isset($this->arrImages[$referring_module][$type])) {
        $icon_url = $this->arrImages[$referring_module][$type];
      }
    }

    // Requested a module but no type,.
    elseif (!empty($referring_module) && empty($type)) {
      // So get default for that module.
      if (isset($this->arrImages[$referring_module]['default'])) {
        $iconUrl = $this->arrImages[$referring_module]['default'];
      }
    }

    // Requested type but no module.
    elseif (empty($referring_module) && !empty($type)) {
      // If there is a module set from instatiation, use that, otherwise use
      // the default module icon set.
      if ($this->referringModule) {
        if (isset($this->arrImages[$this->referringModule][$type])) {
          $icon_url = $this->arrImages[$this->referringModule][$type];
        }
      }
      else {
        if (isset($this->arrImages['default'][$type])) {
          $icon_url = $this->arrImages['default'][$type];
        }
      }
    }

    // There is no module or type.
    elseif (empty($referring_module) && empty($type)) {
      // So use any con already manually set, or use the default type from
      // the module set during initialisation, or else use the default from
      // the default ...
      if ($this->iconUrl) {
        $icon_url = $this->iconUrl;
      }
      elseif ($this->referringModule) {
        if (isset($this->arrImages[$this->referringModule]['default'])) {
          $icon_url = $this->arrImages[$this->referringModule]['default'];
        }
      }
      else {
        $icon_url = $this->arrImages['default']['default'];
      }
    }

    // Now make sure its got a full path.
    if (stripos(strtolower($icon_url), "http") === FALSE && substr($icon_url, 0, 2) != "//") {

      if (substr($icon_url, 0, 2) != "/") {
        // Assumed link is relative to slack module.
        $icon_url = $base_url . "/" .\Drupal::service('extension.path.resolver')->getPath("module", "slackposter") . "/" . $icon_url;
      }
      else {
        // If not assumed link is off the servername.
        $icon_url = $base_url . $icon_url;
      }
    }

    return $icon_url;

  }

  /**
   * Post helper function.  This is called externally to make the post to slack.
   *
   * @param string $comment
   *   Adds a comment to the end of the message body.
   * @param string $channel
   *   The channel to post to (prefixed with '#').
   * @param string $usern
   *   The message author to be attributed.
   * @param string $postType
   *   Special posting types.
   * @param string $sanitize
   *   Should the message be sanitized.
   *
   * @return array
   *   The posting result.
   */
  public function post($comment = NULL, $channel = NULL, $usern = NULL, $postType = "default", $sanitize = FALSE) {

    // Handle inputs.
    if (!empty($comment)) {
      $this->comment = $comment;
    }
    if (!empty($channel)) {
      $this->channel = $channel;
    }
    if (!empty($usern)) {
      $this->username = $usern;
    }

    // Process & sanitize the message.
    if (!empty($sanitize)) {
      if (is_array($this->comment)) {
        $this->comment['text'] = $this->reformat($this->comment['text']);
      }
      else {
        $this->comment = $this->reformat($this->comment);
      }
    }

    // Process the username.
    if (!empty($this->usernameSuffix)) {
      $this->username .= " -" . $this->usernameSuffix;
    }

    // Process the icons.
    if (!isset($this->icon)) {
      $this->iconUrl = $this->selectIcon(NULL, $postType);
    }

    // Process the URL and channel.
    if (!isset($this->channel)) {
      $this->channel = $this->settings['channels']['default'];
    }

    // Validate if can set this slack message or not.
    $response = new SlackRestResponse();
    if (!isset($this->comment)) {
      return $response->setResult(FALSE, "Bad Request - Nothing to post")->toArray();
    }

    // Now build the array to pass to slack.
    $text = [
      'username' => $this->username,
      'channel' => $this->channel,
      'icon_url' => $this->iconUrl,
      'mrkdwn' => TRUE,
    ];

    // For attachments see notes at https://api.slack.com/docs/attachments
    if (empty($this->attachments) && !is_array($this->comment)) {
      $this->comment .= "\n_Origin: " . $this->settings['general']['app_id'] . " (" . $_SERVER['SERVER_NAME'] . ")_";
    }
    else {
      $text['attachments'] = $this->attachments;
    }

    if (is_array($this->comment)) {
      if (empty($this->comment['footer'])) {
        // Create attachment to get the template.
        $tmp = new slackAttachment();
        // Now merge the template with whatever we have ...
        $this->comment = $tmp->toArray() + $this->comment;
      }
      $text['attachments'][] = $this->comment;
    }
    else {
      $text['text'] = $this->comment;
    }

    // Do the actual posting and return the output.
    return $this->curlPost($this->url, $text);

  }

  /**
   * This is the CURL to post to slack.
   *
   * @param string $url
   *   The slack endpoint to post to.
   * @param array $payload
   *   The message to be posted to slack.
   *
   * @return array
   *   A Drupal\slackposter\Integrate\SlackRestResponse object as an array.
   */
  protected function curlPost(string $url, array $payload) {

    // Create a standard rest response object.
    $response = new SlackRestResponse($payload);

    // Do the posting using curl.
    try {
      if (!($ch = curl_init())) {
        return $response->setResult(FALSE, "Bad Request - Cannot create CURL object")->toArray();
      }

      curl_setopt($ch, CURLOPT_POST, TRUE);
      curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
      curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
      curl_setopt($ch, CURLOPT_HEADER, FALSE);
      curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
      curl_setopt($ch, CURLOPT_FOLLOWLOCATION, FALSE);
      curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1)');
      $header[] = 'Content-Type:application/x-www-form-urlencoded';
      curl_setopt($ch, CURLOPT_HTTPHEADER, $header);

      // Set the url, number of POST vars, POST data.
      curl_setopt($ch, CURLOPT_URL, $url);
      curl_setopt($ch, CURLOPT_POST, count($payload));
      curl_setopt($ch, CURLOPT_POSTFIELDS, "payload=" . urlencode(json_encode($payload)));

      $result = curl_exec($ch);
      curl_close($ch);

      if ($result == "ok") {
        return $response->setResult(TRUE)->toArray();
      }
      return $response->setResult(FALSE, $result)->toArray();

    }
    catch (Exception $e) {
      return $response->setResult(FALSE, $e->getMessage())->toArray();
    }

  }

}
