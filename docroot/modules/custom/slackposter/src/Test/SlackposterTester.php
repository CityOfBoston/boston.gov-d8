<?php

namespace Drupal\slackposter\Test;

use Drupal\Core\Url;
use Drupal\slackposter\Integrate\SlackPost;
use Drupal\slackposter\Integrate\SlackRestResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Testing for slackposter.
 */
class SlackposterTester {

  /**
   * Creates a test post to slack.
   *
   * @inheritDoc.
   */
  public static function main($what) {

    $out = '';

    $config = \Drupal::config("slackposter.settings");
    if (empty($config->get("rest.enabled"))) {
      \Drupal::messenger()->addMessage("REST not enabled for slackposter.");
      return new RedirectResponse(Url::fromRoute('<front>')->toString());
    }

    switch ($what) {
      case 'a':
        break;

      default:
        $slack = new SlackPost();
        $out = $slack->post('posting test', '#integrationtest');
        break;
    }
    \Drupal::messenger()->addMessage("Test Post Completed.");
    return new RedirectResponse(Url::fromRoute('<front>')->toString());
  }

  /**
   * Creates a test post to slack.
   *
   * @inheritDoc.
   */
  public static function openmain($what) {

    $out = '';
    global $base_url;

    $config = \Drupal::config("slackposter.settings");
    if (empty($config->get("rest.enabled"))) {
      \Drupal::messenger()->addMessage("REST not enabled for slackposter.");
      return new RedirectResponse(Url::fromRoute('<front>')->toString());
    }

    switch ($what) {
      case "":
      default:
        $slack = new SlackPost();
        $out = $slack->post('posting test', '#integrationtest');
        break;
    }
    \Drupal::messenger()->addMessage("Test Post Completed.");
    return new RedirectResponse(Url::fromRoute('<front>')->toString());
  }

}
