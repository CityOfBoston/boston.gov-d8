<?php

namespace Drupal\bos_search\Form;

use Drupal\bos_search\AiSearch;
use Drupal\bos_search\Plugin\AiSearch\AiSearchPluginManager;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/*
  class SearchConfigForm
  Creates the Administration/Configuration form for bos_search

  david 06 2024
  @file docroot/modules/custom/bos_components/modules/bos_search/src/Form/SearchConfigForm.php
*/

class AiSearchConfigForm extends ConfigFormBase {

  /** @var $pluginManagerAiSearch AiSearchPluginManager */
  protected $pluginManagerAiSearch;

  public function __construct(ConfigFactoryInterface $config_factory, AiSearchPluginManager $plugin_manager_aisearch, protected $typedConfigManager = NULL) {
    parent::__construct($config_factory, $typedConfigManager);
    $this->pluginManagerAiSearch  = $plugin_manager_aisearch;
  }

  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('plugin.manager.aisearch'),
      $container->get('config.typed')
    );
  }
  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'bos_search_SearchConfigForm';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames(): array {
    return ["bos_search.settings"];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    $presets = AiSearch::getPresets();
    $form = [
      'SearchConfigForm' => [
        '#tree' => TRUE,
        '#type' => 'fieldset',
        '#title' => 'AI Search Configuration',
        'presets' => [
          "#type" => "fieldset",
          '#title' => "presets",
          '#attributes' => ["id" => "edit-presets"],
          '#description_display' => 'before',
          '#description' => "You can define any number of presets and use these in search form implementations.",
        ],
        'actions' => [
          'add' => [
            "#type" => "button",
            "#value" => "Add Preset",
            '#ajax' => [
              'callback' => '::ajaxAddPreset',
              'event' => 'click',
              'wrapper' => 'edit-presets',
              'disable-refocus' => FALSE,
              'limit' => FALSE
            ]
          ]
        ]
      ],
    ];

    // Get and populate each existing preset.
    foreach($presets as $pid => $preset) {
      $form['SearchConfigForm']['presets'][$pid] = $this->preset($pid);
    }

    if ($form_state->isRebuilding()) {
      // A rebuild does occur when an ajax button is clicked.
      if ($form_state->getTriggeringElement()["#value"] == $form["SearchConfigForm"]["actions"]["add"]["#value"]) {
        // The ajax "Add Preset" button has been clicked.
        $this->addPreset($form, $form_state);
      }
      elseif ($form_state->getTriggeringElement()["#value"] == "Delete Preset") {
        // An ajax "Delete Preset" button has been clicked.
        $this->deletePreset($form, $form_state);
      }
    }

    $form = parent::buildForm($form, $form_state);
    return $form;

  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    // Save the presets and any other fields to the settings config object.
    $values = $form_state->getUserInput();
    $config = $this->config('bos_search.settings');
    $params = [];
    foreach($values["SearchConfigForm"]["presets"] as &$preset) {
      if (empty($preset['pid'])) {
        $preset['pid'] = AiSearch::machineName($preset["name"]);
      }
      unset($preset["actions"]);
      $params[$preset['pid']] = $preset;
    }
    $config->set("presets", $params);
    $config->save();
    parent::submitForm($form, $form_state); // optional
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state): void {
    parent::validateForm($form, $form_state);
    // TODO: Create a form validator here.
    //        Typically alter the $form_state object.
    $values = $form_state->getValues();
    $trigger = $form_state->getTriggeringElement();
  }

  /**
   * Callback for Add Preset button on form.
   *
   * @param array $form
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *
   * @return mixed
   */
  public function ajaxAddPreset(array &$form, FormStateInterface $form_state) {
    // The buildForm will have been called twice by this time, once as a build,
    // and once as a rebuild.
    return $form['SearchConfigForm']['presets'];
  }

  /**
   * Add a New preset to the form object.
   * NOTE: nothing is saved until the config form is saved (submitted))
   *
   * @param $form
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *
   * @return void
   */
  public function addPreset(&$form, FormStateInterface $form_state) {
    $pid = count($form_state->getValues()['SearchConfigForm']['presets'] ?? []);
    $form['SearchConfigForm']['presets'][$pid] = $this->preset();
    $rand = intval(microtime(TRUE) * 1000);
    foreach($form['SearchConfigForm']['presets'][$pid] as $key => &$preset) {
      if (!str_contains($key, "#")) {
        $preset["#id"] = "edit-searchconfigform-presets-$pid-$key--$rand";
        $preset["#attributes"] = [
          "data-drupal-selector" => "edit-searchconfigform-presets-$pid-$key",
        ];
      }
    }
    $form['SearchConfigForm']['presets'][$pid]["#title"] = "New Preset";
    $form['SearchConfigForm']['presets'][$pid]["#open"] = TRUE;
    $form['SearchConfigForm']['presets'][$pid]["#id"] = "edit-searchconfigform-presets-$pid--$rand";
    $form['SearchConfigForm']['presets'][$pid]["#attributes"] = [
      "data-drupal-selector" => "edit-searchconfigform-presets-$pid",
    ];
  }

  /**
   * Callback for Delete Preset button on form.
   *
   * @param array $form
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *
   * @return mixed
   */

  public function ajaxDeletePreset(array &$form, FormStateInterface $form_state) {
    return $form['SearchConfigForm']['presets'];
  }

  /**
   * Delete the selected Preset.
   * NOTE: nothing is actually deleted until the config form is saved (submitted)
   *
   * @param array $form
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *
   * @return void
   */
  public function deletePreset(array &$form, FormStateInterface $form_state) {
    $pid = $form_state->getTriggeringElement()['#attributes']['data-pid'];
    unset($form['SearchConfigForm']['presets'][$pid]);
  }

  /**
   * Defines a preset fieldset on the form.
   *
   * @param string $pid
   *
   * @return array
   */
  private function preset(string $pid = "") {

    $preset = AiSearch::getPreset($pid) ?? [];

    /**
     * @var $models \Drupal\bos_search\Plugin\AiSearch\AiSearchPluginManager
     */
    $models = $this->pluginManagerAiSearch->getDefinitions();
    $model_opts = [];
    foreach($models as $model) {
      $model_opts[$model["id"]] = $model["id"];
    }

    $_templates = glob(\Drupal::service("extension.list.module")->getPath('bos_search') . "/templates/search_results/*.html.twig");
    $templates = [];
    foreach($_templates as $value) {
      $value = basename($value);
      $key = str_replace(".html.twig", "", $value);
      $templates[$key] = ucwords(str_replace(["_", "-"], " ", $key));
    };

    $_themes = glob(\Drupal::service("extension.list.module")->getPath('bos_search') . "/templates/form_elements/*", GLOB_ONLYDIR);
    $themes = [];
    foreach($_themes as $value) {
      $value = basename($value);
      $themes[$value] = ucwords(str_replace(["_", "-"], " ", $value));
    };

    $output = [
      '#type' => 'details',
      '#title' => (empty($preset) ? "": $preset['name']) . (empty($preset) ? "" : " (". $preset['aimodel'] .")"),
      '#open' => FALSE,

      'aimodel' => [
        '#type' => 'select',
        '#options' => $model_opts,
        "#default_value" => empty($preset) ? "" : ($preset['aimodel'] ?? "") ,
        '#title' => $this->t("Select the AI Model to use:"),
      ],
      'name' => [
        '#type' => 'textfield',
        '#required' => TRUE,
        '#title' => $this->t("Preset Name"),
        "#default_value" => empty($preset) ? "" : ($preset['name'] ?? ""),
        '#placeholder' => "Enter the name for this preset",
      ],
      'modalform' => [
        '#type' => 'fieldset',
        '#title' => 'Modal AI Search Form Styling',
        'theme' => [
          '#type' => 'select',
          '#options' => $themes,
          "#default_value" => empty($preset) ? "" : ($preset['results']['theme'] ?? "") ,
          '#title' => $this->t("Select the general form theme"),
        ],
        'disclaimer' => [
          '#type' => 'checkbox',
          "#default_value" => empty($preset) ? 0 : ($preset['modalform']['disclaimer'] ?? ""),
          '#title' => $this->t("Show a modal disclaimer when search launched"),
        ],
        'disclaimer_text' => [
          '#type' => 'textarea',
          "#default_value" => empty($preset) ? "" : ($preset['modalform']['disclaimer_text'] ?? ""),
          '#title' => $this->t("Disclaimer Text"),
        ],
        'footer' => [
          '#type' => 'textarea',
          "#default_value" => empty($preset) ? "" : ($preset['modalform']['footer'] ?? ""),
          '#title' => $this->t("Footer Text"),
        ],
      ],
      'results' => [
        '#type' => 'fieldset',
        '#title' => 'Search Results Section Styling',
        'output_template' => [
          '#type' => 'select',
          '#options' => $templates,
          "#default_value" => empty($preset) ? "" : ($preset['results']['output_template'] ?? "") ,
          '#title' => $this->t("Select results template"),
        ],
        'result_count' => [
          '#type' => 'select',
          '#options' => [
            0 => "All",
            1 => "1",
            3 => "3",
            5 => "5",
            10 => "10",
            15 => "15",
            20 => "20",
          ],
          "#default_value" => empty($preset) ? 0 : ($preset['results']['result_count'] ?? 0) ,
          '#title' => $this->t("How many results should be returned?"),
        ],
        'references' => [
          '#type' => 'checkbox',
          "#default_value" => empty($preset) ? 0 : ($preset['results']['references'] ?? 0),
          '#title' => $this->t("Show references in results output (if available)."),
        ],
        'citations' => [
          '#type' => 'checkbox',
          "#default_value" => empty($preset) ? 0 : ($preset['results']['citations'] ?? 0),
          '#title' => $this->t("Show citations in results output (if available)."),
        ],
        'metadata' => [
          '#type' => 'checkbox',
          "#default_value" => empty($preset) ? 0 : ($preset['results']['metadata']  ?? 0),
          '#title' => $this->t("Show AI Model metadata in results output (if available)."),
        ],
        'pid' => [
          '#type' => 'hidden',
          "#default_value" => $pid,
          "#value" => $pid,
        ],
      ],
      'actions' => [
        '#type' => "button",
        '#value' => "Delete Preset",
        '#attributes' => [
          "class" => [
            "button--danger"
          ],
          "data-pid" => "$pid",
        ],
        '#ajax' => [
          'callback' => '::ajaxDeletePreset',
          'event' => 'click',
          'wrapper' => 'edit-presets',
          'disable-refocus' => FALSE,
          'limit' => FALSE
        ]
      ],
    ];

    if (!isset($pid) || $pid == "") {
      // Configure for a new Preset.
      unset($output["actions"]);
      foreach($output as &$row) {
        if (is_array($row) && $row["#type"] == "textarea") {
          $row["#value"] = "";
        }
      }

    }

    return $output;

  }

}
