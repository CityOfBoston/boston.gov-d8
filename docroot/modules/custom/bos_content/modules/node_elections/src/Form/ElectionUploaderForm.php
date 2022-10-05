<?php

namespace Drupal\node_elections\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\file\Entity\File;
use Drupal\node\Entity\Node;
use Drupal\node_elections\Controller\ElectionFileUploader;
use Drupal\taxonomy\Entity\Term;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Class ElectionUploaderForm.
 *
 * @package Drupal\node_elections\Form
 */
class ElectionUploaderForm extends FormBase {

  /**
   * @var \Drupal\node_elections\Form\ElectionUploaderForm
   *   Controller class used to manage the actual uplad process.
   */
  protected ElectionFileUploader $importer;

  /**
   * Implements getFormId().
   */
  public function getFormId() {
    return 'node_elections_uploader';
  }

  /**
   * Implements buildForm().
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('node_elections.settings');

    // Manage the upload folder.
    if (!$directory = $config->get('upload_directory')) {
      $directory = 'public://election_results';
      $config = \Drupal::service('config.factory')->getEditable("node_elections.settings");
      $config->set('upload_directory', $directory)->save();
    }

    if (!file_exists($directory)) {
      mkdir($directory);
    }
    $directory_is_writable = file_exists($directory) && is_writable($directory);
    if (!$directory_is_writable) {
      $this
        ->messenger()
        ->addError($this
          ->t('The directory %directory does not exist or is not writable. Error 9100', [
            '%directory' => $directory,
          ]));
    }

    $history = '<tr><th>' . $this->t("Election") . '</th><th>' . $this->t("Report Timestamp") . '</th><th>' . $this->t("File Loaded") . '</th><th>' . $this->t("Upload Timestamp"). '</th><th>' . $this->t("Result") . '</th></tr>';
    foreach ($config->get("history") ?: [] as $hist) {
      $rdate = date("d M Y <b>h:n A</b>", $hist['generate_date']);
      $idate = date("d M Y <b>h:n A</b>", $hist['upload_date']);
      $elec_term_name = isset($hist["election"]) ? Term::load($hist["election"])->getName() : "";
      $file = File::load($hist["file"]);
      $file = "<a href='" . \Drupal::service('file_url_generator')->generate($file->get("uri")->getString())->getUri() ."' target='_blank'>" . $file->getFilename() . "</a>";
      $revision = "";
      if (isset($hist["revision"])) {
        $node_id = \Drupal::entityTypeManager()
          ->getStorage("node")
          ->loadRevision($hist["revision"])
          ->id();
        $revision_link = "/node/{$node_id}/revisions/{$hist["revision"]}/view";
        $revision = " (<a href='{$revision_link}' target='_blank'>{$hist["revision"]}</a>)";
      }
      $class = "result " . strtolower($hist["result"]);
      $history .= "<tr><td>{$elec_term_name}{$revision}</td><td>{$rdate}</td><td>{$file}</td><td>{$idate}</td><td class='{$class}'>{$hist["result"]}</td></tr>";
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
            "#attributes" => ["title" => "Select \"other\" if the title does not contain the words 'primary','general' or 'municipal'."],
            '#required' => TRUE,
            '#default_value' => '',
            '#options' => [
              '' => '-- Select --',
              'state primary' => 'State Primary',
              'state general' => 'State General',
              'municipal primary' => 'Municipal Primary',
              'municipal general' => 'Municipal General',
              'special primary' => 'Special Primary',
              'special' => 'Special',
              'other' => 'Other',
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
            '#upload_location' => $directory,
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
   * Implements submitForm().
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {

    // When the file is uploaded, this validateForm function is called.
    // We cannot check the file because it's not uploaded yet, so exit.
    if ((string) $form_state->getTriggeringElement()["#value"] == "Upload") {
      return;
    }

    // This will locate and validate the uploaded file.
    // The file contents are saved in the $this->uploader->results object
    $this->importer = new ElectionFileUploader();

    $file = $form_state->getValue('upload', FALSE);

    if ($file && count($file) == 1) {
      if ($file = File::load($file[0])) {
        $file_path = $file->getFileUri();
        if ($file_path && file_exists($file_path)) {
          $this->importer->readElectionResults($form_state, $file_path);
        }
        else {
          $form_state->setErrorByName('upload', $this->t('File does not exist. Error 9007.'));
        }
      }
      else {
        $form_state->setErrorByName('upload', $this->t('File did not upload properly. Try again. Error 9008.'));
      }
    }
    else {
      $form_state->setErrorByName('upload', $this->t('Please provide a file to upload. Error 9009.'));
    }
  }

  /**
   * Implements submitForm().
   */
   public function submitForm(array &$form, FormStateInterface $form_state) {

     // Just verify that we have an authenticated user in case the form is
     // somehow compromised.  Routing should handle this ... this is just a
     // double check!
     $account = $this->currentUser();
     if (empty($account)
       || $account->isAnonymous()
       || !$account->hasPermission("edit any election_report content")
     ) {
       throw new NotFoundHttpException();
     }

     // Check that the file has been read and the contents have passed an
     // initial validation in validateForm().
     if (!$this->importer->hasResults()) {
       $this->messenger()
         ->addError("The File processing has failed. Contact Digital Team. Error 9101.");
       return FALSE;
     }

     // This will process the uploaded file into the database.
     $this->importer->import($form_state);

  }


}
