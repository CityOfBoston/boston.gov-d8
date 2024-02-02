<?php

namespace Drupal\bos_emergency_alerts\Controller;

use Drupal\bos_emergency_alerts\EmergencyAlertsAPISettingsInterface;
use Drupal\bos_emergency_alerts\Event\EmergencyAlertsBuildFormEvent;
use Drupal\bos_emergency_alerts\Event\EmergencyAlertsSubmitFormEvent;
use Drupal\bos_emergency_alerts\Event\EmergencyAlertsValidateFormEvent;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Response;
use Drupal\Core\DependencyInjection\DependencySerializationTrait;

/**
 * Class Genasys Subscriber.
 *
 * @package Drupal\bos_emergency_alerts\Controller
 */
class GenasysSubscriber extends EmergencyAlertsSubscriberBase implements EmergencyAlertsAPISettingsInterface, EventSubscriberInterface  {

  protected bool $debug_headers = FALSE;

  private array $settings = [];

  /**
   * Some settings we don't ever want to come from the envar (i.e. they must
   * be set using the config form).
   *
   * @var array $envar_list This list specifies the only settings which can be set from the envar.
   */
  private array $envar_list = [
    "api_base",
    "api_user",
    "api_pass",
    "api_clientid",
    "api_clientsecret",
  ];

  /**
   * @inheritDoc
   */
  public function __construct() {
    $this->settings = parent::getSettings("GENASYS_SETTINGS","genasys", $this->envar_list);
  }

  /**
   * Subscribe to the EmergencyAlerts config form build & submit events.
   */
  public static function getSubscribedEvents(): array {
    $events[EmergencyAlertsBuildFormEvent::BUILD_CONFIG_FORM] = ['buildForm'];
    $events[EmergencyAlertsSubmitFormEvent::SUBMIT_CONFIG_FORM] = ['submitForm'];
    $events[EmergencyAlertsValidateFormEvent::VALIDATE_CONFIG_FORM] = ['validateForm'];
    return $events;
  }

  /**
   * @inheritDoc
   */
  public function buildForm(EmergencyAlertsBuildFormEvent $event, string $event_id): EmergencyAlertsBuildFormEvent {

    if ($event_id == EmergencyAlertsBuildFormEvent::BUILD_CONFIG_FORM) {

      $envar_list = array_flip($this->settings["config"] ?? []);
      $rec_settings = json_encode(array_intersect_key($this->settings, array_flip($this->envar_list)));
      $required = ($event->form["bos_emergency_alerts"]["emergency_alerts_settings"]["current_api"]["#default_value"] == "GenasysSubscriber");

      $genToken = "Fetch/Generate Token";
      if (!empty($this->settings["refresh_token"])) {
        $genToken = "Refresh Token";
      }

      // Add config for the url/user/pass for everbridge API
      $event->form["bos_emergency_alerts"]["emergency_alerts_settings"]["api_config"]["genasys"] = [

        '#type' => 'details',
        '#title' => 'Genasys Endpoint',
        '#description' => 'Configuration for Emergency Alert Subscriptions via Genasys API.',
        '#markup' => empty($envar_list)
          ? "<p>Genasys Settings are stored in Drupal config, this is not best practice and not recommended for production sites.</p><p>Please set the <b>GENASYS_SETTINGS</b> envar to this value:<br><b>{$rec_settings}</b></p>"
          : "<b>Some settings are defined in the envar GENASYS_SETTINGS and cannot be changed using this form.</b> This is best practice - Please change them in the environment.",
        '#open' => FALSE,

        'api_base' => [
          '#type' => 'textfield',
          '#title' => t('Genasys API URL'),
          '#description' => t('Enter the full (remote) URL for the endpoint / API used to register subscriptions.'),
          '#disabled' => array_key_exists("api_base", $envar_list),
          '#required' => !array_key_exists("api_base", $envar_list) && $required,
          '#default_value' => $this->settings["api_base"] ?? "",
          '#attributes' => [
            "placeholder" => 'e.g. https://alertboston.genasys.com',
          ],
        ],
        'api_clientid' => [
          '#type' => 'textfield',
          '#title' => t('Genasys Client ID'),
          '#description' => t('The City of Boston Client ID for authentication.'),
          '#default_value' => $this->settings["api_clientid"] ?? "",
          '#disabled' => array_key_exists("api_clientid", $envar_list),
          '#required' => !array_key_exists("api_clientid", $envar_list) && $required,
        ],
        'api_clientsecret' => [
          '#type' => 'textfield',
          '#title' => t('Genasys Client Secret'),
          '#description' => t('Genasys City of Boston Client Secret for authentication.'),
          '#default_value' => $this->settings["api_clientsecret"] ?? "",
          '#disabled' => array_key_exists("api_clientsecret", $envar_list),
          '#required' => !array_key_exists("api_clientsecret", $envar_list) && $required,
        ],
        'api_user' => [
          '#type' => 'textfield',
          '#title' => t('API Username'),
          '#description' => t('API Username for authentication.'),
          '#default_value' => $this->settings["api_user"] ?? "",
          '#disabled' => array_key_exists("api_user", $envar_list),
          '#required' => !array_key_exists("api_user", $envar_list) && $required,
        ],
        'api_pass' => [
          '#type' => 'textfield',
          '#title' => t('API Password'),
          '#description' => t('API Password for authentication.'),
          '#default_value' => $this->settings["api_pass"] ?? "",
          '#disabled' => array_key_exists("api_pass", $envar_list),
          '#required' => !array_key_exists("api_pass", $envar_list) && $required,
        ],
        'token_wrapper' => [
          '#type' => 'fieldset',
          '#title' => 'Oauth2.0 Token',
          '#description' => 'Token used for authentication with Genasys endpoint.',
          '#description_display' => 'before',
          'api_token' => [
            '#type' => 'textfield',
            '#title' => t('Current Token'),
            '#prefix' => "<div id='edit-api-token'>",
            '#suffix' => "</div>",
            '#default_value' => $this->obfuscateToken($this->settings["api_token"] ?? ""),
            '#disabled' => TRUE,
            '#required' => FALSE,
          ],
          'generate_token' => [
            '#type' => 'button',
            '#value' => $genToken,
            '#attributes' => ["cob-tag" => "genToken"],
            '#ajax' => [
              'callback' => [$this, 'ajaxAuthenticate'],
              'wrapper' => "edit-api-token",
              'event' => 'click',
              'progress' => [
                'type' => 'throbber',
                'message' => $this->t('Retrieving oauth2.0 token.'),
              ],
            ],
          ],
        ],
        'contact_wrapper' => [
          '#type' => 'fieldset',
          '#title' => 'New Contact Settings',
          '#description' => 'Default values used for contact registration with Genasys.',
          '#description_display' => 'before',
          'address' => [
            'geocode_wrapper' => [
              '#type' => 'fieldset',
              '#title' => 'New Contact Settings',
              '#description' => 'Default values used for contact registration with Genasys.',
              '#description_display' => 'before',
              'geo_lat' => [
                '#type' => 'textfield',
                '#title' => t('Default Latitude'),
                '#description' => t(''),
                '#default_value' => $this->settings["geo_lat"] ?? "",
                '#required' => $required,
                '#attributes' => [
                  "placeholder" => 'e.g. 42.3560729',
                ],
              ],
              'geo_long' => [
                '#type' => 'textfield',
                '#title' => t('Default Longitude'),
                '#description' => t(''),
                '#default_value' => $this->settings["geo_long"] ?? "",
                '#required' => $required,
                '#attributes' => [
                  "placeholder" => 'e.g. -71.3452085',
                ],
              ],
            ],
          ],
          'groups' => [
            '#type' => 'textfield',
            '#title' => t('Subscription Groups'),
            '#description' => t('Enter a list of Genasys-defined groups that users will be subscribed to.<br><i>Separate each group with a comma (e.g. group1,group2).</i>'),
            '#default_value' => $this->settings["groups"] ?? "",
            '#required' => $required,
            '#attributes' => [
              "placeholder" => 'e.g. group1,group2',
            ],
          ],
          'types' => [
            '#type' => 'textfield',
            '#title' => t('Notification Types'),
            '#description' => t('Enter a list of Genasys-defined notification types that users will be subscribed to.<br><i>Separate each type with a comma (e.g. type1,types2).</i>'),
            '#default_value' => $this->settings["types"] ?? "",
            '#required' => $required,
            '#attributes' => [
              "placeholder" => 'e.g. type1,types2',
            ],
          ],
          'messages' => [
            'msg_success' => [
              '#type' => 'textarea',
              '#title' => t('Sucessful subscription'),
              '#description' => t('This message will display after the user sucessfully subscribes.'),
              '#default_value' => $this->settings["msg_success"] ?? "",
              '#required' => $required,
            ],
            'msg_duplicate_number' => [
              '#type' => 'textarea',
              '#title' => t('Subscription- Phone already registered'),
              '#description' => t('This message will display if the user\'s phone number is already subscribed.'),
              '#default_value' => $this->settings["msg_duplicate_number"] ?? "",
              '#required' => $required,
            ],
            'msg_duplicate_email' => [
              '#type' => 'textarea',
              '#title' => t('Subscription-  Email already registered'),
              '#description' => t('This message will display if the user\'s email address is already subscribed.'),
              '#default_value' => $this->settings["msg_duplicate_email"] ?? "",
              '#required' => $required,
            ],
            'msg_error' => [
              '#type' => 'textarea',
              '#title' => t('Subscription Error'),
              '#description' => t('This message will display when there is a subscription error.'),
              '#default_value' => $this->settings["msg_error"] ?? "",
              '#required' => $required,
            ],
          ],
        ],
      ];

      // Add vendor to the dropdown.
      $classname = explode('\\', get_class($this));
      $class = array_pop($classname);
      $event->form["bos_emergency_alerts"]["emergency_alerts_settings"]["current_api"]["#options"][$class] = "Genasys";

    }
    return $event;  }

  /**
   * @inheritDoc
   */
  public function submitForm(EmergencyAlertsSubmitFormEvent $event, string $event_id): EmergencyAlertsSubmitFormEvent {

    if ($event_id == EmergencyAlertsSubmitFormEvent::SUBMIT_CONFIG_FORM) {

      $form_values = $event->form_state->getValue('bos_emergency_alerts')['emergency_alerts_settings']['api_config']['genasys'];
      $form = $event->form['bos_emergency_alerts']["emergency_alerts_settings"]["api_config"]["genasys"];

      $config = $event->config;

      if (!$form['api_base']['#disabled']) {
        $config->set('api_config.genasys.api_base', $form_values['api_base']);
      }
      if (!$form['api_user']['#disabled']) {
        $config->set('api_config.genasys.api_user', $form_values['api_user']);
      }
      if (!$form['api_pass']['#disabled']) {
        $config->set('api_config.genasys.api_pass', $form_values['api_pass']);
      }
      if (!$form['api_clientid']['#disabled']) {
        $config->set('api_config.genasys.api_clientid', $form_values['api_clientid']);
      }
      if (!$form['api_clientsecret']['#disabled']) {
        $config->set('api_config.genasys.api_clientsecret', $form_values['api_clientsecret']);
      }
      $config->set('api_config.genasys.groups', $form_values['contact_wrapper']['groups']);
      $config->set('api_config.genasys.types', $form_values['contact_wrapper']['types']);
      $config->set('api_config.genasys.geo_lat', $form_values['contact_wrapper']['address']['geocode_wrapper']['geo_lat']);
      $config->set('api_config.genasys.geo_long', $form_values['contact_wrapper']['address']['geocode_wrapper']['geo_long']);
      $config->set('api_config.genasys.msg_success', $form_values['contact_wrapper']['messages']['msg_success']);
      $config->set('api_config.genasys.msg_duplicate_number', $form_values['contact_wrapper']['messages']['msg_duplicate_number']);
      $config->set('api_config.genasys.msg_duplicate_email', $form_values['contact_wrapper']['messages']['msg_duplicate_email']);
      $config->set('api_config.genasys.msg_error', $form_values['contact_wrapper']['messages']['msg_error']);
      $config->save();
    }

    return $event;

  }

  /**
   * @inheritDoc
   */
  public function validateForm(EmergencyAlertsValidateFormEvent $event, string $event_id): EmergencyAlertsValidateFormEvent {

    if ($event->form_state->getTriggeringElement()["#attributes"]["cob-tag"] == "genToken"){
      // We don't want the ajax callback to validate anything.
      $event->form_state->disableCache();
      $event->form_state->clearErrors();
    }
    return $event;
  }

  /**
   * @inheritDoc
   */
  protected function makeCurl(string $endpoint, array|string $post_fields, array $headers = [], string $type = "POST", bool $insecure = FALSE) {

    $post_url = "{$this->settings["api_base"]}/{$endpoint}";

    return parent::makeCurl($post_url, $post_fields, $headers, $type, $insecure);

  }

  /**
   * @inheritDoc
   */
  protected function executeCurl($handle, bool $retry = FALSE): array {
    // Use the parent function, but trap errors and provide some Genasys
    // specific logic.
    return parent::executeCurl($handle, $retry);

  }

  /**
   * This is the callback from the form when the Generate Token button
   * is clicked.
   *
   * A new Oauth2.0 token is generated with Genasys.
   *
   * @param array $form
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *
   * @return array
   */
  public function ajaxAuthenticate(array &$form, FormStateInterface $form_state): array {

    // Use authentication showing on the form, not what is saved as the user may
    // have changed but not saved some fields.
    $values = $form_state->getValues();
    $creds = $values["bos_emergency_alerts"]["emergency_alerts_settings"]["api_config"]["genasys"];

    // Generate or refresh token.
    $refresh = !empty($this->settings["refresh_token"]);
    $token = $this->authenticate($refresh, $creds);

    // Check the token
    if ($token) {
      \Drupal::messenger()->addStatus("New Token generated and saved.");
    }
    else {
      \Drupal::messenger()->addError($this->error);
      $token = "Authentication Failed";
    }

    // Update and return the form element.
    $form["bos_emergency_alerts"]["emergency_alerts_settings"]["api_config"]["genasys"]['token_wrapper']["api_token"]["#default_value"] = $token;
    $form["bos_emergency_alerts"]["emergency_alerts_settings"]["api_config"]["genasys"]['token_wrapper']["api_token"]["#value"] = $token;
    return $form["bos_emergency_alerts"]["emergency_alerts_settings"]["api_config"]["genasys"]['token_wrapper']["api_token"];

  }

  /**
   * Generate a new Oauth2.0 token from Genesys endpoint.
   *
   * @param bool $refresh TRUE = Refresh an existing token, FALSE = get new token
   * @param array $creds an array with wuth creds for genarting token.
   *
   * @return string|bool
   */
  private function authenticate(bool $refresh = FALSE, array $creds = []): string|bool {

    if (!empty($creds)) {
      $settings = $creds;
      $settings["refresh_token"] = $this->settings["refresh_token"];
    }
    else {
      $settings = $this->settings;
    }

    $auth = "{$settings["api_clientid"]}:{$settings["api_clientsecret"]}";
    $auth = base64_encode($auth);

    $headers = [
      "Authorization" =>  "Basic {$auth}",
      "Content-Type" => "application/x-www-form-urlencoded"
    ];

    // The authorization request must use post, and be application/x-www-form-urlencoded
    // This means the post_fields must be a urlencoded string (not an array).
    if ($refresh) {
      $post_fields = "grant_type=" . urlencode("refresh_token");
      $post_fields .= "&refresh_token=" . urlencode($settings['refresh_token']);
    }
    else {
      $post_fields = "grant_type=" . urlencode("password");
      $post_fields .= "&username=" . urlencode($settings['api_user']);
      $post_fields .= "&password=" . urlencode($settings['api_pass']);
    }

    try {
      $ch = $this->makeCurl("oauth/token", $post_fields, $headers, "POST");
      $result = $this->executeCurl($ch, FALSE);
    }
    catch (\Exception $e) {
      if ( $refresh
        && ($this->response["http_code"] == 400 || $this->response["http_code"] == 401)
        && str_contains($this->response["body"]["error_description"] ?? "", "Invalid refresh token")) {
        // The refresh token is expired or invalid.
        return $this->authenticate(FALSE, $settings);
      }
      return FALSE;
    }

    if ($result) {
      $config = \Drupal::configFactory()
        ->getEditable('bos_emergency_alerts.settings');

      $config->set("api_config.genasys.api_token", $result["access_token"])
       ->set("api_config.genasys.refresh_token", $result["refresh_token"])
       ->set("api_config.genasys.token_expiry", intval($result["date"]/1000) + ($result["expires_in"] - 1)) // round ms down
       ->save();

      // Reload the settings into the class.
      $this->settings = parent::getSettings("GENASYS_SETTINGS","genasys");

      return $result["access_token"];
    }

    return FALSE;

  }

  /**
   * Ensure that we have a token, and that it has not expired.
   * Returns FALSE if we have no token or a stale token and cannot authenticate
   * or refresh it as needed.
   *
   * @return bool
   */
  private function validateToken():bool {

    if (empty($this->settings["api_token"])) {
      // No token, so need to go get the token (or create it)
      if (!$this->authenticate(FALSE)) {
        return FALSE;
      }
    }

    if (($this->settings["token_expiry"] - 60) <= strtotime("now")) {
      // The save or retrieved token is within 1 min of expiry so must refresh.
      if (!$this->authenticate(TRUE)) {
        return FALSE;
      }
    }

    return TRUE;

  }

  /**
   * The subscribes a new user to the Genasys system.
   *
   * @return \Symfony\Component\HttpFoundation\Response
   *   Response from the Genasys remote API.
   */
  public function subscribe(array $request_bag, ApiRouter $router): Response {

    $this->router = $router;

    $types = [];

    $groups = explode(",", $this->settings["groups"]);

    foreach (explode(",", $this->settings["types"]) as $type) {
      $types[] = ["name" => $type, "active"=>TRUE];
    }

    // TODO: make a lat/long lookup here.

    $contact = json_encode([
      "contact" => [
        "number" => $request_bag['phone_number'],
        "prefix_number" => '+1',
        "name" => $request_bag['first_name'],
        "surname" => $request_bag['last_name'],
        "email" => $request_bag['email'],
        "lang" => $request_bag['language'],
        "groupNames" => $groups,
        "locations" => [
          [
            "name" => 'Primary',
            "line1" => $request_bag['address'],
            "city" => $request_bag['city'],
            "zipCode" => $request_bag['zip_code'],
            "state" => $request_bag['state'],
            "country" => 'United States',
            "latitude" => $this->settings["geo_lat"],
            "longitude" => $this->settings["geo_long"],
          ],
        ],
        "notification_types" => $types,
        "notification_channels" => [
          ["name" => "EMAIL", "active" => !empty($request_bag["email"])],
          ["name" => "SMS", "active" => !empty($request_bag["phone_number"]) && ($request_bag["text"]??0 == "1")],
          ["name" => "VOICE", "active" => !empty($request_bag["phone_number"]) && ($request_bag["call"]??0 == '1')],
        ],
        "groups" => [],
        "customFields" => [],
        "emails" => [],
        "phones" => [],
        "alternative_contacts" => [],
      ]
    ]);

    // Check the token is still valid.
    if (!$this->validateToken()) {
      $this->router->responseOutput(json_encode(["status" => "failed", "reason" => $this->error]), 400);
    }

    $headers["Content-Type"] = "application/json";
    $headers["Authorization"] = "Bearer {$this->settings["api_token"]}";

    try {
      $ch = $this->makeCurl("api/contact", $contact, $headers, "POST");
      if ($result = $this->executeCurl($ch)) {
        if (empty($result["id"])) {
          throw new \Exception($this->error, self::BAD_RESPONSE);
        }
        return $this->router->responseOutput($this->settings["msg_success"]??"", 200);
      }
      throw new \Exception("Empty Response", self::BAD_RESPONSE);
    }
    catch (\Exception $e) {
      if ($this->response["http_code"] == 400) {
        if ($this->response["body"]["internalCode"] == 1222) {
          // Number or email already subscribed.
          if (str_contains($this->response["body"]["message"], "Email")) {
            return $this->router->responseOutput($this->settings["msg_duplicate_email"]??"---", 401);
          }
          else {
            return $this->router->responseOutput($this->settings["msg_duplicate_number"]??"--", 401);
          }
        }
      }

      // Something else went wrong.  It's all logged just return a friendly
      // message.
      return $this->router->responseOutput($this->settings["msg_error"], 400);
    }

  }

  /**
   * Make token hard to guess when shown on-screen.
   *
   * @param string $tokenThe token.
   *
   * @return string
   */
  private function obfuscateToken(string $token = "") {
    if (!empty($token)) {
      $token = trim($token);
      return substr($token, 0, 4) . "****-****" . substr($token, -4, 4);
    }
    return "No Token";
  }

}
