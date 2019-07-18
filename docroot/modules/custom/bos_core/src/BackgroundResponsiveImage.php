<?php

namespace Drupal\bos_core;

use Drupal\Core\Render\Markup;
use Drupal\image\Entity\ImageStyle;
use Drupal\responsive_image\Entity\ResponsiveImageStyle;

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
   * @param array $background_image
   *   Background image render array.
   * @param string $anchorClass
   *   The class name for css generated - the background image main class tag.
   *
   * @return string|bool
   *   False if failed, otherwise an inline css string that could be injected.
   *
   * @throws \Exception
   */
  public static function createBackgroundCss(array $background_image, $anchorClass = "hro") {

    if ($background_image['#formatter'] != 'responsive_image') {
      throw new \Exception("Image is not a responsive style.");
    }

    $responsiveStyle_group = $background_image[0]["#responsive_image_style_id"];

    // Grab the fielditemlist object.
    $background_image = $background_image["#items"];

    if (!empty($background_image->entity)) {

      $uri = $background_image->entity->getFileUri();

      return self::buildMediaQueries($uri, $responsiveStyle_group, $anchorClass);
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

  /**
   * Builds the media queries (css) as specified by responsive style module.
   *
   * @param string $uri
   *   The image Uri.
   * @param string $responsiveStyle_group
   *   The responsive group to use.
   * @param string $anchorClass
   *   The css anchor element to use.
   *
   * @return string
   *   A string of valid css3.
   */
  public static function buildMediaQueries(string $uri, string $responsiveStyle_group, string $anchorClass) {
    // Work out the responsive group id and the breakpoint set being used.
    $responsiveStyle = ResponsiveImageStyle::load($responsiveStyle_group);
    $breakpoint_group = $responsiveStyle->get("breakpoint_group");

    // Get the breakpoints for specified group (defined in theme.info).
    $breakpoints = \Drupal::service('breakpoint.manager')
      ->getBreakpointsByGroup($breakpoint_group);

    // Create the default style.
    $fallback_style = $responsiveStyle->getFallbackImageStyle();
    $url = ImageStyle::load($fallback_style)->buildUrl($uri);
    $css = ["$anchorClass { background-image: url(" . $url . ");\n    background-size: cover !important;}"];

    // Create an array with URL's for each responsive style.
    $styles = $responsiveStyle->getKeyedImageStyleMappings();
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

}
