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
 * Class CodeRedSubscriber.
 *
 * @package Drupal\bos_emergency_alerts\Controller
 */
class CodeRedSubscriber extends ControllerBase {

  /**
   * API endpoints for message types.
   *
   * @var array
   */
  protected $uri = [
    "login" => "/api/login",
    "contact-list" => "/api/contacts",
    "contact" => "/api/contacts/{}",
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
   * CodeRedSubscriber create.
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
   * CodeRedSubscriber constructor.
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
   * This is the local /rest/codered/subscribe endpoint.
   *
   * @return \Symfony\Component\HttpFoundation\Response
   *   Response from the codered remote API.
   */
  private function subscribe($payload) {
    $codered = $this->config("codered_settings");

    if (!empty($codered['api_base']) && !empty($codered['api_pass']) && !empty($codered['api_user'])) {
      // Track this page.
      $this->gapost->pageview($this->request->getRequestUri(), "CoB REST | CodeRed Subscription");

      $uri = $this->uri['contact-list'];

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
        "CustomKey" => $customKey,
        "FirstName" => $payload['first_name'],
        "LastName" => $payload['last_name'],
        'MobileProvider' => "Sprint",
        'OtherPhone' => "",
        'TextNumber' => "",
        "HomeEmail" => $payload['email'],
        "Zip" => $payload['zip'],
        "PreferredLanguage" => $payload['language'],
        "Groups" => isset($codered['api_group']) ? $codered['api_group'] : 'website signups',
      ];
      if ($payload['call']) {
        $fields["OtherPhone"] = $payload['phone_number'];
      }
      if ($payload['text']) {
        $fields["TextNumber"] = $payload['phone_number'];
      }

      $headers = [
        "Cache-Control: no-cache",
      ];
      $result = $this->post($uri, $fields, $headers);
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
   * Makes a standard (authenticating) POST to the codered API.
   *
   * @param string $uri
   *   The endpoint being POSTED to.
   * @param array $fields
   *   Fields to be posted in the message.
   * @param array $headers
   *   Extra non-default hedaers to add.
   * @param bool $cachebuster
   *   [optional] Appended random string to bust caching (NOT usually needed).
   *
   * @return array
   *   An output array with the codered REST response and http_status_code.
   *
   * @throws \GuzzleHttp\Exception\GuzzleException
   */
  private function post($uri, array $fields, array $headers, $cachebuster = FALSE) {

    $codered = $this->config("codered_settings");
    $url = $codered['api_base'] . "/" . ltrim($uri, "/");

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
    $client = new Client();
    try {
      $jar = new CookieJar();
      $authenticate = $client->request('GET', $codered['api_base'] . "/api/login", [
        'cookies' => $jar,
        'query' => [
          "username" => $codered['api_user'],
          "password" => $codered['api_pass'],
        ],
      ]);

      if (isset($authenticate)) {
        $client = new Client();
        $response = $client->post($url, [
          'cookies' => $jar,
          'form_params' => $fields,
          'headers' => $headers,
        ]);
        if (isset($response)) {
          $http_code = $response->getStatusCode();
          $output = $response->getBody()->getContents();
          $json = json_decode($output);
          if (json_last_error() <> 0) {
            throw new \Exception($output);
          }
        }
        else {
          throw new \Exception("CodeRed Endpoint Error");
        }
      }
      else {
        throw new \Exception("Authentication Error");
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
        'X-COB-Cityscore' => $this->action,
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
        $json['errors'] = "Sorry, internal error.";
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
  private function stringtohex($string, $maxlen = 140) {
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
    $codered = $this->config("codered_settings");

    if (empty($codered["email_alerts"])) {
      $this->log->warning("Emergency_alerts email recipient is not set.  An error has been encountered, but no email has been sent.");
      return;
    }

    $params['message'] = $request;
    $result = $this->mail->mail("bos_emergency_alerts", "subscribe_error", $codered["email_alerts"], "en", $params, NULL, TRUE);

    if ($result['result'] !== TRUE) {
      $this->log->warning("There was a problem sending your message and it was not sent.");
    }
  }

}
