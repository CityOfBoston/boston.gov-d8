<?php

namespace Drupal\bos_swiftype\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class SwiftypeSettingsForm.
 *
 * @package Drupal\bos_swiftype\Form
 */
class SwiftypeSettingsForm extends ConfigFormBase {

  /**
   * Implements getFormId().
   */
  public function getFormId() {
    return 'swiftype_admin_settings';
  }

  /**
   * Implements getEditableConfigNames().
   */
  protected function getEditableConfigNames() {
    return ["bos_swiftype.settings"];
  }

  /**
   * Implements buildForm()
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('bos_swiftype.settings');
    $form = [
      '#tree' => TRUE,
      'swiftype_admin' => [
        '#type' => 'fieldset',
        '#title' => 'Swiftype Search Settings',
        '#description' => 'Configuration for Swiftype Search.',
        'swiftype_key' => [
          '#type' => 'textfield',
          '#title' => t('API KEY / Token'),
          '#description' => t('The Swiftype key (as provided by Swiftype).'),
          '#default_value' => $config->get('swiftype_key'),
          '#required' => TRUE,
        ],
        'swiftype_email' => [
          '#type' => 'textfield',
          '#title' => t('Email'),
          '#description' => t('Email as registered with Swiftype.'),
          '#default_value' => $config->get('swiftype_email'),
          '#required' => TRUE,
        ],
        'swiftype_password' => [
          '#type' => 'password',
          '#title' => t('Password'),
          '#description' => t('Password as registered with Swiftype.'),
          '#default_value' => $config->get('swiftype_password'),
          '#required' => TRUE,
        ],
        'swiftype_engine' => [
          '#type' => 'textfield',
          '#title' => t('Swiftype Engine'),
          '#description' => t('Engine as registered with Swiftype.'),
          '#default_value' => $config->get('swiftype_engine'),
          '#required' => TRUE,
        ],
        'swiftype_endpoint_host' => [
          '#type' => 'textfield',
          '#title' => t('Swiftype Endpoint Host'),
          '#description' => t('The http host (domain) to request from - <i>e.g. https://api.swiftype.com</i>.'),
          '#default_value' => $config->get('swiftype_endpoint_host') ?: "https://api.swiftype.com",
          '#required' => TRUE,
        ],
        'swiftype_endpoint_path' => [
          '#type' => 'textfield',
          '#title' => t('Swiftype Endpoint Path'),
          '#description' => t('The path on the domain to connect to - <i>/api/v1/</i>.'),
          '#default_value' => $config->get('swiftype_endpoint_path') ?: "/api/v1/",
          '#required' => TRUE,
        ],
        'swiftype_notes' => [
          '#type' => 'textarea',
          '#title' => t('Notes'),
          '#description' => t('Any notes you wish to record with this account.'),
          '#default_value' => $config->get('swiftype_notes'),
          '#rows' => 5,
          '#required' => FALSE,
        ],
      ],
    ];
    return parent::buildForm($form, $form_state);
  }

  /**
   * Implements submitForm().
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $settings = $form_state->getValue('swiftype_admin');

    $this->config('bos_swiftype.settings')
      ->set('swiftype_key', $settings['swiftype_key'])
      ->set('swiftype_password', $settings['swiftype_password'])
      ->set('swiftype_email', $settings['swiftype_email'])
      ->set('swiftype_engine', $settings['swiftype_engine'])
      ->set('swiftype_endpoint_host', $settings['swiftype_endpoint_host'])
      ->set('swiftype_endpoint_path', $settings['swiftype_endpoint_path'])
      ->set('swiftype_notes', $settings['swiftype_notes'])
      ->save();
    parent::submitForm($form, $form_state);
  }

}
