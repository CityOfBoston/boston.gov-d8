<?php
/**
 * @file includes/search_form_theme
 */
 /**
 * Contains hooks and custom functions to allow theming of the main (modal)
 * search form.
 *
 * @see ../bos_search.module
 *
 */
use Drupal\bos_search\AiSearch;
use Drupal\Core\Form\FormStateInterface;

function _bos_search_autodiscover_theme(array &$existing):void  {

  // Auto-discover main theme templates:
  $themes = AiSearch::getFormThemes();
  foreach($themes as $theme => $name) {

    $templates = AiSearch::getFormTemplates($theme);
    foreach($templates as $template => $template_name) {
      $idx_file = str_replace(["_", " "], "-", $template);
      $idx = str_replace(["-", " "], "_", $template);
      if ($idx == "results") {
        $existing["{$idx}__{$theme}"] = [
          'template' => "presets/$theme/$idx_file",
          'variables' => [
            "response" => NULL,
            "items" => NULL,
            "metadata" => NULL,
            "references" => NULL,
            "citations" => NULL,
            "content" => NULL,
            "id" => NULL,
            "feedback" => NULL,
          ],
        ];
      }
      else {
        $existing["{$idx}__{$theme}"] = [
          'template' => "presets/$theme/$idx_file",
          'base_hook' => $idx,
          'render element' => 'children',
        ];
      }
    }

  }

}

function _bos_search_snippet_theme(array &$existing):void  {
  $existing['modal_close'] = [
    'template' => 'snippets/modal-close',
    'variables' => [
    ],
  ];
  $existing["aisearch_feedback"] = [
    'template' => 'snippets/aisearch-feedback',
    'variables' => [
      'thumbsup' => FALSE,
      'thumbsdown' => FALSE,
    ],
  ];
}

/**
 * Implements hook_preprocess().
 */
function bos_search_preprocess(&$variables, $hook, $info) {

  if (!AiSearch::isBosSearchThemed()) {
    return;
  }

  switch ($hook) {

    case "block":
      template_preprocess_block($variables);;
      break;

    case "form":
      if ($variables["element"]["#form_id"] == "bos_search_AISearchForm") {
        template_preprocess_form($variables);

        // Add some extra configuration information
        if ($variables["configuration"] = \Drupal::request()->query->all()) {
          $variables["preset"] = AiSearch::getPresetValues($variables["configuration"]["preset"] ?? 'default') ?? [];

          // If required, add in the modal close header .
          if (($variables["configuration"]["display"] ?? "block") == "modal") {
            $variables["form_header"] = [
              '#type' => 'modal_close',
              '#theme' => 'modal_close',
            ];
          }

          // Include any custom styles and scripts.
          $custom_theme_path = "/modules/custom/bos_components/modules/bos_search/templates/presets/{$variables['preset']['searchform']['theme']}";
          $variables["#attached"]["drupalSettings"]["bos_search"] = [
            'dynamic_script' => "$custom_theme_path/js/preset.js",
            'dynamic_style' => "$custom_theme_path/css/preset.css",
          ];
          // Include script to load custom scripts and styles.
          $variables['#attached']['library'][] = 'bos_search/dynamic-loader';
        }
      }
      break;

    case "fieldset":
      template_preprocess_fieldset($variables);
//        _bos_search_preprocess_fieldset($variables, $hook);
      break;


    case "container":
      template_preprocess_container($variables);
//        _bos_search_preprocess_container($variables, $hook);
      break;

    case "input":
//        template_preprocess_input($variables);
      _bos_search_preprocess_input($variables, $hook);
      break;

    case "textarea":
      template_preprocess_textarea($variables);
      $variables["attributes"]["class"][] = "txt-f";
//        _bos_search_preprocess_textarea($variables, $hook);
      break;

    case "form_element":
      template_preprocess_form_element($variables);
//        _bos_search_preprocess_form_element($variables, $hook);
      break;

    case "form_element_label":
      template_preprocess_form_element_label($variables);
//        _bos_search_preprocess_form_element_label($variables, $hook);
      break;

    default:
      break;
  }
}

function _bos_search_preprocess_input(&$variables, $hook):void  {

  template_preprocess_input($variables);
  $variables["attributes"] += $variables["element"]["#attributes"];
  $variables["children"] = $variables['element'];

}

/**
 * Implements hook_theme_suggestions_alter().
 */
function _search_form_suggestions(array &$suggestions, array &$variables, $hook):void  {

  if (!empty($variables["element"])) {
    // Get the form theme being used by the active preset, or else use 'default'.
    $preset = AiSearch::getPreset();
    $form_theme = AiSearch::getPresetValues($preset)["searchform"]["theme"];

    switch ($hook) {
      case "form":
        if (isset($variables["element"]["#errors"])) {
          return;
        }
        if ($variables["element"]["#form_id"] == 'bos_search_AISearchForm') {
          $suggestions[] = "form__$form_theme";
        }
        break;
      case "form_element":
      case "form_element_label":
        if (in_array("AiSearchForm", $variables["element"]["#array_parents"] ?? [])) {
          $suggestions[] = "{$hook}__$form_theme";
        }
        break;
      default:
        if (in_array("AiSearchForm", $variables["element"]["#array_parents"] ?? [])) {
          if (in_array($variables["element"]["#type"], [
            "hidden",
            "button",
            "submit",
          ])) {
            $suggestions[] = "input__$form_theme";
          }
          else {
            $suggestions[] = $variables["element"]["#type"] . "__$form_theme";
          }
        }
        break;
    }
  }

  // Adds suggestions to allow themeing the AI Search Blocks.
  // The block will use a template based on the preset being used.
  if ($hook == "block") {

    if ($variables["elements"]["#plugin_id"] == "Ai-enabled-search-button") {
      // Get the theme from the preset - or use 'default'
      if ($preset = $variables["elements"]["#configuration"]["aisearch_config_preset"]  ?? FALSE) {
        $form_theme = AiSearch::getPresetValues($preset)["searchform"]["theme"] ?? $form_theme;
      }
      $suggestions[] = "block__button__{$form_theme}";
    }
    elseif ($variables["elements"]["#plugin_id"] == "Ai-enabled-search-form") {
      $preset = AiSearch::getPreset();
      $form_theme = AiSearch::getPresetValues($preset)["searchform"]["theme"];
      $suggestions[] = "block__form__{$form_theme}";
    }
  }

}

/*--dead */

function _bos_search_preprocess_fieldset(&$variables, $hook):void  {

  $variables["legend"] = [
    "title" => $variables['element']["#title"] ?? "",
    "attributes" => $variables['element']["#title"]["attributes"] ?? [],
  ];
  $variables["legend_span"]["attributes"] = $variables['element']["#title_wrapper"]["attributes"] ?? [];

  $variables["description"] = [
    "content" => (string) $variables['element']["#description"] ?? "",
    "attributes" => $variables['element']["#description_attributes"] ?? [],
  ];
  $variables["prefix"] = $variables["element"]["#prefix"] ?? NULL;
  $variables["suffix"] = $variables["element"]["#suffix"] ?? NULL;

}

function _bos_search_preprocess_container(&$variables, $hook):void  {

  if (isset($element['#array_parents'])) {
    $variables['has_parent'] = TRUE;
  }
  $variables["attributes"] = $variables['element']["#attributes"];
  $variables["children"] = $variables['element'];

}

function _bos_search_preprocess_textarea(&$variables, $hook):void  {
  $variables["attributes"] = $variables["element"]["#attributes"];
  if (isset($variables["element"]["#rows"])) {
    $variables["attributes"]["rows"] = $variables["element"]["#rows"];
  }
  if (isset($variables["element"]["#cols"])) {
    $variables["attributes"]["cols"] = $variables["element"]["#cols"];
  }
  $variables["attributes"]["id"] = $variables["element"]["#id"];
  $variables["attributes"]["name"] = $variables["element"]["#name"];
  // This class overwrites the cols value.

  $variables["wrapper_attributes"] = $variables["element"]["#wrapper_attributes"] ?? [];
  $variables["resizable"] = $variables["element"]["#resizable"];
  $variables["required"] = $variables["element"]["#required"];
  $variables["value"] = $variables["element"]["#value"];
  $variables["children"] = $variables['element'];
}

function _bos_search_preprocess_form_element(&$variables, $hook):void  {
  $variables["attributes"] = $variables["element"]["#attributes"];
  $variables["type"] = $variables["element"]["#type"];
  $variables["name"] = $variables["element"]["#name"] ?? "";
  if(!empty( $variables["element"]["#title"])) {
    $variables["label"] = [
      '#theme' => 'form_element_label',
      '#title' => $variables["element"]["#title"],
      '#required' => FALSE,
      '#title_display' => $variables["element"]["#title_display"] ?? "",
      '#id' => $variables["element"]["#id"],
      '#attributes' => $variables["element"]["#title_attributes"] ?? [],
      "#array_parents" => $variables["element"]["#array_parents"],
      "#atype" => $variables["element"]["#type"],
    ];
    $variables["label_display"] = $variables["element"]["#title_display"];
    $variables["title_display"] = $variables["element"]["#title_display"];
  }
  $variables["disabled"] = $variables["element"]["#disabled"] ?? FALSE;
  $variables["required"] = $variables["element"]["#required"];
  $variables["description"] = [
    "content" => (string) ($variables['element']["#description"] ?? ""),
    "attributes" => $variables['element']["#description_attributes"] ?? [],
  ];
  $variables["description_display"] = $variables["element"]["#description_display"] ?? "before";
  $variables["prefix"] = $variables["element"]["#prefix"] ?? NULL;
  $variables["suffix"] = $variables["element"]["#suffix"] ?? NULL;
  $variables["children"] = $variables['element'];
}

function _bos_search_preprocess_form_element_label(&$variables, $hook):void  {
  $variables["attributes"] = $variables["element"]["#attributes"];
  $variables["title"] = (string) ($variables["element"]["#title"] ?: "");
  $variables["title_display"] = (string) ($variables["element"]["#title_display"] ?: "before");
  $variables["required"] = $variables["element"]["#required"];
  $variables["type"] = $variables["element"]["#atype"];
  $variables["attributes"]["for"] = match($variables["element"]["#atype"]) {
    "textarea" => "text",
  };
  $variables["children"] = $variables['element'];
}
