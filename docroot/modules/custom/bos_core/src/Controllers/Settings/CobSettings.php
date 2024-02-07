<?php

namespace Drupal\bos_core\Controllers\Settings;

/**
 * Reads settings from an environment variable or the database config as needed.
 */
class CobSettings {

  /**
   * Return an array of settings.
   * Fetch settings from the Drupal configuration system (if any) but replace
   * with any values found from the indicated an environment variable.
   *
   * @return array
   */
  public static function getSettings(string $envar_name, string $module_name, string $config_root = "", array $envar_list = []):array {

    // The connection details should be stored in an environment variable
    // on the Acquia environment.
    // However, for local-dev, the connection details may be in the site config
    // because it's easier to use config locally than envars.
    //
    // We are expecting one or both of:
    //    - an encoded string in the envar which can be decoded into an array,
    //    - and/or an array from the Drupal config object (i.e. in DB).
    //
    // The config field contains an array of values which were found (and
    // therefore used) from the environment variable.
    //
    // SECURITY BEST PRACTICE:
    // Data stored in environment variables on the server are generally
    // considered to be more secure than data stored in the Drupal configuration
    // system.
    // However, for ease of management, the envar really only needs to contain
    // secrets. Additional config information can be handled by the Drupal
    // configuration system and has the benefit of being managed via the Drupal
    // GUI.

    $config = [];

    if (getenv($envar_name)) {

      $config = self::envar_decode(getenv($envar_name));

      if (!empty($envar_list)) {
        // Only keep envar settings permitted by envar_list
        $config = array_intersect_key($config, array_flip($envar_list));
      }

      $config["config"] = array_keys($config); // list of fields found from envar

    }

    $settings = \Drupal::config("{$module_name}.settings")->get($config_root) ?? [];

    if ($settings) {
      // merge envar list and array list, envar overwriting existing DB settings.
      $config = array_merge($settings, $config);
    }

    return $config;
  }

  public static function envar_decode(string $envar): array {
    // Unfortunately, Acquia does not allow quotes in the ENVAR, so we cannot
    // save a json string as an envar.
    // So we will use a string which separates keyvalues pairs by commas and
    // key-values by semicolons.  The values are base64 encoded, so we don't have
    // issues with embedded commans, colons, quotes etc.
    $config = [];
    foreach(explode(",", $envar) as $var) {
      $keyval = explode(":", $var, 2);
      $config[$keyval[0]] = base64_decode($keyval[1]);
    }
    return $config;

  }

  /**
   * Encode an array into an Acquia-safe string to place in an envar.
   * Optionally will only encode settings found in the $envar_list.
   *
   * @param array $settings The settings
   * @param array $envar_list [optional] a list of settings to encode.
   *
   * @return string a string which can be used as an evar in Acquia.
   */
  public static function envar_encode(array $settings, array $envar_list = []):string {

    // Unfortunately, Acquia does not allow quotes in the ENVAR, so we cannot
    // simply save a json string as an envar.
    // So we will create a string which separates keyvalues pairs by commas and
    // key-values by semicolons.  The values are base64 encoded, so we don't have
    // issues with embedded commans, colons, quotes etc.

    $string = "";

    if (!empty($envar_list)) {
      // only keep settings that are in the envar list.
      $settings = array_intersect_key($settings, array_flip($envar_list));
    }

    foreach($settings as $key => $value) {
      if (!empty($key)) {
        $value = base64_encode($value);
        $string = "${string},{$key}:{$value}";
      }
    }

    return trim($string, "\n\r\t\v\0,");

  }

}
