<?php

namespace Drupal\bos_core;

use Drupal\Core\Render\Markup;
use Drupal\image\Entity\ImageStyle;
use Drupal\responsive_image\Entity\ResponsiveImageStyle;
use Drupal\file\Plugin\Field\FieldType\FileFieldItemList;

/**
 * Class BackgroundResponsiveImage.
 *
 * @package Drupal\bos_core
 */
class BackgroundResponsiveImage extends ResponsiveImageStyle {

  /**
   * Creates inline css to make a background image responsive (uses css @media).
   *
   * Needs to be called from a theme_preprocess_hook or else the ['#attached']
   * array field does not set and the style is not applied to the page.
   *
   * @param \Drupal\file\Plugin\Field\FieldType\FileFieldItemList $background_image
   *   Usual $variables variable from preprocess function.
   * @param string $breakpoint_group
   *   The breakpoint group to use (defined in the theme.ino.yml file).
   * @param string $responsiveStyle_group
   *   Style-set to be applied (/admin/config/media/responsive-image-style).
   * @param string $anchorClass
   *   The class name for css generated - the background image main class tag.
   *
   * @return string|bool
   *   False if failed, otherwise an inline css string that could be injected.
   */
  public static function createBackgroundCss(FileFieldItemList $background_image, string $breakpoint_group, string $responsiveStyle_group, $anchorClass = "hro") {

    if (!empty($background_image->entity)) {

      $uri = $background_image->entity->getFileUri();

      // Get the breakpoints for specified group (defined in theme.info).
      $breakpoints = \Drupal::service('breakpoint.manager')
        ->getBreakpointsByGroup($breakpoint_group);

      // Create the default style.
      $fallback_style = ResponsiveImageStyle::load($responsiveStyle_group)
        ->getFallbackImageStyle();
      $url = ImageStyle::load($fallback_style)->buildUrl($uri);
      $css = ["$anchorClass { background-image: url(" . $url . ");\n    background-size: cover !important;}"];

      // Create an array with URL's for each responsive style.
      $styles = ResponsiveImageStyle::load($responsiveStyle_group)
        ->getKeyedImageStyleMappings();
      foreach ($styles as $key => $style) {
        foreach ($style as $multiplier => $breakpoint) {
          if (empty($breakpoint['multiplier']) || $breakpoint['multiplier'] == "1x") {
            $multiplier = "";
          }
          else {
            $multiplier = str_replace('x', '', $breakpoint['multiplier']);
            $multiplier = "and (-webkit-min-device-pixel-ratio: $multiplier) ";
          }
          $url = ImageStyle::load($breakpoint['image_mapping'])
            ->buildUrl($uri);
          $breakpoint = $breakpoints[$breakpoint['breakpoint_id']]->getMediaQuery();
          $css[] = "@media screen and $breakpoint $multiplier{\n    $anchorClass { background-image: url($url); } }";
        }
      }
      return implode("\n", $css);
    }
    return FALSE;
  }

  /**
   * Renders the HTML framework to implement a responsive background image.
   *
   * @param array $nestedFramework
   *   Structured array of nested HTML elements.
   *   First element encloses second etc.
   *
   * @throws \Exception
   *
   * @return \Drupal\Core\Render\Markup
   *   Markup to be applied to a drupal render array.
   */
  public static function createBackgroundFramework(array $nestedFramework) {
    if (!is_array($nestedFramework)) {
      throw new \Exception("Require array input to function.");
    }
    foreach ($nestedFramework as $key => $classDef) {
      $ky = key($classDef);
      $class = $classDef[$ky];
      $html[] = "<$ky class=\"$class\">";
      $htmlClose[] = "</$ky>";
    }
    $html = implode("", $html) . implode("", $htmlClose);
    return Markup::create($html);
  }

}
