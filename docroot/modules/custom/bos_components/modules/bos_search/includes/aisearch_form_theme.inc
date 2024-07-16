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

function _autodiscover_form_templates(array &$existing) {

  // Auto-discover main theme templates:
  $themes = AiSearch::getFormThemes();
  foreach($themes as $theme => $name) {

    $templates = AiSearch::getFormTemplates($theme);
    foreach($templates as $template => $template_name) {
      $idx_file = str_replace(["_", " "], "-", $template);
      $idx = str_replace(["-", " "], "_", $template);
      $existing["{$idx}__{$theme}"] = [
        'template' => "form_elements/$theme/$idx_file",
        'base_hook' => $idx,
        'render element' => 'children',
      ];
    }

  }

  // Auto-discover search results templates:
  $templates = AiSearch::getFormResultTemplates();
  foreach ($templates as $template => $template_name) {
    $idx_file = str_replace(["_", " "], "-", $template);
    $idx = str_replace(["-", " "], "_", $template);
    $existing[$idx] = [
      'template' => "search_results/$idx_file",
      'variables' => [
        "response" => NULL,
        "items" => NULL,
        "metadata" => NULL,
        "references" => NULL,
        "citations" => NULL,
        "content" => NULL,
        "id" => NULL,
      ],
    ];
  }

}

function bos_search_preprocess_form__boston(&$variables, $hook) {

  $variables["children"] = $variables['element'];
  $variables["attributes"] = [
    "data-drupal-selector" => $variables["element"]['#id'],
    "novalidate" => 'novalidate',
    "action" => $variables["element"]['#action'],
    "method" => $variables["element"]['#method'],
    "id" => $variables["element"]['#id'],
    "accept-charset" => 'UTF-8',
  ];

}

function bos_search_preprocess_fieldset__boston(&$variables, $hook) {

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

function bos_search_preprocess_container__boston(&$variables, $hook) {

  if (isset($element['#array_parents'])) {
    $variables['has_parent'] = TRUE;
  }
  $variables["attributes"] = $variables['element']["#attributes"];
  $variables["children"] = $variables['element'];

}

function bos_search_preprocess_input__boston(&$variables, $hook) {

  $variables["attributes"] = $variables["element"]["#attributes"];
  $variables["children"] = $variables['element'];

}

function bos_search_preprocess_textarea__boston(&$variables, $hook) {
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
  $variables["attributes"]["class"][] = "txt-f";

  $variables["wrapper_attributes"] = $variables["element"]["#wrapper_attributes"] ?? [];
  $variables["resizable"] = $variables["element"]["#resizable"];
  $variables["required"] = $variables["element"]["#required"];
  $variables["value"] = $variables["element"]["#value"];
  $variables["children"] = $variables['element'];
}

function bos_search_preprocess_form_element__boston(&$variables, $hook) {
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

function bos_search_preprocess_form_element_label__boston(&$variables, $hook) {
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

/**
 * Implements hook_theme_suggestions_alter().
 */
function _search_form_suggestions(array &$suggestions, array &$variables, $hook) {

  $form_theme = "boston";

  if (!empty($variables["element"])) {
    switch ($hook) {
      case "form":
        if ($variables["element"]["#form_id"] == 'bos_search_AISearchForm') {
          if ($preset = \Drupal::request()->get("preset")  ?: FALSE) {
            $form_theme = AiSearch::getPreset($preset)["modalform"]["theme"] ?: $form_theme;
          }
          $suggestions[] = "form__$form_theme";
        }
        break;

      case "form_element":
      case "form_element_label":
        if (in_array("AiSearchForm", $variables["element"]["#array_parents"] ?? [])) {
          if ($preset = \Drupal::request()->get("preset")  ?: FALSE) {
            $form_theme = AiSearch::getPreset($preset)["modalform"]["theme"] ?: $form_theme;
          }
          $suggestions[] = "{$hook}__$form_theme";
        }
        break;

      default:
        if (in_array("AiSearchForm", $variables["element"]["#array_parents"] ?? [])) {

          if ($preset = \Drupal::request()->get("preset")  ?: FALSE) {
            $form_theme = AiSearch::getPreset($preset)["modalform"]["theme"] ?: $form_theme;
          }

          if (in_array($variables["element"]["#type"], ["hidden", "button", "submit"])) {
            $suggestions[] = "input__$form_theme";
          }
          else {
            $suggestions[] = $variables["element"]["#type"] . "__$form_theme";
          }

        }
        break;
    }
  }

}
