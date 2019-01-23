<?php

namespace Drupal\bos_emergency_alerts\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class EmergencyAlertsSettingsForm.
 *
 * @package Drupal\bos_emergency_alerts\Form
 */
class EmergencyAlertsSettingsForm extends ConfigFormBase {

  /**
   * Implements getFormId().
   */
  public function getFormId() {
    return 'emergency_alerts_admin_settings';
  }

  /**
   * Implements getEditableConfigNames().
   */
  protected function getEditableConfigNames() {
    return ["bos_emergency_alerts.settings"];
  }

  /**
   * Implements buildForm()
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('bos_emergency_alerts.settings');
    $codered = $config->get('codered_settings');

    $form = [
      '#tree' => TRUE,
      'bos_emergency_alerts' => [
        '#type' => 'fieldset',
        '#title' => 'Emergency Alerts',
        '#description' => 'Configuration for Emergency Alerts.',
        '#collapsible' => FALSE,

        'codered_settings' => [
          '#type' => 'details',
          '#title' => 'CodeRed API Endpoint',
          '#description' => 'Configuration for Emergency Alert Subscriptions on CodeRed System.',
          '#open' => TRUE,

          'api_base' => [
            '#type' => 'textfield',
            '#title' => t('CodeRed API URL'),
            '#description' => t('Enter the full (remote) URL for the CodeRed API used to register subscriptions.'),
            '#default_value' => $codered['api_base'],
            '#attributes' => [
              "placeholder" => 'e.g. https://api.coderedweb.com',
            ],
            '#required' => FALSE,
          ],

          'api_user' => [
            '#type' => 'textfield',
            '#title' => t('CodeRed API Username'),
            '#description' => t('Enter the CodeRed username used to authenticate subscriptions.'),
            '#default_value' => isset($codered['api_user']) ? $codered['api_user'] : "",
            '#required' => FALSE,
          ],

          'api_pass' => [
            '#type' => 'textfield',
            '#title' => t('CodeRed API Password'),
            '#description' => t('Enter the password for the CodeRed user.'),
            '#default_value' => isset($codered['api_pass']) ? $codered['api_pass'] : "",
            '#required' => FALSE,
          ],

          'api_group' => [
            '#type' => 'textfield',
            '#title' => t('CodeRed Subscriber Group'),
            '#description' => t('Enter the group name (string) in which subscribers are to be added.'),
            '#default_value' => isset($codered['api_group']) ? $codered['api_group'] : "",
            '#required' => FALSE,
          ],

          'email_alerts' => [
            '#type' => 'textfield',
            '#title' => t('module Error Alerts'),
            '#description' => t('Enter an email to which module errors will be advised.'),
            '#default_value' => isset($codered['email_alerts']) ? $codered['email_alerts'] : "",
            '#required' => FALSE,
            '#attributes' => [
              "placeholder" => 'e.g. digital@boston.gov',
            ],
          ],
        ],
      ],
    ];
    return parent::buildForm($form, $form_state);
  }

  /**
   * Implements submitForm().
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $settings = $form_state->getValue('bos_emergency_alerts');

    $newValues = [
      'api_base' => $settings['codered_settings']['api_base'],
      'api_user' => $settings['codered_settings']['api_user'],
      'api_pass' => $settings['codered_settings']['api_pass'],
      'api_group' => $settings['codered_settings']['api_group'],
      'email_alerts' => $settings['codered_settings']['email_alerts'],
    ];

    $this->config('bos_emergency_alerts.settings')
      ->set('codered_settings', $newValues)
      ->save();

    parent::submitForm($form, $form_state);
  }

}
