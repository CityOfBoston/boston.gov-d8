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

}
