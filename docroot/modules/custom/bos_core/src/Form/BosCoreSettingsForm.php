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
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'bos_core_admin_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ["bos_core.settings"];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('bos_core.settings');
    $msettings = $config->get('icon');
    $settings = $config->get('ga_settings');

    $endpoint = isset($settings["ga_endpoint"]) ? $settings["ga_endpoint"] : "https://www.google-analytics.com/collect";

    $form = [
      '#tree' => TRUE,
      'bos_core' => [
        '#type' => 'fieldset',
        '#title' => 'Boston Core Settings',
        '#description' => 'Configuration for Core Boston Components.',
        '#collapsible' => FALSE,

        "icon" => [
          '#type' => 'details',
          '#title' => 'Patterns Icon Library',
          '#description' => 'Integration with patterns icon library.',
          '#open' => TRUE,

          "manifest" => [
            '#type' => 'textfield',
            '#title' => t('Manifest location'),
            '#description' => t('The remote http location for the icon manifest.txt file.<br/>example: <i>https://patterns.boston.gov/assets/icons/manifest.txt</i>'),
            '#default_value' => $msettings['manifest'] ?: 'https://patterns.boston.gov/assets/icons/manifest.txt',
            '#attributes' => [
              "placeholder" => 'https://patterns.boston.gov/assets/icons/manifest.txt',
            ],
            '#required' => TRUE,
          ],
          "cron" => [
            '#type' => 'checkbox',
            '#title' => t('Import with Cron'),
            '#description' => t('The manifest file specified above will be imported on cron runs.<br><b>Note:</b> only updated icons will be processed.<br><b>Note:</b> Deslecting this checkbox means icons will only be imported when <span style="color: red">drush biim</span> is executed.'),
            '#default_value' => isset($msettings['cron']) ? $msettings['cron'] : FALSE,
          ],
        ],

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
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $settings = $form_state->getValue('bos_core');

    $newValues1 = [
      'ga_tid' => $settings['ga_settings']['ga_tid'],
      'ga_cid' => $settings['ga_settings']['ga_cid'],
      'ga_enabled' => $settings['ga_settings']['ga_enabled'],
    ];
    $newValues2 = [
      'manifest' => $settings['icon']['manifest'],
      'cron' => $settings['icon']['cron'],
    ];
    $this->config('bos_core.settings')
      ->set('ga_settings', $newValues1)
      ->set('icon', $newValues2)
      ->save();

    parent::submitForm($form, $form_state);
  }

}
