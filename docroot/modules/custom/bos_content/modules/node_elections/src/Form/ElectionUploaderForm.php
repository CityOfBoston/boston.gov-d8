<?php

namespace Drupal\node_elections\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

/**
 * Class ElectionUploaderForm.
 *
 * @package Drupal\node_elections\Form
 */
class ElectionUploaderForm extends FormBase {

  protected function getEditableConfigNames() {
    return ["node_elections.settings"];
  }

  /**
   * Implements getFormId().
   */
  public function getFormId() {
    return 'node_elections_uploader';
  }

  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('node_elections.settings');

    // Manage the upload folder.
    if (!$config->get('upload_directory')) {
      $config->set('upload_directory', 'sites/default/files/election_results');
      $config->save();
    }
    $directory = $config->get('upload_directory');
    if (!file_exists($directory)) {
      mkdir($directory);
    }
    $directory_is_writable = file_exists($directory) && is_writable($directory);
    if (!$directory_is_writable) {
      $this
        ->messenger()
        ->addError($this
          ->t('The directory %directory does not exist or is not writable.', [
            '%directory' => $directory,
          ]));
    }

    $history = '<th><td>Date</td><td>Filename</td><td>Result</td></th>';
    foreach ($config->get("history") ?: [] as $hist) {
      $history .= '<tr><td>${hist["date"]</td><td>${hist["file"]</td><td>${hist["result"]</td></tr>';
    }

    // Create the form.
    $form = [
      '#theme' => 'system_config_form',
      '#attached' => [
        'library' => 'node_elections/election_admin',
      ],
      'node_elections' => [
        '#type' => 'fieldset',
        '#title' => 'Unoffical Elections Results',
        'history_wrapper' => [
          '#type' => 'details',
          '#title' => $this->t('Last 5 Uploads'),
          'history' => [
            '#markup' => "<table>${history}</table>",
          ],
        ],
        'config_wrapper' => [
          '#type' => 'fieldset',
          '#title' => $this->t('Upload File'),
          '#attributes' => [
            'class' => ['flex']
          ],
          'election_type' => [
            '#type' => 'select',
            '#title' => $this->t('Election Type:'),
            '#required' => TRUE,
            '#default_value' => '',
            '#options' => [
              '' => '-- Select --',
              'primary' => 'State Primary',
              'general' => 'State General',
              'municipal' => 'Municipal',
            ],
          ],
          'result_type' => [
            '#type' => 'select',
            '#title' => $this->t('Results Type:'),
            '#default_value' => '0',
            '#options' => [
              '0' => 'Unoffical',
              '1' => 'Official',
            ],
          ],
          'upload' => [
            '#type' => 'managed_file',
            '#required' => TRUE,
            '#upload_location' => "public://election_results",
            '#upload_validators' => [
              'file_validate_extensions' => ['xml']
            ],
            '#size' => 20,
            '#title' => $this->t('Election Results File:'),
            '#description' => $this->t('S'.'elect a file which has been exported from the elections system.<br/>Allowed file type is <i>.xml</i>'),
          ],
        ],
        'actions' => [
          '#type' => 'actions',
          'submit' => [
            '#type' => 'submit',
            '#value' => $this->t('Process File'),
            '#button_type' => 'primary',
            '#disabled' => !$directory_is_writable,
          ],
          'cancel' => [
            '#type' => 'link',
            '#title' => $this->t('Return to Homepage'),
            '#url' => Url::fromUserInput("/"),
            '#attributes' => ['class' => ['button']],
          ],
        ],
      ]
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    if (empty($form_state->getValue('upload'))) {
      $form_state->setErrorByName('upload', $this->t('Please provide a file to upload.'));
    }
  }

  public function submitForm(array &$form, FormStateInterface $form_state) {
    // TODO: Implement submitForm() method.
    return;
  }

}
