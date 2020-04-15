<?php

namespace Drupal\bos_metrolist\Plugin\views\style;

use Drupal\Core\Annotation\Translation;
use Drupal\views\Annotation\ViewsStyle;
use Drupal\views\Plugin\views\style\StylePluginBase;

/**
 * Unformatted style plugin to render rows one after another with no
 * decorations.
 *
 * @ingroup views_style_plugins
 *
 * @ViewsStyle(
 *   id = "metro_list_drawers_style",
 *   title = @Translation("MetroList Drawers"),
 *   help = @Translation("Displays rows one after another in Drawers."),
 *   theme = "views_view_metrolist_drawers",
 *   display_types = {"normal"}
 * )
 */
class MetroListDrawersStyle extends StylePluginBase {

  /**
   * {@inheritdoc}
   */
  protected $usesRowPlugin = TRUE;

  /**
   * Does the style plugin support custom css class for the rows.
   *
   * @var bool
   */
  protected $usesRowClass = TRUE;

}
