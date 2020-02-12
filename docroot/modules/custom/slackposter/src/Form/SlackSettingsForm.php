<?php

namespace Drupal\slackposter\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Logger\RfcLogLevel;

/**
 * Creates an admin/config form for the slackposter module.
 */
class SlackSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'slackposter_admin_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ["slackposter.settings"];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $config = $this->config('slackposter.settings');

    $form = [
      '#tree' => TRUE,
      '#validate' => ['slackposter_form_slackposter_admin_settings_validate'],
      'slackposter' => [
        '#type' => 'details',
        '#title' => t('General Configuration'),
        '#description' => t('General Configuration'),
        '#open' => TRUE,
        'integration' => [
          '#type' => 'textfield',
          '#title' => t('Incoming Webhook URL'),
          '#default_value' => (strlen($config->get('integration')) > 0) ? $config->get('integration') : "",
          '#description' => t("This is the Webhook URL for the 'Incoming Webhook' config in slack (https://xxxx.slack.com/apps/ABCDE01234-incoming-webhooks).<br/>(It has a default channel that it publishes to if one is not specified.)"),
          '#required' => TRUE,
        ],
        'rest' => [
          '#type' => 'details',
          '#title' => t('REST API Endpoint'),
          '#open' => FALSE,
          'rest.enabled' => [
            '#type' => 'checkbox',
            '#title' => t('Create a REST endpoint and respond to requests.'),
            '#default_value' => (strlen($config->get('rest.enabled')) > 0) ? $config->get('rest.enabled') : 0,
            '#description' => t("Allow posting to Slack via a REST API (webhook) from this site."),
          ],
        ],
        'channels' => [
          '#type' => 'details',
          '#title' => t('Slack Channels'),
          '#description' => t('Confgure the channels that the system will post to.<ul><li>These channels must exist in Slack already.</li><li>Prefix with "#".</li></ul>'),
          '#open' => FALSE,
          'channels.default' => [
            '#type' => 'textfield',
            '#title' => t('Default channel to post to'),
            '#default_value' => (strlen($config->get('channels.default')) > 0) ? $config->get('channels.default') : "test",
            '#description' => t("This channel will be used by default when no other channel is specified (or overriden by a default in another module)<br/>Note this will override the default channel specified by the slack 'incoming webhook' at all times.."),
            '#required' => TRUE,
          ],
        ],
        'watchdog' => [
          '#type' => 'details',
          '#title' => t('Capture syslog entries (watchdog)'),
          '#description' => t('Cross-post syslog entries into a slack channel.'),
          '#open' => FALSE,
          'watchdog.enabled' => [
            '#type' => 'checkbox',
            '#title' => t('Post to Watchdog'),
            '#default_value' => (strlen($config->get('watchdog.enabled')) > 0) ? $config->get('watchdog.enabled') : 0,
            '#description' => t("Enable watchdog (drupal syslog) postings to Slack in real-time."),
          ],
          'watchdog.integration' => [
            '#type' => 'textfield',
            '#title' => t('Incoming Webhook URL (watchdog)'),
            '#default_value' => (!empty($config->get('watchdog.integration'))) ? $config->get('watchdog.integration') : "",
            '#description' => t("This is the Webhook URL for the 'Incoming Webhook' config in slack (https://XXXX.slack.com/apps/ABCDEF01234-incoming-webhooks).<br/>(It has a default channel that it publishes to if one is not specified).<br/>This Webhook can be the same as the default above, or different."),
          ],
          'watchdog.channel' => [
            '#type' => 'textfield',
            '#title' => t('Posting Channel'),
            '#default_value' => !(empty($config->get('watchdog.channel'))) ? $config->get('watchdog.channel') : "test",
            '#description' => t("Which Slack channel to post to."),
          ],
          'watchdog.severity' => [
            '#type' => 'select',
            '#options' => RfcLogLevel::getLevels(),
            '#title' => t('Severity Filter'),
            '#required' => FALSE,
            '#multiple' => TRUE,
            '#default_value' => (!empty($config->get('watchdog.severity'))) ? $config->get('watchdog.severity') : [],
            '#description' => t("Select severity levels to include"),
          ],
          'watchdog.filterOut' => [
            '#type' => 'textfield',
            '#title' => t('Log Type Filter'),
            '#default_value' => (!empty($config->get('watchdog.filterOut'))) ? $config->get('watchdog.filterOut') : "",
            '#description' => t("Provide a list of log types to EXCLUDE from posting to Slack.<br>A commas separated list of log types (e.g. php,debug)"),
          ],
          'watchdog.keywords' => [
            '#type' => 'textfield',
            '#title' => t('Keyword Filter'),
            '#default_value' => (!empty($config->get('watchdog.keywords'))) ? $config->get('watchdog.keywords') : "",
            '#description' => t("Provide a list of keywords in the log body to EXCLUDE from posting to Slack.<br>A commas separated list (e.g. tuesday, test.inc)"),
          ],
        ],
      ],
    ];
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

    $settings = $form_state->getValue('slackposter');

    // Validation.
    $settings['channels']['channels.default'] = '#' . trim($settings['channels']['channels.default'], '#');
    $settings['watchdog']['watchdog.channel'] = '#' . trim($settings['watchdog']['watchdog.channel'], '#');
    if ($settings['watchdog']['watchdog.enabled'] && !$settings['watchdog']['watchdog.channel']) {
      $settings['watchdog']['watchdog.channel'] = $settings['channels']['channels.default'];
    }

    \Drupal::logger('slackposter')->info('Hi');

    $this->config('slackposter.settings')
      ->set('integration', $settings['integration'])
      ->set('channels.default', $settings['channels']['channels.default'])
      ->set('rest.enabled', $settings['rest']['rest.enabled'])
      ->set('watchdog.enabled', $settings['watchdog']['watchdog.enabled'])
      ->set('watchdog.integration', $settings['watchdog']['watchdog.integration'])
      ->set('watchdog.channel', $settings['watchdog']['watchdog.channel'])
      ->set('watchdog.severity', $settings['watchdog']['watchdog.severity'])
      ->set('watchdog.filterOut', $settings['watchdog']['watchdog.filterOut'])
      ->set('watchdog.keywords', $settings['watchdog']['watchdog.keywords'])
      ->save();

    parent::submitForm($form, $form_state);
  }

}
