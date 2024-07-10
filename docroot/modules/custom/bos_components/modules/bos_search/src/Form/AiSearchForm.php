<?php

namespace Drupal\bos_search\Form;

use Drupal\bos_search\AiSearchRequest;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/*
  class PromptTesterForm
  Creates the Administration/Configuration form for bos_google_cloud

  david 04 2024
  @file docroot/modules/custom/bos_components/modules/bos_google_cloud/src/Form/src/Form/PromptTesterForm.php
*/

class AiSearchForm extends FormBase {

  /**
   * This form allows a user to submit a conversation-based search.
   */

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'bos_search_AISearchForm';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {

    if ($preset = $form_state->getValue("preset")  ?: \Drupal::request()->get("preset", "")) {
      $config = $this->config("bos_search.settings")->get("presets.$preset");
      $form_theme = $config["modalform"]["theme"] ?: "default";
    }

    $form = [

      "#theme" => ["form--$form_theme"],

      'AiSearchForm' => [
        '#tree' => FALSE,
        '#type' => 'fieldset',
        "#theme" => "fieldset--$form_theme",
        'search' => [
          '#type' => 'container',
          '#attributes' => [
            'id' => ['edit-aisearchform'],
          ],
          "#theme" => "container--$form_theme",

          'preset' => [
            '#type' => 'hidden',
            '#default_value' => $preset,
            "#theme" => "input--$form_theme",
          ],
          'conversation_id' => [
            '#type' => 'hidden',
            '#default_value' => $form_state->getValue("conversation_id")  ?: "",
            "#theme" => "input--$form_theme",
          ],
          'messages' => [
            '#type' => 'container',
            '#attributes' => ['id' => ['edit-messages']],
            "#theme" => "container--$form_theme",
          ],
          'searchresults' => [
            '#type' => 'container',
            '#attributes' => ['id' => ['edit-searchresults']],
            "#theme" => "container--$form_theme",
          ],
          'searchtext' => [
            '#type' => 'textarea',
            '#title' => $this->t("Search Phrase"),
            '#title_attributes' => [
              'class' => ['searchtext-legend']
            ],
            '#default_value' => "Paste or input text here",
            '#rows' => 2,
            '#attributes' => [
              "class" => ["searchtext"],
            ],
            "#theme" => "textarea--$form_theme",
          ],
          'submit' => [
            '#type' => 'button',
            '#value' => 'Search',
            '#ajax' => [
              'callback' => '::ajaxCallbackSearch',
              'wrapper' => 'edit-aisearchform',
            ],
            "#theme" => "input--$form_theme",
          ],
        ],
      ],
    ];

    return $form;

  }

  /**
   * Ajax callback to run the desired test against the selected AI Model.
   *
   * @param array $form
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *
   * @return array
   * @throws \Exception
   */
  public function ajaxCallbackSearch(array $form, FormStateInterface $form_state): array {

    $config = \Drupal::config("bos_search.settings")->get("presets");
    $values = $form_state->getUserInput();
    try {
      $preset = $config[$values["preset"]] ?? FALSE;
      if (!$preset) {
        throw new \Exception("Cannot find the preset {$values['preset']}");
      }
      $plugin_id = $preset["aimodel"];
      /** @var \Drupal\bos_search\Plugin\AiSearch\AiSearchPluginManager $plugin */
      $plugin = \Drupal::service("plugin.manager.aisearch")
        ->createInstance($plugin_id);
      $form_values = $form_state->getUserInput();
      $search = new AiSearchRequest($form_values["searchtext"], $preset['results']["result_count"] ?? 0, $preset['results']["output_template"]);
      if (!empty($form_values["conversation_id"])) {
        $search->set("conversation_id", $form_values["conversation_id"]);
      }
      $result = $plugin->search($search);
    }
    catch (\Exception $e) {
      // TODO: Create and populate an error message on the page..
      \Drupal::messenger()->addError("Test");
    }

    // Recreate the results container and set the rendered results
    $show_citations = ($preset['results']["citations"] == 1);
    $show_references = ($preset['results']["references"] == 1);
    $show_metadata = ($preset['results']["metadata"] == 1);

    $form["AiSearchForm"]["search"]["searchresults"] = [
      '#type' => 'container',
      '#attributes' => ['id' => ['edit-searchresults']],
      'output' => [
        '#markup' => $result->render($show_citations, $show_references, $show_metadata),
      ],
    ];

    // Return the results container.
    return $form["AiSearchForm"]["search"];
  }

  /**
   * @inheritDoc
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Not required for this test form.
  }

}
