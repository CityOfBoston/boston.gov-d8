<?php

namespace Drupal\bos_google_cloud\Services;

use Drupal;
use Drupal\bos_core\Controllers\Curl\BosCurlControllerBase;
use Drupal\bos_google_cloud\GcGenerationURL;
use Drupal\bos_google_cloud\src\GcGenerationPayload;
use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\Config\ImmutableConfig;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Logger\LoggerChannelFactory;
use Drupal\Core\Logger\LoggerChannelInterface;
use Drupal\bos_core\Controllers\Settings\CobSettings;
use Drupal\Core\Render\Markup;
use Exception;

/**
  class GcConversation
  Creates a gen-ai conversation service for bos_google_cloud

  david 01 2024
  @file docroot/modules/custom/bos_components/modules/bos_google_cloud/src/Services/GcConversation.php
*/

class GcConversation extends BosCurlControllerBase implements GcServiceInterface {

    /**
   * Logger object for class.
   *
   * @var \Drupal\Core\Logger\LoggerChannelInterface
   */
  protected LoggerChannelInterface $log;

  /**
   * Config object for class.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  protected ImmutableConfig $config;

  protected array $settings;

  /**
   * @var GcAuthenticator Google Cloud Authenication Service.
   */
  protected GcAuthenticator $authenticator;

  public function __construct(LoggerChannelFactory $logger, ConfigFactory $config) {

    // Load the service-supplied variables.
    $this->log = $logger->get('GcAuthenticator');
    $this->config = $config->get("bos_google_cloud.settings");

    $this->settings = CobSettings::getSettings("GCAPI_SETTINGS", "bos_google_cloud");

    // Create an authenticator using service account 1.
    $this->authenticator = new GcAuthenticator($this->settings['conversation']['service_account'] ?? GcAuthenticator::SVS_ACCOUNT_LIST[0]);

    // Do the CuRL initialization in BosCurlControllerBase.
    parent::__construct();

  }

  /**
   * Set the service_account, overriding the default.
   *
   * @param string $service_account A valid service account.
   *
   * @return $this
   * @throws \Exception
   */
  public function setServiceAccount(string $service_account):GcConversation {
    if (!$this->authenticator->validateServiceAccount($service_account)) {
      throw new Exception("Service account does not exist.");
    }
    $this->settings['service_account'] = $service_account;
    return $this;
  }

  /**
   *  Adds some text to a conversation, using a pre-defined prompt.
   *
   * @param array $parameters Array containing "text" URLencode text to be summarized, and "prompt" A search type prompt.
   *
   * @return string
   * /
   */
  public function execute(array $parameters = []): string {

    $settings = $this->settings["conversation"] ?? [];
    try {
      $headers = [
        "Authorization" => $this->authenticator->getAccessToken($settings['service_account'], "Bearer")
      ];
    }
    catch (Exception $e) {
      $this->error = $e->getMessage() ?? "Error getting access token.";
      return $this->error();
    }

    if (empty($parameters["text"])) {
      $this->error = "A conversation string is required.";
      return $this->error();
    }

    $parameters["prompt"] = $parameters["prompt"] ?? "default";

    $url = GcGenerationURL::build(GcGenerationPayload::CONVERSATION, $settings);

    if (!$payload = GcGenerationPayload::build(GcGenerationPayload::CONVERSATION, $parameters)) {
      $this->error = "Could not build Payload";
      return $this->error;
    }

    $results = $this->post($url, $payload, $headers);

    if ($this->http_code() == 200 && !$this->error()) {

      $this->response["conversation"] = [];

      $this->response["conversation"]["results"] = $results["reply"]["summary"]["summaryWithMetadata"];
      $this->response["conversation"]["conversation"] = $results["conversation"];
      $this->response["conversation"]["results"]["webpages"] = $results["searchResults"];
      $this->loadSafetyRatings($results["reply"]["summary"]["safetyAttributes"]);
      unset($this->response["body"]);

      // TODO: if we are "conversing" then need to create a unique ID, save it
      //  as a timesensitive keyvalue and return the ID so that it can be
      //  accessed to consinute the conversation.
      //  -- maybe for search we dont do this, and thats another difference
      //     between the search and conversation implementations.

      if (empty($this->response["conversation"]["results"]) || $this->error()) {
        $this->error() || $this->error = "Unexpected response from GcConversation";
        return $this->error();
      }

      return $this->response["conversation"]["results"]["summary"];

    }
    elseif ($this->error()) {
      return "";
    }
    else {
      $this->error = "Unknown Error: " . $this->response["http_code"];
      return "";
    }

  }

  /**
   * Update the $this->response["conversation"]["ratings"] array if the safety scores
   * in $ratings are higher (less safe) than those already stored.
   *
   * @param array $ratings The safetyRatings from a gemini ::predict call.
   *
   * @return void
   */
  private function loadSafetyRatings(array $ratings): void {

    if (!isset($this->response["conversation"]["safetyRatings"])) {
      $this->response["conversation"]["safetyRatings"] = [];
    }

    foreach($ratings["categories"] as $key => $rating) {
      $this->response["conversation"]["safetyRatings"][$rating] = $ratings["scores"][$key];
    }

  }

  /**
   * @inheritDoc
   */
  public function buildForm(array &$form): void {

    $project_id="612042612588";
    $model_id="drupalwebsite_1702919119768";
    $location_id="global";
    $endpoint="https://discoveryengine.googleapis.com";

    $settings = $this->settings['conversation'] ?? [];

    $svs_accounts = [];
    foreach ($this->settings["auth"] as $name => $value) {
      if ($name) {
        $svs_accounts[$name] = $name;
      }
    }

    $form = $form + [
      'project_id' => [
        '#type' => 'textfield',
        '#title' => t('The project ID to use'),
        '#description' => t(''),
        '#default_value' => $settings['project_id'] ?? $project_id,
        '#required' => TRUE,
        '#attributes' => [
          "placeholder" => 'e.g. ' . $project_id,
        ],
      ],
      'datastore_id' => [
        '#type' => 'textfield',
        '#title' => t('The Data Store to use:'),
        '#description' => t(''),
        '#default_value' => $settings['datastore_id'] ?? $model_id,
        '#required' => TRUE,
        '#attributes' => [
          "placeholder" => 'e.g. ' . $model_id,
        ],
      ],
      'location_id' => [
        '#type' => 'textfield',
        '#title' => t('The Model Location to use (= a "global")'),
        '#description' => t(''),
        '#default_value' => $settings['location_id'] ?? $location_id,
        '#required' => TRUE,
        '#disabled' => TRUE,
        '#attributes' => [
          "placeholder" => 'e.g. ' . $location_id,
        ],
      ],
      'endpoint' => [
        '#type' => 'textfield',
        '#title' => t('The endpoint to use'),
        '#description' => t(''),
        '#default_value' => $settings['endpoint'] ?? $endpoint,
        '#required' => TRUE,
        '#attributes' => [
          "placeholder" => 'e.g. ' . $endpoint,
        ],
      ],
      'service_account' => [
        '#type' => 'select',
        '#title' => t('The default service account to use'),
        '#description' => t('This default can be overridden using the API.'),
        '#default_value' => $settings['service_account'] ?? ($svs_accounts[0] ?? ""),
        '#options' => $svs_accounts,
        '#required' => TRUE,
        '#attributes' => [
          "placeholder" => 'e.g. ' . ($svs_accounts[0] ?? "No Service Accounts!"),
        ],
      ],
      'test_wrapper' => [
          'test_button' => [
            '#type' => 'button',
            "#value" => t('Test Conversation'),
            '#attributes' => [
              'class' => ['button', 'button--primary'],
              'title' => "Test the provided configuration for this service"
            ],
            '#access' => TRUE,
            '#ajax' => [
              'callback' => [$this, 'ajaxTestService'],
              'event' => 'click',
              'wrapper' => 'edit-convo-result',
              'disable-refocus' => TRUE,
              'progress' => [
                'type' => 'throbber',
              ]
            ],
            '#suffix' => '<span id="edit-convo-result"></span>',
          ],
        ],
    ];
  }

  /**
   * @inheritDoc
   */
  public function submitForm(array $form, FormStateInterface $form_state): void {

    $values = $form_state->getValues()["google_cloud"]['services_wrapper']['conversation'];
    $config = Drupal::configFactory()->getEditable("bos_google_cloud.settings");

    if ($config->get("conversation.project_id") != $values['project_id']
      ||$config->get("conversation.datastore_id") != $values['datastore_id']
      ||$config->get("conversation.location_id") != $values['location_id']
      ||$config->get("conversation.service_account") != $values['service_account']
      ||$config->get("conversation.endpoint") != $values['endpoint']) {
      $config->set("conversation.project_id", $values['project_id'])
        ->set("conversation.datastore_id", $values['datastore_id'])
        ->set("conversation.location_id", $values['location_id'])
        ->set("conversation.endpoint", $values['endpoint'])
        ->set("conversation.service_account", $values['service_account'])
        ->save();
    }

  }

  /**
   * @inheritDoc
   */
  public function validateForm(array $form, FormStateInterface &$form_state): void {
    // not required
  }


  /**
   * Ajax callback to test Conversation Service.
   *
   * @param array $form
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *
   * @return array
   */
  public static function ajaxTestService(array &$form, FormStateInterface $form_state): array {

    $values = $form_state->getValues()["google_cloud"]['services_wrapper']['conversation'];
    $conversation = Drupal::service("bos_google_cloud.GcConversation");

    $options = [
      "text" => "How do I pay a parking ticket",
      "prompt" => "default",
    ];

    unset($values["test_wrapper"]);
    $conversation->settings = CobSettings::array_merge_deep($conversation->settings, ["conversation" => $values]);
    $result = $conversation->execute($options);

    if (!empty($result)) {
      return ["#markup" => Markup::create("<span id='edit-convo-result' style='color:green'><b>&#x2714; Success:</b> Authentication and Service Config are OK.</span>")];
    }
    else {
      return ["#markup" => Markup::create("<span id='edit-convo-result' style='color:red'><b>&#x2717; Failed:</b> {$conversation->error()}</span>")];
    }

  }

}
