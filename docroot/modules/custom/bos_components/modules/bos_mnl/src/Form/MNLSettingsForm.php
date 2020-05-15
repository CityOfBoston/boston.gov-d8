<?php

namespace Drupal\bos_mnl\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class MNLSettingsForm.
 *
 * @package Drupal\bos_mnl\Form
 */
class MNLSettingsForm extends ConfigFormBase {

  /**
   * Implements getFormId().
   */
  public function getFormId() {
    return 'mnl_admin_settings';
  }

  /**
   * Implements getEditableConfigNames().
   */
  protected function getEditableConfigNames() {
    return ["bos_mnl.settings"];
  }

  /**
   * Implements buildForm()
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('bos_mnl.settings');
    $form = [
      '#tree' => TRUE,
      'mnl_admin' => [
        '#type' => 'fieldset',
        '#title' => 'MNL API Endpoint',
        '#description' => 'Configuration for My Neighborhood Lookup custom API endpoints.',
        'auth_token' => [
          '#type' => 'textfield',
          '#title' => t('API KEY / Token'),
          '#description' => t('Enter a random string to authenticate API calls.'),
          '#default_value' => $config->get('auth_token'),
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
    $settings = $form_state->getValue('mnl_admin');

    $this->config('bos_mnl.settings')
      ->set('auth_token', $settings['auth_token'])
      ->save();
    parent::submitForm($form, $form_state);
  }

}
