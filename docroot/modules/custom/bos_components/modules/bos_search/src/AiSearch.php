<?php

namespace Drupal\bos_search;

use Drupal\Core\Form\FormStateInterface;

class AiSearch {

  public static function getPreset(array $form = [], ?FormStateInterface $form_state = NULL):string {

    // If the form State has a value for preset, then return it.
    if ($form_state && $form_state->hasValue("preset") ?: FALSE) {
      return $form_state->getValue("preset");
    }

    // Get the preset from the request object.
    $request = \Drupal::request();
    if ($request->query->has('preset')) {
      return $request->query->get('preset');
    }

    // Return the first preset as a default.
    return array_key_first(self::getPresets());

  }

  /**
   * Fetch the preset (set on Search Config Form)
   *
   * @param string $preset_name
   *
   * @return array
   */
  public static function getPresetValues(string $preset_name = ""): array {
    if ($preset_name == "") {
      $preset_name = self::getPreset();
    }
    $config = \Drupal::config("bos_search.settings")->get("presets");
    if (empty($preset_name)) {
      return [];
    }
    else {
      return $config[$preset_name] ?? [];
    }
  }

  /**
   * Get an Assoc Array with all presets listed.
   * This format is suitable for options in select form objects.
   *
   * @return array
   */
  public static function getPresets(): array {
    $config = \Drupal::config("bos_search.settings")->get("presets") ?? [];
    $output = [];
    foreach ($config as $cid => $preset) {
      $output[$cid] = $preset["name"];
    }
    return $output;
  }

  /**
   * Creates a new string from a string.
   * The new string can be used as a valid drupal machine id.
   *
   * @param string $name
   *
   * @return string
   */
  public static function machineName(string $name):string {
    return strtolower(preg_replace('/[^a-zA-Z0-9_]+/', '_', $name));;
  }

  /**
   * Cleans up a string.
   *
   * @param $string string the string to be cleaned
   *
   * @return string the cleaned string
   */
  public static function sanitize(string $string): string {
    // TODO: Do we want to add profanity filters or other forms of sanitation here?
    return (trim($string));
  }

  /**
   * Scans the Templates folder and gets a list of implemented themes (subfolders)
   * for the main search form.
   *
   * @return array
   */
  public static function getFormThemes(): array {
    $folders = glob(\Drupal::service("extension.list.module")->getPath('bos_search') . "/templates/presets/*", GLOB_ONLYDIR);
    $themes = [];
    foreach($folders as $folder) {
      $folder = basename($folder);
      $themes[$folder] = ucwords(str_replace(["_", "-"], " ", $folder));
    }
    return $themes;
  }

  /**
   * Scans the provided folder's 'presets' subfolder and gets a list of
   * implemented templates to be used for the overall search theme for the
   * main search form.
   *
   * The array has an index with the filename stripped of "html.twig" extension
   * with "-" replacing underscores in the filename.
   * The array values are a generated human-readable name for the filename by
   * replacing all underscores spaces.
   *
   * @param string $theme The folder to scan
   *
   * @return array an assoc array of templates.
   */
  public static function getFormTemplates(string $theme): array {
    $files = glob(\Drupal::service("extension.list.module")->getPath('bos_search') . "/templates/presets/{$theme}/*.html.twig");
    $templates = [];
    foreach($files as $file) {
      $twig = basename($file);
      $template = str_replace(".html.twig", "", $twig);
      $templates[$template] = ucwords(str_replace(["_", "-"], " ", $template));
    }
    return $templates;
  }

  /**
   * Scans the templates search_results subfolder and gets a list of implemented
   * templates for the search results section of the main search form.
   *
   * The array has an index with the filename stripped of "html.twig" extension
   * with "-" replacing underscores in the filename.
   * The array values are a generated human-readable name for the filename by
   * replacing all underscores spaces.
   *
   * @return array An assoc array of templates
   *
   */
  public static function getFormResultTemplates(): array {
    // Deprecated
    return [];
  }

  public static function isBosSearchThemed(): bool {

    // Is this the disclaimer form?
    if (\Drupal::request()->attributes->get("_route") == "bos_search.open_DisclaimerForm") {
      return TRUE;
    }

    // Is this the AISearch form?
    if (\Drupal::request()->attributes->get("_route") == "bos_search.open_AISearchForm") {
      return TRUE;
    }

    // Is this the AISearch Config form?
    if (\Drupal::request()->attributes->get("_route") == "bos_search.AiSearchConfigForm") {
      return TRUE;
    }

    // If this is a node, check if the node has a block displayed within it.
    if (!empty(\Drupal::request()->attributes->get("node"))) {
      // Find out if a block which implements any of the aisearch blocks is
      // attached to this node, and visible to this user.
      $blocks = \Drupal::entityTypeManager()->getStorage('block')->getQuery()
        ->accessCheck(TRUE);
      $or_group = $blocks->orConditionGroup();
      $or_group = $or_group->condition('id', 'aienabledsearchbutton', 'CONTAINS');
      $or_group = $or_group->condition('id', 'aienabledsearchform', 'CONTAINS');
      $blocks->condition($or_group);
      $blocks = $blocks->execute();
      foreach($blocks as $blockname) {
        $block = \Drupal::entityTypeManager()
          ->getStorage('block')
          ->load($blockname);
        foreach ($block->getVisibilityConditions() as $condition) {
          if ($condition->evaluate()) {
            // Soon as you find a matching condition return TRUE.
            return TRUE;
          }
        }
      }
    }

    // Don't appear to need the bos_search theme, return false.
    return FALSE;
  }


  /**
   * Sets a custom session cookie.
   *
   * @param string $key
   *   The key used to store the value in the session.
   * @param string|bool|array $value
   *   The value to store in the session, which can be a string, boolean, or array. Defaults to TRUE.
   *    NOTE: Bool values are coerced into an integer (0=false, 1=true)
   *
   * @return void
   *   Does not return any value.
   */
  public static function setSessionCookie(string $key, string|bool|array $value = TRUE):void {
    // Set a custom session cookie.
    if (session_status() == PHP_SESSION_NONE) {
      session_start();
    }
    if (is_array($value)) {
      $value = serialize($value);
    }
    $_SESSION[$key] = base64_encode($value);
  }

  /**
   * Retrieves a custom session cookie.
   *
   * @param string $key
   *   The key of the session cookie to retrieve.
   *
   * @return string|array
   *   The decoded session cookie (bools converted to int), or FALSE if not set.
   */
  public static function getSessionCookie(string $key): string|array {
    // Set a custom session cookie.
    if (session_status() == PHP_SESSION_NONE) {
      session_start();
    }
    if (empty($_SESSION['shown_search_disclaimer'])) {
      return FALSE;
    }
    //    return FALSE;
    return base64_decode($_SESSION['shown_search_disclaimer']);
  }
}
