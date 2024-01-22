<?php

namespace Drupal\bos_emergency_alerts\Controller;

use Drupal\bos_emergency_alerts\EmergencyAlertsAPISettingsInterface;
use Drupal\bos_emergency_alerts\Event\EmergencyAlertsBuildFormEvent;
use Drupal\bos_emergency_alerts\Event\EmergencyAlertsSubmitFormEvent;
use Drupal\bos_emergency_alerts\Event\EmergencyAlertsValidateFormEvent;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class Genasys Subscriber.
 *
 * @package Drupal\bos_emergency_alerts\Controller
 */
class GenasysSubscriber extends EmergencyAlertsSubscriberBase implements EmergencyAlertsAPISettingsInterface, EventSubscriberInterface  {

  /**
   * API endpoints for message types.
   *
   * @var array
   */
  protected $uri = [
    "login" => "/api/login",
    "contacts" => "/rest/contacts/454102597238915",
  ];

  protected bool $debug_headers = FALSE;

  private array $settings = [];

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
   * The subscribes a new user to the Genasys system.
   *
   * @return \Symfony\Component\HttpFoundation\Response
   *   Response from the Genasys remote API.
   */
  public function subscribe(array $request_bag, ApiRouter $router): Response {

    $this->router = $router;

    $settings = parent::getSettings("GENASYS_SETTINGS","genasys");

    $groups = [];
    foreach (explode(",", $settings["groups"]) as $group) {
      $groups[] = ["name" => $group];
    }
    $types = [];
    foreach (explode(",", $settings["types"]) as $type) {
      $types[] = ["name" => $type, "active"=>TRUE];
    }

    $contact = json_encode([
      "contact" => [
        "number" => $request_bag['phone_number'],
        "prefix_number" => '+1',
        "name" => $request_bag['first_name'],
        "surname" => $request_bag['last_name'],
        "email" => $request_bag['email'],
        "lang" => 'en',
        "groupNames" => $groups,
        "locations" => [
          [
            "name" => 'Home',
            "line1" => $request_bag['address'],
            "city" => $request_bag['city'],
            "zipCode" => $request_bag['zip_code'],
            "state" => $request_bag['state'],
            "country" => 'US',
          ],
        ],
        "notification_types" => $types,
        "notification_channels" => [
          ["name" => "EMAIL", "active" => !empty($request_bag["email"])],
          ["name" => "SMS", "active" => !empty($request_bag["phone_number"]) && ($request_bag["text"] == "1")],
          ["name" => "VOICE", "active" => !empty($request_bag["phone_number"]) && ($request_bag["call"] == '1')],
        ]
      ]
    ]);

    $headers["Content-Type"] = "text/json";

    // Check the token is still valid.
    if (!$this->validateToken()) {
      $this->router->responseOutput(["status" => "failed"], 400);
    }

    // TODO check if user is already subscribed.
    // How do we know

    try {
      $ch = $this->makeCurl($settings["api_base"], $contact, $headers, "post");
      if ($this->executeCurl($ch)) {
        return $this->router->responseOutput(["status" => "success"], 200);
      }
      throw new \Exception("Empty Response", self::BAD_RESPONSE);
    }
    catch (\Exception $e) {
      return $this->router->responseOutput(["status" => "failed"], 400);
    }

  }

  protected function executeCurl(CurlHandle $handle, bool $retry = FALSE): array {
    // Use the parent function, but trap errors and provide some Genasys
    // specific logic.
    return parent::executeCurl($handle, $retry);

  }

  /**
   * @inheritDoc
   */
  public function buildForm(EmergencyAlertsBuildFormEvent $event, string $event_id): EmergencyAlertsBuildFormEvent {

    if ($event_id == EmergencyAlertsBuildFormEvent::BUILD_CONFIG_FORM) {

      $settings = parent::getSettings("GENASYS_SETTINGS","genasys");
      $uneditable = $settings["config"] ?? [];

      // Add config for the url/user/pass for everbridge API
      $event->form["bos_emergency_alerts"]["emergency_alerts_settings"]["api_config"]["genasys"] = [

        '#type' => 'details',
        '#title' => 'Genasys Endpoint',
        '#description' => 'Configuration for Emergency Alert Subscriptions via Genasys API.',
        '#markup' => empty($uneditable) ? NULL : "<b>Some settings are defined in the envar GENASYS_SETTINGS and cannot be changed using this form.</b>",
        '#open' => FALSE,

        'api_base' => [
          '#type' => 'textfield',
          '#title' => t('Genasys API URL'),
          '#description' => t('Enter the full (remote) URL for the endpoint / API used to register subscriptions.'),
          '#disabled' => array_key_exists("api_base", $uneditable),
          '#required' => !array_key_exists("api_base", $uneditable),
          '#default_value' => $settings["api_base"] ?? "",
          '#attributes' => [
            "placeholder" => 'e.g. https://alertboston.genasys.com',
          ],
        ],
        'api_clientid' => [
          '#type' => 'textfield',
          '#title' => t('Genasys Client ID'),
          '#description' => t('The City of Boston Client ID for authentication.'),
          '#default_value' => $settings["api_clientid"] ?? "",
          '#disabled' => array_key_exists("api_clientid", $uneditable),
          '#required' => !array_key_exists("api_clientid", $uneditable),
        ],
        'api_clientsecret' => [
          '#type' => 'textfield',
          '#title' => t('Genasys Client Secret'),
          '#description' => t('Genasys City of Boston Client Secret for authentication.'),
          '#default_value' => $settings["api_clientsecret"] ?? "",
          '#disabled' => array_key_exists("api_clientsecret", $uneditable),
          '#required' => !array_key_exists("api_clientsecret", $uneditable),
        ],
        'api_user' => [
          '#type' => 'textfield',
          '#title' => t('API Username'),
          '#description' => t('API Username for authentication.'),
          '#default_value' => $settings["api_user"] ?? "",
          '#disabled' => array_key_exists("api_user", $uneditable),
          '#required' => !array_key_exists("api_user", $uneditable),
        ],
        'api_pass' => [
          '#type' => 'textfield',
          '#title' => t('API Password'),
          '#description' => t('API Password for authentication.'),
          '#default_value' => $settings["api_pass"] ?? "",
          '#disabled' => array_key_exists("api_pass", $uneditable),
          '#required' => !array_key_exists("api_pass", $uneditable),
        ],
        'token_wrapper' => [
          '#type' => 'fieldset',
          '#title' => 'Oauth2.0 Token',
          '#description' => 'Token used for authentication with Genasys endpoint.',
          '#description_display' => 'before',
          'api_renewal' => [
            '#type' => 'radios',
            '#title' => t('Refresh/Request Token'),
            '#description' => t("Strategy for automated handling when token expiries.<br><i>Request gives better security as the token is changed periodically, Refresh is useful if the specified api_user credentials are shared with other appications.</i>"),
            '#description_display' => 'before',
            '#options' => [
              'refresh' => "Refresh <i>- will refresh the existing token when the current token expires</i>",
              'request' => "Request <i>- will request a new token when the current token expires</i>",
            ],
            '#default_value' => $settings["api_renewal"] ?? "request",
            '#disabled' => array_key_exists("api_renewal", $uneditable),
            '#required' => !array_key_exists("api_renewal", $uneditable),
          ],
          'api_token' => [
            '#type' => 'textfield',
            '#title' => t('Current Token'),
            '#prefix' => "<div id='edit-api-token'>",
            '#suffix' => "</div>",
            '#default_value' => $this->obfuscateToken($settings["api_token"] ?? ""),
            '#disabled' => TRUE,
            '#required' => FALSE,
          ],
          'generate_token' => [
            '#type' => 'button',
            '#value' => t('Generate New Token'),
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
        'groups' => [
          '#type' => 'textfield',
          '#title' => t('Subscription Groups'),
          '#description' => t('Enter a list of Genasys-defined groups that users will be subscribed to.<br><i>Separate each group with a comma (e.g. group1,group2).</i>'),
          '#default_value' => $settings["groups"] ?? "",
          '#disabled' => array_key_exists("groups", $uneditable),
          '#required' => !array_key_exists("groups", $uneditable),
          '#attributes' => [
            "placeholder" => 'e.g. group1,group2',
          ],
        ],
        'types' => [
          '#type' => 'textfield',
          '#title' => t('Notification Types'),
          '#description' => t('Enter a list of Genasys-defined notification types that users will be subscribed to.<br><i>Separate each type with a comma (e.g. type1,types2).</i>'),
          '#default_value' => $settings["types"] ?? "",
          '#disabled' => array_key_exists("types", $uneditable),
          '#required' => !array_key_exists("types", $uneditable),
          '#attributes' => [
            "placeholder" => 'e.g. type1,types2',
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
      if (!$form['groups']['#disabled']) {
        $config->set('api_config.genasys.groups', $form_values['groups']);
      }
      if (!$form['types']['#disabled']) {
        $config->set('api_config.genasys.types', $form_values['types']);
      }
      $config->set('api_config.genasys.api_renewal', $form_values['api_renewal'] ?? "request");
      $config->save();
    }

    return $event;

  }

  /**
   * @inheritDoc
   */
  public function validateForm(EmergencyAlertsValidateFormEvent $event, string $event_id): EmergencyAlertsValidateFormEvent {

    if ((string) $event->form_state->getTriggeringElement()["#value"] == "Generate New Token"){
      // We don't want the ajax callback to validate anything.
      $event->form_state->disableCache();
      $event->form_state->clearErrors();
    }
    return $event;
  }

  /**
   * This is the callback from the form when the "Generate New Token" button
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

    // Generate a new token.
    $token = $this->authenticate(FALSE);

    // Update and return the form element.
    $form["bos_emergency_alerts"]["emergency_alerts_settings"]["api_config"]["genasys"]['token_wrapper']["api_token"]["#default_value"] = $token;
    $form["bos_emergency_alerts"]["emergency_alerts_settings"]["api_config"]["genasys"]['token_wrapper']["api_token"]["#value"] = $token;
    \Drupal::messenger()->addStatus("New Token generated and saved.");
    return $form["bos_emergency_alerts"]["emergency_alerts_settings"]["api_config"]["genasys"]['token_wrapper']["api_token"];

  }

  /**
   * Generate a new Oauth2.0 token from Genesys endpoint.
   */
  private function authenticate(bool $refresh = FALSE): string|bool {

    $config = \Drupal::configFactory()
      ->getEditable('bos_emergency_alerts.settings');
    $settings = $config->get("api_config")["genasys"];

    $auth = "{$settings["api_clientid"]}:{$settings["api_clientsecret"]}";
    $auth = base64_encode($auth);

    $url = "{$settings["api_base"]}/oauth/token";
    $headers = ["Authorization" =>  "Basic {$auth}"];

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
      $ch = $this->makeCurl($url, $post_fields, $headers, '', "POST");
      $result = $this->executeCurl($ch, FALSE);
    }
    catch (\Exception $e) {
      return FALSE;
    }

    if ($result) {
      $config->set("api_config.genasys.api_token", $result["access_token"])
       ->set("api_config.genasys.refresh_token", $result["refresh_token"])
       ->set("api_config.genasys.token_expiry", intval($result["date"]/1000) + ($result["expires_in"] - 1)) // round ms down
       ->save();
      return $result["access_token"];
    }

    return FALSE;

  }

  private function validateToken():bool {

    $config = \Drupal::configFactory()
      ->getEditable('bos_emergency_alerts.settings');
    $settings = $config->get("api_config")["genasys"];

    if (($settings["token_expiry"] - 120) <= strtotime("now")) {
      // Saved token is within 2 mins of expiry so must get a new one.
      $strategy = $config->get('api_renewal') ?? "request";
      if (!$this->authenticate($strategy == "refresh")) {
        return FALSE;
      }
    }

    return TRUE;

  }

  private function obfuscateToken(string $token = "") {
    if (!empty($token)) {
      $token = trim($token);
      return substr($token, 0, 4) . "****-****" . substr($token, -4, 4);
    }
    return "No Token";
  }

}
