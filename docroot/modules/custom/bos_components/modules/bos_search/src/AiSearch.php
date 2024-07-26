<?php

namespace Drupal\bos_search;

class AiSearch {

  /**
   * Fetch the preset (set on Search Config Form)
   *
   * @param string $preset_name
   *
   * @return array
   */
  public static function getPreset(string $preset_name): array {
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
    $folders = glob(\Drupal::service("extension.list.module")->getPath('bos_search') . "/templates/form_elements/*", GLOB_ONLYDIR);
    $themes = [];
    foreach($folders as $folder) {
      $folder = basename($folder);
      $themes[$folder] = ucwords(str_replace(["_", "-"], " ", $folder));
    }
    return $themes;
  }

  /**
   * Scans the provided folder's 'form_elements' subfolder and gets a list of
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
    $files = glob(\Drupal::service("extension.list.module")->getPath('bos_search') . "/templates/form_elements/{$theme}/*.html.twig");
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
    $files = glob(\Drupal::service("extension.list.module")->getPath('bos_search') . "/templates/search_results/*.html.twig");
    $templates = [];
    foreach($files as $file) {
      $twig = basename($file);
      $template = str_replace(["-",".html.twig"], ["_",""], $twig);
      $templates[$template] = ucwords(str_replace(["_", "-"], " ", $template));
    }
    return $templates;
  }

}
