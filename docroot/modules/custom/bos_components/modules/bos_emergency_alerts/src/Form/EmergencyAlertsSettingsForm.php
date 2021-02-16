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
    $config_settings = $config->get('emergency_alerts_settings');

    if (isset($_ENV['EVERBRIDGE_SETTINGS'])) {
      $everbridge_env = (object) [];
      $get_vars = explode(",", $_ENV['EVERBRIDGE_SETTINGS']);
      foreach ($get_vars as $item) {
        $json = explode(":", $item);
        $everbridge_env->{$json[0]} = $json[1];
      }
      $everbridge_env = json_encode($everbridge_env);
      $everbridge_env = json_decode($everbridge_env);
    }
    

    $form = [
      '#tree' => TRUE,
      'bos_emergency_alerts' => [
        '#type' => 'fieldset',
        '#title' => 'Emergency Alerts',
        '#description' => 'Configuration for Emergency Alerts.',
        '#collapsible' => FALSE,

        'emergency_alerts_settings' => [
          '#type' => 'details',
          '#title' => 'API Endpoint',
          '#description' => 'Configuration for Emergency Alert Subscriptions via vendor API.',
          '#open' => TRUE,

          'api_base' => [
            '#type' => 'textfield',
            '#title' => t('API URL'),
            '#description' => t('Enter the full (remote) URL for the endpoint / API used to register subscriptions.'),
            '#default_value' => isset($config_settings['api_base']) ? $config_settings['api_base']  : "",
            '#attributes' => [
              "placeholder" => 'e.g. https://api.everbridge.com',
            ],
            '#required' => FALSE,
          ],

          'api_user' => [
            '#type' => 'textfield',
            '#title' => t('API Username'),
            '#description' => t('Username set as Environment variable.'),
            '#default_value' => isset($_ENV['EVERBRIDGE_SETTINGS']) ? $everbridge_env->api_user : "",
            '#attributes' => [
              "readonly" => 'readonly',
            ],
            '#required' => FALSE,
          ],

          'api_pass' => [
            '#type' => 'textfield',
            '#title' => t('API Password'),
            '#description' => t('Password set as Environment variable.'),
            '#default_value' => isset($_ENV['EVERBRIDGE_SETTINGS']) ? $everbridge_env->api_password : "",
            '#attributes' => [
              "readonly" => 'readonly',
            ],
            '#required' => FALSE,
          ],

          'email_alerts' => [
            '#type' => 'textfield',
            '#title' => t('module Error Alerts'),
            '#description' => t('Enter an email to which module errors will be advised.'),
            '#default_value' => isset($config_settings['email_alerts']) ? $config_settings['email_alerts'] : "",
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
      'api_base' => $settings['emergency_alerts_settings']['api_base'],
      'email_alerts' => $settings['emergency_alerts_settings']['email_alerts'],
    ];

    $this->config('bos_emergency_alerts.settings')
      ->set('emergency_alerts_settings', $newValues)
      ->save();

    parent::submitForm($form, $form_state);
  }

}
