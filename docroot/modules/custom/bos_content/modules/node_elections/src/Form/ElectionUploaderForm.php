<?php

namespace Drupal\node_elections\Form;

use Drupal\Core\Entity\EntityStorageException;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Markup;
use Drupal\Core\Url;
use Drupal\file\Entity\File;
use Drupal\node\Entity\Node;
use Drupal\paragraphs\Entity\Paragraph;
use Drupal\taxonomy\Entity\Term;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Class ElectionUploaderForm.
 *
 * @package Drupal\node_elections\Form
 */
class ElectionUploaderForm extends FormBase {

  /**
   * @var \SimpleXMLElement The parsed contents of the uploaded file.
   */
  protected $results;

  /**
   * @var string Stores the actual path (uri) to the uploaded file.
   */
  protected $file_path;

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
          ->t('The directory %directory does not exist or is not writable.', [
            '%directory' => $directory,
          ]));
    }

    $history = '<th><td>' . $this->t("Report Generated") . '</td><td>' . $this->t("Import Timestamp"). '</td><td>' . $this->t("Filename") . '</td><td>' . $this->t("Result") . '</td></th>';
    foreach ($config->get("history") ?: [] as $hist) {
      $history .= '<tr><td>${hist["generate_date"]</td><td>${hist["upload_date"]</td><td>${hist["file"]</td><td>${hist["result"]</td></tr>';
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
    // The file contents are saved in the $this->>results object
    $this->results = NULL;

    $file = $form_state->getValue('upload', FALSE);

    if ($file && count($file) == 1) {
      if ($file = File::load($file[0])) {
        $this->file_path = $file->getFileUri();
        if ($this->file_path && file_exists($this->file_path)) {
          $this->results = $this->readElectionResults($form_state, $this->file_path);
        }
        else {
          $form_state->setErrorByName('upload', $this->t('File does not exist.'));
        }
      }
      else {
        $form_state->setErrorByName('upload', $this->t('File did not upload properly. Try again.'));
      }
    }
    else {
      $form_state->setErrorByName('upload', $this->t('Please provide a file to upload.'));
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
     if (!$this->results) {
       $this->messenger()
         ->addError("The File processing has failed. Contact Digital Team.");
       return FALSE;
     }
     // This will process the uploaded file into the database.
     $this->fileProcessor($form_state);

  }

  /**
   * Reads the xml file into an object, and validates it.
   *
   * @param $form_file
   *
   * @return \SimpleXMLElement|boolean
   */
  protected function readElectionResults(FormStateInterface &$form_state, string $file_path) {

    try {
      // Load XML into object
      if ($results = file_get_contents($file_path)) {
        $results = str_replace(["\r\n", "\r\n "], "", $results);
        $results = new \SimpleXMLElement($results);
      }
      else {
        $form_state->setErrorByName('upload', "Could not read file.");
        return FALSE;
      }
    }
    catch (\Exception $e) {
      $form_state->setErrorByName('upload', $e->getMessage());
      return FALSE;
    }

    // Validate the results.
    $submitted = $form_state->getValues();

    // Check the basic fields exist.
    if (empty($results->choices)
      || empty($results->contests)
      || empty($results->conteststats)
      || empty($results->electorgroups)
      || empty($results->pollprogress)
      || empty($results->results)
      || empty($results->Report_Info)
      || empty($results->settings)
    ) {
      $form_state->setErrorByName('upload', "The selected file does not have all the required fields. The file will not be processed.");
      return FALSE;

    }

    // Check the Unofficial/offical flag in the file matches the sected value
    //  on the form.
    if (intval($results->settings->ch[0]['officialResults']) != intval($submitted['result_type'])) {
      $type = intval($results->settings->ch[0]['officialResults']) ? "OFFICIAL" : "UNOFFICIAL";
      $form_state->setErrorByName('upload', "The selected file contains ${type} results. The file will not be processed.");
      return FALSE;
    }

    // Check to see that the election type input on the form is mentioned in the
    //  title of the report.
//    $type = strtoupper($submitted->Report_Info[0]['name']);
//    if ($submitted['election_type'] != "other" && stripos((string) $type, $submitted['election_type']) === FALSE) {
//      $form_state->setErrorByName('election_type', "The selected file does not appear to contain a ${submitted['election_type']} election. The file will not be processed.");
//      return FALSE;
//    }

    //  TODO: Check if this file has already been imported (compare report generated timestamps)

    return $results;
  }

  /**
   * Manager for processing the file into the entites.
   *
   * @param \Drupal\Core\Form\FormState $form_state
   *
   * @return boolean
   */
  protected function fileProcessor(FormStateInterface $form_state) {

    $submitted = $form_state->getValues();

    // Get some info about the uploaded file.
    $file = [
      "fid" => (int) $submitted['upload'][0],
      "path" => $this->file_path,
      "data" => $this->results,
      "areas" => [],
      "outcome" => "Success",
      "election_date" => $this->_setElectionDate($this->results->Report_Info[0]['Create']),
      "election_type" => $submitted['election_type'],
      "is_offical" => $submitted['result_type'],
      "new_election" => FALSE,
    ];

    // Evaluate the list of areas defined within the contests.
    foreach ($this->results->contests->contest as $contest) {
      $file["areas"][(string) $contest["areaId"]] = [
        "areadId" => (string) $contest["areaId"],
        "areadName" => (string) $contest["areaName"],
      ];
    }

    // Load the file into the entity
    switch ($submitted['election_type']) {
      case "state primary":
      case "state general":
      case "municipal primary":
      case "municipal general":
      case "special primary":
      case "special":
      case "other":
        // All election types are handled the same way at the moment.
        $this->processDefault($file);
    }

    // Finally, set the status message for the screen
    if ($file["outcome"] == "Success") {
      $this->messenger()
        ->addStatus("Success! The Election Results File has been processed and the results updated on the website.");
    }

    // Update the history array in the settings file.
    $config = \Drupal::service('config.factory')->getEditable("node_elections.settings");
    $history = $config->get("history");
    $history[] = [
      "generate_date" => strtotime((string) $this->results->Report_Info['Create']),
      "upload_date" => strtotime("Now"),
      "file" => $file["fid"],
      "result" => $file["outcome"],
    ];
    if (count($history) > 5) {
      unset($history[0]);
      $history = array_values($history);  //reindex so first element is [0].
    }
    $config->set("history", $history);
    $config->set("last-run", $history["upload_date"])
      ->save();

  }

  /**
   * This is the default loader for imported files.
   *   This function loads the XML file into the relevant entity.
   *
   * @param array $file Array of values required to import.
   *
   * @return void
   */
  protected function processDefault(array &$file) {

    // Build out a larger array to manage the data required to build out a
    // results update.
    $election = [
      "file" => $file,
      "taxonomies" => [
        "elections" => NULL,
        "election_areas" => NULL,
        "election_contests" => NULL,
        "elector_groups" => NULL,
        "election_candidates" => NULL,
      ],
      "nodes" =>[
        "election_report" => NULL,
      ],
      "paragraphs" => [
        "election_area_results" => NULL,
        "election_contest_results" => NULL,
        "election_candidate_results" => NULL,
      ],
    ];

    $current_election = \Drupal::entityTypeManager()
      ->getStorage("taxonomy_term")
      ->loadByProperties([
        "vid"=> "elections",
        "field_election_date" => $file["election_date"]
      ]);

    if ($current_election) {
      // Because the main taxonmy exists, the rest will too.
      $election["taxonomies"]["elections"] = reset($current_election);
      $this->fetchElection($election);
      $this->updateElection($election);
    }

    else {
      // Taxonomy does not exist for this election date.
      if ($this->createElection($election)) {
        $this->messenger()->addStatus("A new Election was created.");
      }
    }
  }

  /**
   * Fetch an existing election, including the creation of areas, contests and
   *   candidates etc.
   *
   * @param $election array Array containing the report contents.
   *
   * @return boolean
   */
  protected function fetchElection(array &$election) {

    $storage = \Drupal::entityTypeManager()
      ->getStorage("taxonomy_term");

    $election_id = $election["taxonomies"]["elections"]->id();

    // Load the taxonomies linked to this Election.
    // This captures election_areas, elections_contests and elector_groups.
    $terms = $storage->loadByProperties([
      "field_election" => $election_id,
    ]);
    foreach($terms as $id => $term) {
      if ($term->bundle() != "election_contests") {
        $election["taxonomies"][$term->bundle()][$id] = $term;
        $election["mapping"][$term->bundle()][$term->get("field_original_id")->getValue()[0]["value"]] = $id;
      }
    }

    // Load election_contests from election_areas.
    foreach ($election["taxonomies"]["election_areas"] as $area_id => $area_term) {
      $cont_terms = $storage->loadByProperties([
        "field_area" => $area_id,
        "vid" => "election_contests",
      ]);
      foreach($cont_terms as $cont_id => $cont_term) {
        $election["taxonomies"][$cont_term->bundle()][$cont_id] = $cont_term;
        $election["mapping"][$term->bundle()][$cont_term->get("field_original_id")->getValue()[0]["value"]] =  $cont_id;

        // Load election_candidates from election_contests.
        $cand_terms = $storage->loadByProperties([
          "field_contest" => $cont_id,
          "vid" => "election_candidates",
        ]);
        foreach($cand_terms as $cand_id => $cand_term) {
          $election["taxonomies"][$cand_term->bundle()][$cand_id] = $cand_term;
          $election["mapping"][$term->bundle()][$cand_term->get("field_original_id")->getValue()[0]["value"]] =  $cand_id;
        }
      }
    }

    // Load the current revision of the election report (node)
    $node = \Drupal::entityTypeManager()
      ->getStorage("node")
      ->loadByProperties([
        "field_election" => $election_id,
        "revision_default" => 1,
        "type" => "election_report",
      ]);
    $node = reset($node);
    $election["nodes"]["election_report"] = $node;

    // Now build out the paragraphs which contain the actual results.
    $storage = \Drupal::entityTypeManager()->getStorage('paragraph');
    foreach($node->get("field_area_results") as $area_key => $area_target) {
      $para_id = $area_target->get("target_id")->getValue();
      $para = $storage->load($para_id);
      $election["paragraphs"]["election_area_results"][$para_id] = $para;
      $election["mapping"]["election_area_results"][$para->get("field_election_area")->getValue("target_id")[0]["target_id"]] = $para_id;

      foreach($para->get("field_election_contest_results") as $contest_key => $contest_target) {
        $para_id = $contest_target->get("target_id")->getValue();
        $para = $storage->load($para_id);
        $election["paragraphs"]["election_contest_results"][$para_id] = $para;
        $election["mapping"]["election_contest_results"][$para->get("field_election_contest")->getValue("target_id")[0]["target_id"]] = $para_id;

        foreach($para->get("field_candidate_results") as $cand_key => $cand_target) {
          $para_id = $cand_target->get("target_id")->getValue();
          $para = $storage->load($para_id);
          $election["paragraphs"]["election_candidate_results"][$para_id] = $para;
          $election["mapping"]["election_candidate_results"][$para->get("field_election_candidate")->getValue("target_id")[0]["target_id"]] = $para_id;
        }

      }

    }



    return TRUE;
  }

  /**
   * This updates an existing election with new results.
   *
   * @param array $election
   *
   * @return bool
   */
  protected function updateElection(array &$election) {

    $data = $election["file"]["data"];
    /**
     * @var Node $node.
     */
    $node = $election["nodes"]["election_report"];
    $election_id = $election["nodes"]["election_report"]->get("field_election")->getValue()[0]["target_id"];

    // - Update the subtitle for the election in the taxonomy
    $tax = $election["taxonomies"]["elections"];
    if ($tax->get("field_election_subtitle", [])[0]->value != (string) $data->Report_Info["Report"]) {
      $tax->setNewRevision(FALSE);
      $tax->set("field_election_subtitle", $data->Report_Info["Report"]);
      try {
        if ($tax->save() != SAVED_UPDATED) {
          throw new EntityStorageException("New 'elections' taxonomy created when update was expected.");
        }
      }
      catch (EntityStorageException $e) {
        $this->messenger()->addError(Markup::create("Error updating data in 'elections' (taxonomy). <br> {$e->getMessage()}"));
        $election["file"]["outcome"] = "Failed";
        return FALSE;
      }
    }

    // Check that we have got enough entries in the taxonomies.
    if (count($election["taxonomies"]["election_areas"]) != count($election["file"]["areas"])) {
      $result = $this->_upsert_areas($election, $election_id);
      if ($result === FALSE) {
        $election["file"]["outcome"] = "Failed";
        return FALSE;
      }
    }
    if (count($election["taxonomies"]["elector_groups"]) != count($data->electorgroups->group)) {
      $result = $this->_upsert_groups($election, $election_id);
      if ($result=== FALSE) {
        $election["file"]["outcome"] = "Failed";
        return FALSE;
      }
    }
    if (count($election["taxonomies"]["election_contests"]) != count($data->contests->contest)) {
      $result = $this->_upsert_contests($election, $election_id);
      if ($result === FALSE) {
        $election["file"]["outcome"] = "Failed";
        return FALSE;
      }

    }
    if (count($election["taxonomies"]["election_candidates"]) != count($data->choices->ch)) {
      $result = $this->_upsert_candidates($election, $election_id);
      if ($result === FALSE) {
        $election["file"]["outcome"] = "Failed";
        return FALSE;
      }
    }

    // - Update the node with the filename and dates
    /**
     * @var \Drupal\node\Entity\Node $node
     */
    $el = ucfirst($election["file"]["election_type"]);
    $node->set("field_updated_date", strtotime($data->Report_Info["Create"]));
    $node->set("field_source_file", [
      'uri' => \Drupal::service('file_url_generator')->generate($election["file"]["path"])->getUri(),
      'title' => $el . " election on " . $election["file"]["election_date"]
    ]);
    $node->set("changed", strtotime("Now"));
    $node->set("uid", $this->currentUser()->id());
    // Ensure a new revision is created.
    $node->setNewRevision(TRUE);
    $node->set("revision_log", "Data for {$data->Report_Info["Create"]} updated via upload form.");
    try {
      if ($node->save() != SAVED_UPDATED) {
        throw new EntityStorageException("New 'election_report' node created when update was expected.");
      }
    }
    catch (EntityStorageException $e) {
      $this->messenger()->addError(Markup::create("Error updating data in' election_report' (node). <br> {$e->getMessage()}"));
      $election["file"]["outcome"] = "Failed";
      return FALSE;
    }

    // Take each area in update its paragraph.
    if (!$this->_upsert_area_results($election)) {
      $election["file"]["outcome"] = "Failed";
      return FALSE;
    }
    if (!$this->_upsert_contests_results($election)) {
      $election["file"]["outcome"] = "Failed";
      return FALSE;
    }
    if (!$this->_upsert_candidates_results($election)) {
      return FALSE;
    }

    $this->messenger()->addStatus(Markup::create("Success !. <br> File was process and results are now showing on the website."));
    return TRUE;
  }

  /**
   * Create a new election, including the creation of areas, contests and
   *   candidates etc.
   *
   * @param $election array Array containing the report contents.
   *
   * @return boolean
   */
  protected function createElection(&$election) {

    $election["file"]["new_election"] = TRUE;
    $data = $election["file"]["data"];

    $storage = \Drupal::entityTypeManager()
      ->getStorage("taxonomy_term");

    // === CREATE THE BASE TAXONOMY for this election ====
    foreach ($data->Report_info->Information as $info) {
      if ((string) $info["Description"] == "Note") {
        break;
      }
    }
    $election["taxonomies"]["elections"] = $storage
      ->create([
        "name" => (string) $data->Report_Info[0]["name"],
        "vid" => "elections",
        "description" => (string) $info ?: "",
        "field_display_title" => (string) $data->Report_Info[0]["name"],
        "field_election_subtitle" => (string) $data->Report_Info[0]["Report"],
        "field_election_date" => $election["file"]["election_date"],
      ])
      ->save();

    return TRUE;
  }

  /**
   * Calculate the Election date from the report creation date.
   *
   * @param string|int $date
   *
   * @return int
   */
  private function _setElectionDate(string|int $date) {

    // Convert to a timestamp if not already
    if (is_string($date)) {
      $election_date = strtotime($date);
    }

    // Subtract 12 hours so that we can generate reports up to 12 hours into the
    // day after the election.
    $election_date = strtotime("-12 hours", $election_date);

    // Just want a date, set to midday on election day.
    return date("Y-m-d", $election_date);
  }

  private function _upsert_groups(array &$election, int $id) {

    // There is nothing to change in the Group taxonomy after it has been created
    // for a particular election.

    $taxonomies = $election["taxonomies"]["elector_groups"];
    $data = $election["file"]["data"];

    foreach ($data->electorgroups as $group) {

      // Find this group in the taxonomy (or don't).
      $req_tax = FALSE;
      foreach ($taxonomies as $term_id => $tax) {
        if ($tax->get("field_original_id")->getValue()[0]["value"] == (string) $group["groupId"]) {
          $req_tax = TRUE;
          break;
        }
      }

      // Taxonomy is not found, so we need to create it.
      if (!$req_tax) {
        $tax = [
          "vid" => "elector_groups",
          "name" => $group["name"],
          "description" => "",
          "field_display_title" => $group["name"],
          "field_original_id" => $group["groupId"],
          "field_is_top" => $group["isTop"],
          "field_short_name" => $group["abbreviation"],
          "field_election" => [
            "target_id" => $id,
          ],
        ];

        try {
          $term = Term::create($tax);
          if ($term->save() == SAVED_NEW) {
            $election["taxonomies"]["elector_groups"][] = $term->id();
          }
          else {
            throw New EntityStorageException("Issue saving new Elector Group term.");
          }
        }
        catch (EntityStorageException $e) {
          $this->messenger()->addError("Error: {$e->getMessage()}");
          return FALSE;
        }

      }

    }

    return TRUE;
  }

  private function _upsert_areas(array &$election, int $id) {

    // There is nothing to change in the Area taxonomy after it has been created
    // for a particular election.

    $taxonomies = $election["taxonomies"]["election_areas"];
    $areas = $election["file"]["areas"];

    /**
     * @var Term $tax
     */
    foreach ($areas as $area) {
      // Find this area in the taxonomy (or don't).
      $req_tax = FALSE;
      foreach ($taxonomies as $term_id => $tax) {
        if ($tax->get("field_original_id")->getValue()[0]["value"] == (string) $area["areadId"]) {
          $req_tax = TRUE;
          break;
        }
      }

      // Taxonomy is not found, so we need to create it.
      if (!$req_tax) {
        $tax = [
          "vid" => "election_areas",
          "name" => $area["areadName"],
          "description" => "",
          "field_display_title" => $area["areadName"],
          "field_original_id" => $area["areadId"],
          "field_election" => [
            "target_id" => $id,
          ],
        ];

        try {
          $term = Term::create($tax);
          if ($term->save() == SAVED_NEW) {
            $election["taxonomies"]["election_areas"][$term->id()] = $term;
            $election["mapping"]["election_areas"][$area["areadId"]] =  $term->id();
          }
          else {
            throw New EntityStorageException("Issue saving new Area term.");
          }
        }
        catch (EntityStorageException $e) {
          $this->messenger()->addError("Error: {$e->getMessage()}");
          return FALSE;
        }

      }

    }
    return TRUE;
  }

  private function _upsert_area_results(array &$election) {

    $data = $election["file"]["data"];
    $node = $election["nodes"]["election_report"];

    foreach ($data->pollprogress->area as $area_result) {
      // Find the result and update.
      $term_id = $election["mapping"]["election_areas"][(string) $area_result["areaId"]];
      if (empty($election["mapping"]["election_area_results"][$term_id])) {
        try {
          $para_area_result = Paragraph::create([
            "type" => "election_area_results",
            "field_election_area" => $term_id,
            "field_precincts_total" => (string) $area_result["total"],
            "field_precincts_reported" => (string) $area_result["reported"],
          ]);
          $para_area_result->setParentEntity($election["nodes"]["election_report"], "field_area_results");
          $para_area_result->save();
          $election["paragraphs"]["election_area_results"][$para_area_result->id()] = $para_area_result;
          $election["mapping"]["election_area_results"][$term_id] =  $para_area_result->id();
          $node_array = $node->get("field_area_results");
          $node_array[] = ["target_id" => "", "target_revision_id" => ""];
          $node->set("field_areas_results", $node_array)->save();
        }
        catch (EntityStorageException $e) {
          $this->messenger()->addError("Error: {$e->getMessage()}");
          return FALSE;
        }
      }

      else {
        $para_id = $election["mapping"]["election_area_results"][$term_id];
        $para_area_result = $election["paragraphs"]["election_area_results"][$para_id];
        $para_area_result->set("field_precincts_reported", (string) $area_result["reported"]);
        $para_area_result->set("field_precincts_total", (string) $area_result["total"]);
        try {
          $para_area_result->save();
        }
        catch (EntityStorageException $e) {
          $this->messenger()->addError("Error: {$e->getMessage()}");
          return FALSE;
        }
      }

    }


    return TRUE;
  }

  private function _upsert_contests(array &$election, int $id) {

    // TODO: is the anything to change in the Contest taxonomy after it has
    //   been created for a particular election.

    $taxonomies = $election["taxonomies"]["election_contests"];
    $data = $election["file"]["data"];

    foreach ($data->contests->contest as $contest) {

      // Find this contest in the taxonomy (or don't).
      $req_tax = FALSE;
      foreach ($taxonomies as $term_id => $tax) {
        if ($tax->get("field_original_id")->getValue()[0]["value"] == (string) $contest["contestId"]) {
          $req_tax = TRUE;
          break;
        }
      }

      // Taxonomy is not found, so we need to create it.
      if (!$req_tax) {
        $area = NULL;
        foreach ($election["taxonomies"]["election_areas"] as $area_term) {
          if ($area_term->get("field_original_id")->getValue()[0]["value"] == $election["file"]["areas"][(string) $contest["areaId"]]["areadId"]) {
            $area = $area_term->id();
            break;
          }
        }
        $tax = [
          "vid" => "election_contests",
          "name" => $contest["name"],
          "description" => "",
          "field_display_title" => $contest["name"],
          "field_original_id" => $contest["contestId"],
          "field_contest_eligible" => $contest["eligible"],
          "field_contest_isacclaimed" => $contest["isAcclaimed"],
          "field_contest_isdisabled" => $contest["isDisabled"],
          "field_contest_ismajor" => $contest["IsMajor"],
          "field_contest_pos" => $contest["pos"],
          "field_contest_sortorder" => $contest["sortOrder"],
          "field_has_writeins" => $contest["writeins"],
          "field_election" => [
            "target_id" => $id,
          ],
          "field_area" => [
            "target_id" => $area,
          ],
        ];

        try {
          $term = Term::create($tax);
          if ($term->save() == SAVED_NEW) {
            $election["taxonomies"]["election_contests"][$term->id()] = $term;
            $election["mapping"]["election_contests"][$contest["contestId"]] = $term->id();
          }
          else {
            throw New EntityStorageException("Issue saving new Contest term.");
          }
        }
        catch (EntityStorageException $e) {
          $this->messenger()->addError("Error: {$e->getMessage()}");
          return FALSE;
        }

      }

    }

    return TRUE;
  }

  private function _upsert_contest_results(array &$election) {
    try {}
    catch (\EntityStorageExceptio $e) {
      $this->messenger()->addError("Error: {$e->getMessage()}");
      return FALSE;
    }
    return TRUE;
  }

  private function _upsert_candidates(array &$election, int $id) {
    // TODO: is the anything to change in the Candidate taxonomy after it has
    //   been created for a particular election.

    $taxonomies = $election["taxonomies"]["election_candidates"];
    $data = $election["file"]["data"];

    foreach ($data->choices->ch as $choice) {

      // Find this area in the taxonomy (or don't).
      $req_tax = FALSE;
      foreach ($taxonomies as $term_id => $tax) {
        if ($tax->get("field_original_id")->getValue()[0]["value"] == (string) $choice["chId"]) {
          $req_tax = TRUE;
          break;
        }
      }

      // Taxonomy is not found, so we need to create it.
      if (!$req_tax) {
        $contest = NULL;
        foreach ($election["taxonomies"]["election_contests"] as $contest_term) {
          if ($contest_term->get("field_original_id")->getValue()[0]["value"] == $choice["conId"]) {
            $contest = $contest_term->id();
            break;
          }
        }
        $tax = [
          "vid" => "election_candidates",
          "name" => $choice["name"],
          "description" => "",
          "field_display_title" => $choice["name"],
          "field_original_id" => $choice["chId"],

          "field_candidate_dis" => $choice["dis"],
          "field_candidate_showvotes" => $choice["showVotes"],
          "field_candidate_wri" => $choice["wri"],
          "field_candidate_wrind" => $choice["wrInd"],

          "field_contest" => [
            "target_id" => $contest,
          ],
        ];

        try {
          $term = Term::create($tax);
          if ($term->save() == SAVED_NEW) {
            $election["taxonomies"]["election_candidates"][] = $term->id();
            $election["mapping"]["election_candidates"][$choice["chId"]] = $term->id();
          }
          else {
            throw New EntityStorageException("Issue saving new Candidate term.");
          }
        }
        catch (EntityStorageException $e) {
          $this->messenger()->addError("Error: {$e->getMessage()}");
          return FALSE;
        }

      }

    }

    return TRUE;
  }

  private function _upsert_candidate_results(array &$election) {
    try {}
    catch (EntityStorageException $e) {
      $this->messenger()->addError("Error: {$e->getMessage()}");
      return FALSE;
    }
    return TRUE;
  }


}
