<?php

namespace Drupal\slackposter\Integrate;

/**
 * This class manages a slack attachment object.
 *
 * Note: This style of attachment is outmoded. Use blocks.
 *
 * @see https://api.slack.com/reference/messaging/attachments.
 */
class SlackAttachment {

  /**
   * Fallback.
   *
   * A plain text summary of the attachment used in clients that don't show
   * formatted text (eg. IRC, mobile notifications).
   *
   * @var string
   */
  public $fallback;
  /**
   * Color.
   *
   * Changes the color of the border on the left side of this attachment
   * from the default gray. Can either be one of good (green),
   * warning (yellow), danger (red), or any hex color code (eg. #439FE0).
   *
   * @var string
   */
  public $color;
  /**
   * Pre-text.
   *
   * Text that appears above the message attachment block.
   * It can be formatted as plain text, or with mrkdwn by including it in the
   * mrkdwn_in field.
   *
   * @var string
   */
  public $pretext;
  /**
   * Small text used to display the author's name.
   *
   * @var string
   */
  public $authorName;
  /**
   * Author Link.
   *
   * A valid URL that will hyperlink the author_name text.
   * Will only work if author_name is present.
   *
   * @var string
   */
  public $authorLink;
  /**
   * Author icon.
   *
   * A valid URL that displays a small 16px by 16px image to the left of the
   * author_name text. Will only work if author_name is present.
   *
   * @var string
   */
  public $authorIcon;
  /**
   * Title.
   *
   * Large title text near the top of the attachment.
   *
   * @var string
   */
  public $title;
  /**
   * Title Link.
   *
   * A valid URL that turns the title text into a hyperlink.
   *
   * @var string
   */
  public $titlelink;
  /**
   * Text.
   *
   * The main body text of the attachment. It can be formatted as plain text,
   * or with mrkdwn by including it in the mrkdwn_in field. The content will
   * automatically collapse if it contains 700+ characters or 5+ linebreaks,
   * and will display a "Show more..." link to expand the content.
   *
   * @var string
   */
  public $text;
  /**
   * Image URL.
   *
   * A valid URL to an image file that will be displayed at the bottom
   * of the attachment. We support GIF, JPEG, PNG, and BMP formats.
   *
   * @var string
   */
  public $imageUrl;
  /**
   * Thumb URL.
   *
   * A valid URL to an image file that will be displayed as a thumbnail on the
   * right side of a message attachment. We currently support the following
   * formats: GIF, JPEG, PNG, and BMP.
   *
   * @var string
   */
  public $thumbUrl;
  /**
   * Footer.
   *
   * Some brief text to help contextualize and identify an attachment.
   * Limited to 300 characters, and may be truncated further when displayed
   * to users in environments with limited screen real estate.
   *
   * @var string
   */
  public $footer;
  /**
   * Footer Icon.
   *
   * A valid URL to an image file that will be displayed beside the footer text.
   * Will only work if author_name is present. We'll render what you provide
   * at 16px by 16px. It's best to use an image that is similarly sized.
   *
   * @var string
   */
  public $footerIcon;
  /**
   * Timestamp.
   *
   * An integer Unix timestamp that is used to related your attachment to a
   * specific time. The attachment will display the additional timestamp value
   * as part of the attachment's footer.
   *
   * @var string
   */
  public $ts;
  /**
   * Fields.
   *
   * An array of field objects that get displayed in a table-like way.
   * For best results, include no more than 2-3 field objects.
   *
   * @var string
   */
  public $fields;
  /**
   * Markdon In.
   *
   * An array of field names that should be formatted by mrkdwn syntax.
   *
   * @var string
   */
  public $mrkdwn = ["text", "pretext", "fields"];

  /**
   * Class constructor.
   */
  public function __construct() {

    $config = \Drupal::config('system.site');
    global $base_url;
    $this->footer = "Origin: " . $config->get('name') . " (" . $base_url . ")";
    $this->ts = date("U");
    try {
      $this->footerIcon = function_exists("theme_get_setting") ? theme_get_setting('favicon') : '';
    }
    catch (\Error $e) {
    }
  }

  /**
   * Add an attachment field to the slack attachment object.
   *
   * @param string $title
   *   Title for the attachment field.
   * @param string $value
   *   Body of attachment field.
   * @param string $short
   *   Use columns for the field.
   *
   * @return array
   *   The attachment field as an array.
   */
  public function addfield(string $title, string $value, string $short) {

    // Initialise the fields array if not yet done.
    if (!$this->fields) {
      $this->fields = [];
    }

    // Make the field and  save it.
    $this->fields[] = $field = new SlackAttachmentField($title, $value, $short);

    // Return it.
    return $field->toArray();
  }

  /**
   * Build the object: basically return it as an array ...
   *
   * @return array
   *   The full attachment as an array - ready for posting to slack.
   */
  public function toArray() {
    return [
      "fallback" => $this->fallback,
      "color" => $this->color,
      "pretext" => $this->pretext,
      "author_name" => $this->authorName,
      "author_link" => $this->authorLink,
      "author_icon" => $this->authorIcon,
      "title" => $this->title,
      "titlelink" => $this->titlelink,
      "text" => $this->text,
      "image_url" => $this->imageUrl,
      "thumb_url" => $this->thumbUrl,
      "footer" => $this->footer,
      "mrkdwn_in" => $this->mrkdwn,
      "ts" => $this->ts,
      "fields" => $this->fields,
    ];
  }

}
