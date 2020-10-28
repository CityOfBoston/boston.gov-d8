<?php

namespace Drupal\bos_email\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Site\Settings;

/**
 * Postmark variables for email API.
 */
class PostmarkVars extends ControllerBase {

  /**
   * Get vars for Postmark servers.
   */
  public function varsPostmark() {

    if (isset($_ENV['POSTMARK_SETTINGS'])) {
      $postmark_env = [];
      $get_vars = explode(",", $_ENV['POSTMARK_SETTINGS']);
      foreach ($get_vars as $item) {
        $json = explode(":", $item);
        $postmark_env[$json[0]] = $json[1];
      }
    }
    else {
      $postmark_env = [
        "registry_token" => Settings::get('postmark_settings')['registry_token'],
        "contactform_token" => Settings::get('postmark_settings')['contactform_token'],
        "commissions_token" => Settings::get('postmark_settings')['commissions_token'],
        "auth" => Settings::get('postmark_settings')['auth'],
      ];
    }

    return $postmark_env;
  }

}

// End PostmarkVars class.
