<?php
/**
 * @file includes/bos_theme_theme.inc
 */
/**
 * Contains theme definitions that should be part of the main bos_theme theme.
 *
 * @see ../bos_search.module
 * @see themes/custom/bos_theme/bos_theme.theme
 *
 */

/**
 * Adds in defintions for render components which should be included in the
 * bos_theme.theme file.
 *
 * @param array $existing Already defined theme definitions.
 *
 * @return void
 */
function _load_component_definitions(array &$existing) {
  $existing['quote_card'] = [
    'template' => 'components/quote-card',
    'variables' => [
      "content" => "",
      "person" => "",
      "show_quotes" => "",
      "picture" => "",
      "location" => "",
      "attributes" => [],
    ],
  ];
  $existing['grid_of_quote_cards'] = [
    'template' => 'components/grid-of-quote-cards',
    'variables' => [
      "attributes" => [],
      "title" => "",
      "title_attributes" => [],
      "cards" => []
    ],
    "render element" => "cards",
  ];
  $existing['card'] = [
    'template' => 'components/card',
    'variables' => [
      "attributes" => [],
      "link" => "",
      "image" => "",
      "title" => "",
      "title_attributes" => [],
      "subtitle" => "",
      "subtitle_attributes" => [],
      "content" => "",
      "content_attributes" => [],
    ],
  ];
  $existing['grid_of_cards'] = [
    'template' => 'components/grid-of-cards',
    'variables' => [
      "attributes" => [],
      "title" => "",
      "title_attributes" => [],
      "cards" => [],
    ],
    "render element" => "cards",
  ];
}

/**
 * Implements hook_preprocess_HOOK().
 */
function bos_search_preprocess_grid_of_cards(&$variables) {

  // Give the grid of cardas a unique ID, this is useful for anchoring.
  $variables["attributes"]["id"] = $variables["attributes"]["data-drupal-selector"] ?? "grid_" . rand(100000,999999);

  // Give each card a unique ID within this grid.
  foreach($variables['cards'] as $key => &$card) {
    $card["#attributes"]["id"] =  "{$variables["attributes"]["id"]}_card_{$key}";
    $card["#attributes"]["class"][] = "card_{$key}";
  }
}
