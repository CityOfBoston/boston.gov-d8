<?php

namespace Drupal\bos_emergency_alerts\Controller;

use Drupal\bos_core\Services\BosCoreGAPost;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Logger\LoggerChannelFactory;
use Drupal\Core\Mail\MailManager;
use GuzzleHttp\Client;
use GuzzleHttp\Cookie\CookieJar;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Class EverbridgeSubscriber.
 *
 * @package Drupal\bos_emergency_alerts\Controller
 */
class EverbridgeSubscriber extends ControllerBase {

  /**
   * API endpoints for message types.
   *
   * @var array
   */
  protected $uri = [
    "login" => "/api/login",
    "contacts" => "/rest/contacts/454102597238915",
  ];

  /**
   * Current request object.
   *
   * @var \Symfony\Component\HttpFoundation\Request|null
   */
  protected $request;

  /**
   * Logger object for class.
   *
   * @var \Drupal\Core\Logger\LoggerChannelInterface
   */
  protected $log;

  /**
   * Mail object for class.
   *
   * @var \Drupal\Core\Mail\MailManager
   */
  protected $mail;

  /**
   * Google Anaytics object for class.
   *
   * @var \Drupal\bos_core\Services\BosCoreGAPost
   */
  protected $gapost;

  /**
   * EverbridgeSubscriber create.
   *
   * @inheritdoc
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('request_stack'),
      $container->get('logger.factory'),
      $container->get('plugin.manager.mail'),
      $container->get('bos_core.gapost')
    );
  }

  /**
   * EverbridgeSubscriber constructor.
   *
   * @inheritdoc
   */
  public function __construct(RequestStack $requestStack, LoggerChannelFactory $logger, MailManager $mail, BosCoreGAPost $gapost) {
    $this->request = $requestStack->getCurrentRequest();
    $this->log = $logger->get('EmergencyAlerts');
    $this->mail = $mail;
    $this->gapost = $gapost;
  }

  /**
   * Magic function.  Catches calls to endpoints that dont exist.
   *
   * @param string $name
   *   Name.
   * @param mixed $arguments
   *   Arguments.
   */
  public function __call($name, $arguments) {
    throw new NotFoundHttpException();
  }

  /**
   * Extends the config function.
   *
   * @param string $name
   *   The config(setting) to be managed.
   *
   * @return array|\Drupal\Core\Config\Config|mixed|null
   *   The value of the setting being managed.
   */
  public function config($name) {
    $config = parent::config("bos_emergency_alerts.settings");
    if (isset($name)) {
      return $config->get($name);
    }
    return $config->getRawData();
  }

  /**
   * The main entrypoint for the controller.
   *
   * @param string $action
   *   The action being called via the endpoint uri.
   *
   * @return \Symfony\Component\HttpFoundation\Response
   *   The JSON output string.
   */
  public function api($action) {
    switch ($this->request->getMethod()) {
      case "POST":
        return $this->$action($this->request->request->all());
    }
  }

  /**
   * This is the local /rest/everbridge/subscribe endpoint.
   *
   * @return \Symfony\Component\HttpFoundation\Response
   *   Response from the Everbridge remote API.
   */
  private function subscribe($payload) {
    $everbridge = $this->config("codered_settings");

    if (isset($_ENV['EVERBRIDGE_SETTINGS'])) {
      $everbridge_env = (object) [];
      $get_vars = explode(",", $_ENV['EVERBRIDGE_SETTINGS']);
      foreach ($get_vars as $item) {
        $json = explode(":", $item);
        $everbridge_env->{$json[0]} = $json[1];
      }
      $everbridge_env = json_encode($everbridge_env);
    }
    else {
      $everbridge_env = '{
        "org_id":454102597238915,
        "rec_type_id":487225385025537,
        "text":241901148045324,
        "phone":219910915489799,
        "email":241901148045317,
        "language_id":487225385025538
      }';
    }
    $everbridge_env = json_decode($everbridge_env);

    if (!empty($everbridge['api_base']) && !empty($everbridge['api_pass']) && !empty($everbridge['api_user'])) {
      // Track this page.
      $this->gapost->pageview($this->request->getRequestUri(), "CoB REST | Everbridge Subscription");

      $uri = $this->uri['contacts'];

      // Make a customKey.
      if (!empty($payload['email'])) {
        $customKey = $this->stringtohex($payload['email']);
      }
      elseif (!empty($payload['phone_number'])) {
        $customKey = $this->stringtohex($payload['phone_number']);
      }
      else {
        $customKey = $this->stringtohex($payload['first_name'] . $payload['last_name']);
      }

      $fields = [
        // Required to post to API.
        "organizationId" => $everbridge_env->org_id,
        "recordTypeId" => $everbridge_env->rec_type_id,
        "externalId" => $customKey,
        "lastName" => $payload['last_name'],
        "firstName" => $payload['first_name'],
        // End required items.
        "country" => "US",
        "uploadProcessing" => FALSE,
        "timezoneId" => "America/New_York",
      ];
      $fields_paths = [];
      if ($payload['text'] && $payload['phone_number'] !== "") {
        $paths_text = [
          "waitTime" => 0,
          "countryCode" => "US",
          "pathId" => $everbridge_env->text,
          "value" => $payload['phone_number'],
          "skipValidation" => FALSE,
        ];
        array_push($fields_paths, $paths_text);
      }
      if ($payload['call'] && $payload['phone_number'] !== "") {
        $paths_call = [
          "waitTime" => 0,
          "countryCode" => "US",
          "pathId" => $everbridge_env->phone,
          "value" => $payload['phone_number'],
          "skipValidation" => FALSE,
        ];
        array_push($fields_paths, $paths_call);
      }
      if ($payload["email"] !== "") {
        $paths_email = [
          "waitTime" => 0,
          "pathId" => $everbridge_env->email,
          "value" => $payload['email'],
          "skipValidation" => FALSE,
        ];
        array_push($fields_paths, $paths_email);
      }
      $fields_paths_full["paths"] = $fields_paths;
      $fields = array_merge($fields, $fields_paths_full);

      if ($payload["address"] !== "") {
        $fields_address = [
          "address" => [
            [
              "streetAddress" => $payload['address'],
              "postalCode" => $payload['zip_code'],
              "source" => "MANUAL",
              "state" => $payload['state'],
              "locationName" => "Home",
              "country" => "US",
              "city" => $payload['city'],
            ],
          ]
        ];
        $fields = array_merge($fields, $fields_address);
      }

      if ($payload["language"] !== "") {
        $fields_language = [
          "contactAttributes" => [
            [
              "name" => "Language",
              "orgAttrId" => $everbridge_env->language_id,
              "values" => [$payload["language"]]
            ],
          ]
        ];
        $fields = array_merge($fields, $fields_language);
      }

      $result = $this->post($uri, $fields, $everbridge_env);
    }
    else {
      $result = [
        "output" => "Missing Drupal Configuration.",
        "HTTP_CODE" => "500",
      ];
    }

    return $this->responseOutput($result['output'], $result['http_code']);
  }

  /**
   * Makes a standard (authenticating) POST to the Everbridge API.
   *
   * @param string $uri
   *   The endpoint being POSTED to.
   * @param array $fields
   *   Fields to be posted in the message.
   * @param object $everbridge_env
   *   Env variables for endpoint.
   * @param bool $cachebuster
   *   [optional] Appended random string to bust caching (NOT usually needed).
   *
   * @return array
   *   An output array with the Everbridge REST response and http_status_code.
   *
   * @throws \GuzzleHttp\Exception\GuzzleException
   */
  private function post($uri, array $fields, object $everbridge_env, $cachebuster = FALSE) {

    $everbridge = $this->config("codered_settings");
    $url = "https://api.everbridge.net/rest/contacts/" . $everbridge_env->org_id;

    // Add a random string at end of post to bust any caches.
    if (isset($cachebuster) && $cachebuster) {
      if (stripos($url, "?") > 0) {
        $url .= "&cobcb=" . rand();
      }
      else {
        $url .= "?cobcb=" . rand();
      }
    }

    // Build headers.
    if (!isset($headers)) {
      $headers = [];
    }

    // Make the post and return the response.
    try {
      $user = $everbridge['api_user'];
      $pass = $everbridge['api_pass'];
      $user_pass = base64_encode($user . ':' . $pass);
      $ch = curl_init();
      curl_setopt($ch, CURLOPT_URL, $url);
      curl_setopt($ch, CURLOPT_POST, TRUE);
      curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
      curl_setopt($ch, CURLOPT_HTTPHEADER, [
          "Authorization: Basic " . $user_pass,
          "Content-Type: application/json"
      ]);
      curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($fields));
      $info = curl_exec($ch);

      if (isset($info)) {
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $json = json_decode($info);
        if (json_last_error() <> 0) {
          throw new \Exception($info);
        }
      }
      else {
        throw new \Exception("Everbridge Endpoint Error");
      }
    }
    catch (\Exception $e) {
      $json = '{"errors":' . $e->getMessage() . '}';
      $http_code = Response::HTTP_INTERNAL_SERVER_ERROR;
    }

    return [
      "output" => $json,
      "http_code" => $http_code,
    ];

  }

  /**
   * Helper: Formats a standardised Response object.
   *
   * @param string $message
   *   Message to JSON'ify.
   * @param int $type
   *   Response constant for the HTTP Status code returned.
   *
   * @return \Symfony\Component\HttpFoundation\Response
   *   Full Response object to be returned to caller.
   */
  private function responseOutput($message, $type) {
    $json = [
      'status' => 'error',
      'contact' => $message,
    ];
    $response = new Response(
      json_encode($json),
      $type,
      [
        'Content-Type' => 'application/json',
        'Cache-Control' => 'must-revalidate, no-cache, private',
        'X-Generator-Origin' => 'City of Boston (https://www.boston.gov)',
        'Content-Language' => 'en',
      ]
    );
    switch ($type) {
      case Response::HTTP_CREATED:
      case Response::HTTP_OK:
      case Response::HTTP_NON_AUTHORITATIVE_INFORMATION:
        $json['status'] = 'success';
        $response->setContent(json_encode($json));
        break;

      case Response::HTTP_UNAUTHORIZED:
      case Response::HTTP_NO_CONTENT:
      case Response::HTTP_FORBIDDEN:
      case Response::HTTP_BAD_REQUEST:
      case Response::HTTP_METHOD_NOT_ALLOWED:
      case Response::HTTP_INTERNAL_SERVER_ERROR:
        $json['status'] = 'error';
        $json['errors'] = $message;
        unset($json['contact']);
        $response->setContent(json_encode($json));
        // Write log.
        $this->log
          ->error("Internal Error");
        // Send email.
        $this->mailAlert();
        break;
    }
    return $response;
  }

  /**
   * Convert each char in a string to a hex "number" and output new string.
   *
   * @param string $string
   *   A string which is to be converted into a hex number.
   * @param int $maxlen
   *   Maximum number of chars to be returned.
   *
   * @return string
   *   The string in hex format.
   */
  private function stringtohex($string, $maxlen = 50) {
    $hex = '';

    for ($i = 0; $i < strlen($string); $i++) {
      $ord = ord($string[$i]);
      $hex .= substr('0' . dechex($ord), -2);
    }

    return substr(strtoupper($hex), 0, $maxlen);
  }

  /**
   * Helper function to email alerts.
   *
   * Actual email formatted in bos_emergency_alerts_mail().
   */
  private function mailAlert() {
    $request = $this->request->request->all();
    $everbridge = $this->config("codered_settings");

    if (empty($everbridge["email_alerts"])) {
      $this->log->warning("Emergency_alerts email recipient is not set.  An error has been encountered, but no email has been sent.");
      return;
    }

    $params['message'] = $request;
    $result = $this->mail->mail("bos_emergency_alerts", "subscribe_error", $everbridge["email_alerts"], "en", $params, NULL, TRUE);

    if ($result['result'] !== TRUE) {
      $this->log->warning("There was a problem sending your message and it was not sent.");
    }
  }

}
