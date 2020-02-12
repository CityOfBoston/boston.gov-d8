<?php

namespace Drupal\slackposter\Controller;

use Drupal\Core\Controller\ControllerBase;

/**
 * Mainly to create a help page.
 */
class SlackController extends ControllerBase {

  /**
   * {@inheritdoc}
   */
  public function adminOverview() {
    $form['admin'] = [
      'title' => [
        '#markup' => '<h2>Slack Admin</h2>',
      ],
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function config($name) {
    $config = parent::config("slackposter.settings");
    if (isset($name)) {
      return $config->get($name);
    }
    return $config->getRawData();
  }

  /**
   * {@inheritdoc}
   */
  public function helpPage() {
    $form = [
      'help_page' => [
        '#tree' => TRUE,
        '#type' => 'fieldset',
        '#title' => "About Slack Poster",
        '#markup' => "<p>The slackposter module provides Slack Integration, allowing:.<ul>
          <li>Logger (syslog / watchdog) reporting to post to Slack</li>
          <li>A REST API to allow external posting to slack channels</li>
          <li>A popup window to collect information to post to slack (e.g. support dialog)</li>
          <li>Server side PHP library (API) to use in code</li>
          <li>Client side JS library (API) to use in code</li>
          </ul></p>",
      ],
    ];
    return $form;
  }

}
