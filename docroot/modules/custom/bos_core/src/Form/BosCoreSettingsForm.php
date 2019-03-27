<?php

namespace Drupal\bos_core\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Admin Settings form for bos_core.
 *
 * @see https://developers.google.com/analytics/devguides/collection/protocol/v1/reference
 */

/**
 * Class BosCoreSettingsForm.
 *
 * @package Drupal\bos_core\Form
 */
class BosCoreSettingsForm extends ConfigFormBase {

  /**
   * Implements getFormId().
   */
  public function getFormId() {
    return 'bos_core_admin_settings';
  }

  /**
   * Implements getEditableConfigNames().
   */
  protected function getEditableConfigNames() {
    return ["bos_core.settings"];
  }

  /**
   * Implements buildForm()
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('bos_core.settings');
    $settings = $config->get('ga_settings');

    $endpoint = isset($settings["ga_endpoint"]) ? $settings["ga_endpoint"] : "https://www.google-analytics.com/collect";

    $form = [
      '#tree' => TRUE,
      'bos_core' => [
        '#type' => 'fieldset',
        '#title' => 'Boston Core Settings',
        '#description' => 'Configuration for Core Boston Components.',
        '#collapsible' => FALSE,

        "ga_settings" => [
          '#type' => 'details',
          '#title' => 'Google Analytics',
          '#description' => 'Configuration for REST endpoint tracking in Google Analytics.',
          '#open' => TRUE,

          "ga_enabled" => [
            '#type' => 'checkbox',
            '#title' => t('REST Tracking Enabled'),
            '#default_value' => isset($settings['ga_enabled']) ? $settings['ga_enabled'] : FALSE,
          ],

          'ga_endpoint' => [
            '#type' => 'textfield',
            '#title' => t('Google URL Endpoint'),
            '#description' => t('This is the Google Measurement API endpoint being used.<br/>
                  This value can only be changed using drush (drush bgae) - please contact a developer to make changes.'),
            '#default_value' => $endpoint,
            '#attributes' => [
              "disabled" => TRUE,
            ],
          ],
          'ga_tid' => [
            '#type' => 'textfield',
            '#title' => t('Tracking ID'),
            '#description' => t('Enter the Google Tracking Id provided by Google.'),
            '#default_value' => $settings['ga_tid'],
            '#attributes' => [
              "placeholder" => 'e.g. UA-XXXXXXX-XX',
            ],
            '#required' => TRUE,
          ],
          "ga_cid" => [
            '#type' => 'textfield',
            '#title' => t('Client ID'),
            '#description' => t("Enter a Client Id (32char Hex). <br/> See <a href='https://developers.google.com/analytics/devguides/collection/protocol/v1/parameters#cid'>documentation</a>"),
            '#default_value' => $settings['ga_cid'],
            '#attributes' => [
              "placeholder" => 'e.g. 35009a79-1a05-49d7-b876-2b884d0f825b',
            ],
            '#required' => TRUE,
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
    $settings = $form_state->getValue('bos_core');

    $newValues = [
      'ga_tid' => $settings['ga_settings']['ga_tid'],
      'ga_cid' => $settings['ga_settings']['ga_cid'],
      'ga_enabled' => $settings['ga_settings']['ga_enabled'],
    ];

    $this->config('bos_core.settings')
      ->set('ga_settings', $newValues)
      ->save();

    parent::submitForm($form, $form_state);
  }

}
