<?php

namespace Drupal\bos_google_cloud\Form;

use Drupal;
use Drupal\bos_google_cloud\GcGenerationPrompt;
use Drupal\bos_google_cloud\Services\GcAuthenticator;
use Drupal\bos_google_cloud\Services\GcGeocoder;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Markup;

/**
 * Creates a config/admin form for bos_google_cloud module.
 *
 * david 01 2024
 * @file docroot/modules/custom/bos_components/modules/bos_google_cloud/src/Form/configForm.php
 */
class ConfigForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'bos_google_cloud_configForm';
  }

  /**
   * @inheritDoc
   */
  protected function getEditableConfigNames(): array {
    return ["bos_google_cloud.settings"];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    // Base form structure
    $form = [
      'google_cloud' => [
        '#type' => 'fieldset',
        '#title' => 'Google Cloud API Config',
        "#markup" => "<h5>This page allows you to set the various Google Cloud configurations and locations for Services exposed in this module.</h5>",
        '#tree' => true,
        'authentication_wrapper' => [
          '#type' => "fieldset",
          '#title' => "Authentication",
          "#markup" => "<h5>Configure the shared authentication processes.</h5>",
          'authentication' => [
            '#type' => 'details',
            '#title' => 'Google Cloud Authentication',
            '#open' => FALSE,
          ],
        ],

        'services_wrapper' => [
          '#type' => "fieldset",
          '#title' => "Services",
          "#markup" => "<h5>Configure Google Cloud Service implementations.</h5>",
          'search' => [
            '#type' => 'details',
            '#title' => 'Gen-AI Search',
            '#markup' => Markup::create("Search a dataset containing boston.gov pages and return a summary text, page results, annotations and references."),
            "#description" => "This Service is intended to use Vertex AI Search and Conversation API",
            '#open' => FALSE,
          ],
          'conversation' => [
            '#type' => 'details',
            '#title' => 'Gen-AI Conversation',
            '#markup' => Markup::create("Perform a new or ongoing conversation with an app based on a dataset containing boston.gov pages."),
            "#description" => "This Service is intended to use Vertex AI Search and Conversation API",
            '#open' => FALSE,
          ],
          'rewriter' => [
            '#type' => 'details',
            '#title' => 'Gen-AI Text Rewriter',
            '#markup' => Markup::create("Using gen-ai rewrite a section of text according to various prompts."),
            "#description" => "This Service is intended to use Gemini-Pro the LLM, or some other replacement.",
            '#open' => FALSE,
          ],
          'summarizer' => [
            '#type' => 'details',
            '#title' => 'Gen-AI Text Summarizer',
            '#markup' => Markup::create("Using gen-ai summarize a section of text according to various prompts."),
            "#description" => "This Service is intended to use Gemini-Pro the LLM, or some other replacement.",
            '#open' => FALSE,
          ],
          'translate' => [
            '#type' => 'details',
            '#title' => 'Gen-AI Translation',
            '#markup' => Markup::create("Using gen-ai translate a section of text from English to a non-English language."),
            "#description" => "This Service is intended to use Gemini-Pro the LLM, or some other replacement.",
            '#open' => FALSE,
          ],
          'geocoder' => [
            '#type' => 'details',
            '#title' => 'Google Cloud Geocoder',
            '#markup' => Markup::create("<p style='color:red'><b>These configurations are managed by the Geocoder Service (bos_geocoder). Click here <a href='/admin/config/system/boston/geocoder'>to edit</a>.</b></p>"),
            '#open' => FALSE,
          ],
        ],

        'prompts_wrapper' => [
          '#type' => "fieldset",
          '#title' => "Prompt Settings",
          "#description" => "The following are prompts which can be defined for the various services.",
        ],

      ]
    ];

    // Authentication section.
    $authenticator = new GcAuthenticator();
    $authenticator->buildForm($form['google_cloud']['authentication_wrapper']['authentication']);

    // Search section
    /**
     * @var $search \Drupal\bos_google_cloud\Services\GcSearch
     */
    $search = Drupal::service("bos_google_cloud.GcSearch");
    $search->buildForm($form["google_cloud"]["services_wrapper"]["search"]);

    // Conversation section
    /**
     * @var $search \Drupal\bos_google_cloud\Services\GcSearch
     */
    $conversation = Drupal::service("bos_google_cloud.GcConversation");
    $conversation->buildForm($form["google_cloud"]["services_wrapper"]["conversation"]);

    // Rewiter section
    /**
     * @var $rewriter \Drupal\bos_google_cloud\Services\GcTextRewriter
     */
    $rewriter = Drupal::service("bos_google_cloud.GcTextRewriter");
    $rewriter->buildForm($form["google_cloud"]["services_wrapper"]["rewriter"]);

    // Summaraizer section
    /**
     * @var $summarizer \Drupal\bos_google_cloud\Services\GcTextSummarizer
     */
    $summarizer = Drupal::service("bos_google_cloud.GcTextSummarizer");
    $summarizer->buildForm($form["google_cloud"]["services_wrapper"]["summarizer"]);

    // Translation section
    /**
     * @var $search \Drupal\bos_google_cloud\Services\GcTranslation
     */
    $translation = Drupal::service("bos_google_cloud.GcTranslate");
    $translation->buildForm($form["google_cloud"]["services_wrapper"]["translate"]);

    // Geocoder section (copy settings across from bos_geocoder module)
    $geocoder = new GcGeocoder();
    $geocoder->buildForm($form['google_cloud']["services_wrapper"]['geocoder']);

    // Prompt section
    $prompt = new GcGenerationPrompt();
    $prompt->buildForm($form['google_cloud']["prompts_wrapper"]);

    return parent::buildForm($form, $form_state);

  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {

    // Authentication section.
    $authenticator = new GcAuthenticator();
    $authenticator->submitForm($form, $form_state);

    // Search section
    /**
     * @var $search \Drupal\bos_google_cloud\Services\GcSearch
     */
    $search = Drupal::service("bos_google_cloud.GcSearch");
    $search->submitForm($form, $form_state);

    // Conversation section
    /**
     * @var $conversation \Drupal\bos_google_cloud\Services\GcConversation
     */
    $conversation = Drupal::service("bos_google_cloud.GcConversation");
    $conversation->submitForm($form, $form_state);

    // Rewriter section
    /**
     * @var $rewriter \Drupal\bos_google_cloud\Services\GcTextRewriter
     */
    $rewriter = Drupal::service("bos_google_cloud.GcTextRewriter");
    $rewriter->submitForm($form, $form_state);

    // Summaraizer section
    /**
     * @var $summarizer \Drupal\bos_google_cloud\Services\GcTextSummarizer
     */
    $summarizer = Drupal::service("bos_google_cloud.GcTextSummarizer");
    $summarizer->submitForm($form, $form_state);

    // Translation section
    /**
     * @var $translation \Drupal\bos_google_cloud\Services\GcTranslation
     */
    $translation = Drupal::service("bos_google_cloud.GcTranslate");
    $translation->submitForm($form, $form_state);

    // Prompt section
    $prompt = new GcGenerationPrompt();
    $prompt->submitForm($form, $form_state);

    parent::submitForm($form, $form_state); // optional
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state): void {
    parent::validateForm($form, $form_state);
  }

}
