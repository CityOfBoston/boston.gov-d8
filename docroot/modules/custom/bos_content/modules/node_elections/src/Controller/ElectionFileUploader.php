<?php

namespace Drupal\node_elections\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityStorageException;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Markup;
use Drupal\node\Entity\Node;
use Drupal\paragraphs\Entity\Paragraph;
use Drupal\taxonomy\Entity\Term;

class ElectionFileUploader extends ControllerBase {

  /**
   * @var ElectionResults The parsed contents of the uploaded file.
   */
  private ElectionResults $results;

  /**
   * @var string Stores the actual path (uri) to the uploaded file.
   */
  public $file_path;

  /**
   * Detect if the $this->results variable has been set yet
   *   (i.e. has the file been validated yet).
   *
   * @return bool
   */
  public function hasResults(): bool {
    return !empty($this->results);
  }

  /**
   * Controls the reading and validating of the uploaded file.
   *
   * @param FormStateInterface $form_state The submitted form.
   * @param string $file_path The path to the uploaded file in a public folder.
   *
   * @return boolean Import success flag.
   */
  public function readElectionResults(FormStateInterface &$form_state, string $file_path) {

    $submitted = $form_state->getValues();

    // At the moment there is only one xml file and therefore only one reader.
    // Later we may find that different elections have different file formats.
    //  Since the ultimate entity structure within Drupal is the same, it is
    //  likely that the best solution will be to try to massage the incoming
    //  file data into the expected format in the $this->results object.
    switch ($submitted['election_type']) {
      case "state primary":
      case "state general":
      case "municipal primary":
      case "municipal general":
      case "special primary":
      case "special":
      case "other":
        if (!$this->readDefaultElectionFormat($form_state, $file_path)) {
          return FALSE;
        }
    }

    // No matter the file format or contents format we should now have the data
    // loaded into a known ElectionResults class.

    // Do a cursory validation the results class.

    // Check the basic fields exist.
    if (empty($this->results->choices)
      || empty($this->results->contests)
      || empty($this->results->conteststats)
      || empty($this->results->electorgroups)
      || empty($this->results->pollprogress)
      || empty($this->results->results)
      || empty($this->results->election)
      || empty($this->results->settings)
    ) {
      $form_state->setErrorByName('upload', "The selected file does not have all the required fields. The file will not be processed. Error 9004.");
      return FALSE;

    }

    // Check logic.
    // These are non-fatal issues. Is it possible that this could happen early
    // in the election ?
    if (count($this->results->contests) != count($this->results->conteststats)) {
      $this->messenger()->addWarning("It appears that there are missing contest results.  Please check the webpage after processing finishes.");
    }
    if (count($this->results->choices) != count($this->results->results)) {
      $this->messenger()->addWarning("It appears that there are missing choice/candidate results.  Please check the webpage after processing finishes.");
    }

    // Check the Unofficial/offical flag in the file matches the sected value
    //  on the form.
    if (intval($this->results->settings[0]['officialresults']) != intval($submitted['result_type'])) {
      $type = intval($this->results->settings[0]['officialresults']) ? "OFFICIAL" : "UNOFFICIAL";
      $form_state->setErrorByName('upload', "The selected file contains ${type} results. The file will not be processed. Error 9005.");
      return FALSE;
    }

    // Check to see that the election type input on the form is mentioned in the
    //  title of the report.
    if (FALSE) {
      // Don't do this for now, with all the election types, anticipating the
      // file naming convention is getting complex and dangerous.
      $type = strtoupper($submitted->election['name']);
      if ($submitted['election_type'] != "other" && stripos($type, $submitted['election_type']) === FALSE) {
        $form_state->setErrorByName('election_type', "The selected file does not appear to contain a ${submitted['election_type']} election. The file will not be processed. Error 9006.");
        return FALSE;
      }
    }

    //  TODO: Check if this file has already been imported (compare report generated timestamps)

  }

  /**
   * Default reader to ingest an xml file into an xml class object.
   *   In the future, there may be additional variants for different file
   *   types (e.g. json) or data structures for the contents.
   * This variant imports the standard xml file exported from the elections
   * system.
   *
   * @param FormStateInterface $form_state The submitted form.
   * @param string $file_path The path to the uploaded file in a public folder.
   *
   * @return bool Read success flag.
   */
  public function readDefaultElectionFormat(FormStateInterface &$form_state, string $file_path) {

    unset($this->xml_results);

    try {
      // Load XML into object
      if (file_exists($file_path)) {
        $this->file_path = $file_path;
        if ($import = file_get_contents($file_path)) {
          $import = str_replace(["\r\n", "\r\n "], "", $import);
          $import = new \SimpleXMLElement($import);

          // We convert the xml into a ElectionFormat class here so that the
          // actual import processes which work on this ElectionFormat object
          // can always be used.  If and when we encounter new file formats we
          // can clone and call a modified variation of this function
          // (readXXXElectionFormat), or, if we are lucky, just clone and call
          // a variation of the following xmltoobject function.
          $this->results = $this->xmltoobject($import);

        }
        else {
          $form_state->setErrorByName('upload', "Could not read file. Error 9001.");
          return FALSE;
        }
      }
      else {
        $form_state->setErrorByName('upload', "Error uploading, could not find this file. Try again or contact Digital Team. Error 9002.");
        return FALSE;
      }
    }
    catch (\Exception $e) {
      $form_state->setErrorByName('upload', "{$e->getMessage()}. Please contact Digital Team. Error 9003.");
      return FALSE;
    }

    return TRUE;
  }

  /**
   * Manager for importing the file contents into the Drupal entites.
   *
   * @param FormState $form_state The submitted form.
   *
   * @return boolean Import success flag.
   */
  public function import(FormStateInterface $form_state) {

    $submitted = $form_state->getValues();

    // Create an array holding all the process data for this election.
    $election = [
      "file" => [
        "data" => $this->results,
        "election_type" => $submitted['election_type'],
        "election_date" => $this->setElectionDate($this->results->election['create']),
        "path" => $this->file_path,
        "fid" => (int) $submitted['upload'][0],
        "areas" => [],
        "is_offical" => $submitted['result_type'],
        "new_election" => FALSE,
        "outcome" => "Success",
      ],
      "taxonomies" => [
        "elections" => [],
        "election_areas" => [],
        "election_contests" => [],
        "elector_groups" => [],
        "election_candidates" => [],
      ],
      "nodes" =>[
        "election_report" => [],
      ],
      "paragraphs" => [
        "election_area_results" => [],
        "election_contest_results" => [],
        "election_candidate_results" => [],
      ],
    ];

    // Evaluate the list of areas defined within the contests.
    $election["file"]["areas"] = $this->results->extractAreas();

    // Load any previous entites
    $this->fetchExistingElection($election, $election["file"]["election_date"]);

    // If this is a new election, then create the basic elements now.
    if ($election["file"]["new_election"]) {
      if ($this->createElection($election)) {
        $this->messenger()->addStatus("A new Election has been created for this file.");
      }
      else {
        return FALSE;
      }
    }

    // Upsert the election results.
    $this->upsertElectionEntities($election);

    // Finally, set the status message for the screen
    if ($election["file"]["outcome"] == "Success") {
      $this->messenger()
        ->addStatus("Success! The Election Results File has been processed and the results updated on the website.");
    }

    // Update the history array in the settings file.
    $config = \Drupal::service('config.factory')->getEditable("node_elections.settings");
    $history = $config->get("history");
    $history[] = [
      "generate_date" => strtotime($this->results->election['create']),
      "upload_date" => strtotime("now"),
      "file" => $election["file"]["fid"],
      "result" => $election["file"]["outcome"],
      "election" => $election["taxonomies"]["elections"]->id(),
      "revision" => $election["nodes"]["election_report"]->getRevisionId(),
    ];
    if (count($history) > 5) {
      unset($history[0]);
      $history = array_values($history);  //reindex so first element is [0].
    }
    $config->set("history", $history);
    $config->set("last-run", end($history)["upload_date"])
      ->save();

  }

  /**
   * Fetch an existing election, including the creation of areas, contests and
   *   candidates etc.
   *
   * @param $election array Array containing the report contents.
   *
   * @return boolean
   */
  protected function fetchExistingElection(array &$election, $election_date) {

    $storage = \Drupal::entityTypeManager()
      ->getStorage("taxonomy_term");

    // First search out the election for this date.
    $current_election = $storage
      ->loadByProperties([
        "vid"=> "elections",
        "field_election_date" => $election_date
      ]);

    if ($current_election) {
      $election["taxonomies"]["elections"] = reset($current_election);
      $election["file"]["new_election"] = FALSE;
    }
    else {
      $election["file"]["new_election"] = TRUE;
      return FALSE;
    }

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
        $election["mapping"][$cont_term->bundle()][$cont_term->get("field_original_id")->getValue()[0]["value"]] =  $cont_id;

        // Load election_candidates from election_contests.
        $cand_terms = $storage->loadByProperties([
          "field_contest" => $cont_id,
          "vid" => "election_candidates",
        ]);
        foreach($cand_terms as $cand_id => $cand_term) {
          $election["taxonomies"][$cand_term->bundle()][$cand_id] = $cand_term;
          $election["mapping"][$cand_term->bundle()][$cand_term->get("field_original_id")->getValue()[0]["value"]] =  $cand_id;
        }
      }
    }

    // load contestgroups from import.
    foreach ($election["file"]["data"]->contestgroups as $cg) {
      // create a mapping for the contestgroups too.
      $eg_term_id = $election["mapping"]["elector_groups"][$cg["egid"]];
      $election["mapping"]["contest_groups"][$cg["conid"]] = $eg_term_id;
    }

    // Load the current revision of the election report (node)
    $node = \Drupal::entityTypeManager()
      ->getStorage("node")
      ->loadByProperties([
        "field_election" => $election_id,
        "revision_default" => 1,
        "type" => "election_report",
      ]);

    if (!$node) {
      // If we cannot get the node, then there has been no sucessful import for
      // this elections term.
      // Remove the existing term, and then mark this as a new import.
      $election["taxonomies"]["elections"]->delete();
      $election["taxonomies"]["elections"] = [];
      $election["file"]["new_election"] = TRUE;
      return FALSE;
    }

    $node = reset($node);
    $election["nodes"]["election_report"] = $node;

    // Now build out the paragraphs which contain the actual results.
    $storage = \Drupal::entityTypeManager()->getStorage('paragraph');
    /**
     * @var $node \Drupal\node\Entity\Node
     */
    if (!empty($node->get("field_area_results"))) {
      foreach ($node->get("field_area_results") as $area_key => $area_target) {
        $para_id = $area_target->get("target_id")->getValue();
        $para = $storage->load($para_id);
        $election["paragraphs"]["election_area_results"][$para_id] = $para;
        $election["mapping"]["election_area_results"][$para->get("field_election_area")
          ->getValue()[0]["target_id"]] = $para_id;

        foreach ($para->get("field_election_contest_results") as $contest_key => $contest_target) {
          $para_id = $contest_target->get("target_id")->getValue();
          $para = $storage->load($para_id);
          $election["paragraphs"]["election_contest_results"][$para_id] = $para;
          $election["mapping"]["election_contest_results"][$para->get("field_election_contest")
            ->getValue()[0]["target_id"]] = $para_id;

          foreach ($para->get("field_candidate_results") as $cand_key => $cand_target) {
            $para_id = $cand_target->get("target_id")->getValue();
            $para = $storage->load($para_id);
            $election["paragraphs"]["election_candidate_results"][$para_id] = $para;
            $election["mapping"]["election_candidate_results"][$para->get("field_election_candidate")
              ->getValue()[0]["target_id"]] = $para_id;
          }

        }

      }
    }

    return TRUE;
  }

  /**
   * Create a new election, essentially this means creating the node for the
   *   election results, and a new entry in the taxonomy for the election
   *   itself.
   *
   * @param $election array Array containing the report contents.
   *
   * @return boolean
   */
  protected function createElection(&$election) {

    $data = $election["file"]["data"];

    $storage = \Drupal::entityTypeManager()
      ->getStorage("taxonomy_term");

    // === CREATE THE BASE TAXONOMY for this election ====
    $election["taxonomies"]["elections"] = $storage
      ->create([
        "name" => $data->election["name"],
        "vid" => "elections",
        "description" => "Results are {$data->election["unofficial"]}",
        "field_display_title" => $data->election["name"],
        "field_election_subtitle" => $data->election["report"],
        "field_election_date" => $election["file"]["election_date"],
      ]);
    $election["taxonomies"]["elections"]->save();

    // - Create a node (election_results) with the filename and dates
    /**
     * @var \Drupal\node\Entity\Node $node
     */
    $storage = \Drupal::entityTypeManager()
      ->getStorage("node");
    $el = ucfirst($election["file"]["election_type"]);
    $election["nodes"]["election_report"] = $storage->create([
      "type" => "election_report",
      "title" => $data->election["name"],
      "field_election" => [
        "target_id" => $election["taxonomies"]["elections"]->id(),
      ],
      "field_election_isofficial" => $election["file"]["is_official"],
      "field_updated_date" => $data->election["progcreate"],
      "field_source_file" => [
        'uri' => \Drupal::service('file_url_generator')->generate($election["file"]["path"])->getUri(),
        'title' => $el . " election on " . $election["file"]["election_date"]
      ],
      "uid" => $this->currentUser()->id(),
      ]);
    // Ensure a new revision is created.
    $election["nodes"]["election_report"]->set("revision_log", "Data for {$data->election["create"]} updated via upload form.");
    try {
      if ($election["nodes"]["election_report"]->save() != SAVED_NEW) {
        throw new EntityStorageException("New 'election_report' node updated when a create was expected.");
      }
    }
    catch (EntityStorageException $e) {
      $this->messenger()->addError(Markup::create("Error updating data in 'election_report' (node). <br> {$e->getMessage()}. Error 9103."));
      $election["file"]["outcome"] = "Failed";
      // rollback if possible.
      if (!empty($election["taxonomies"]["elections"])) {
        try {
          $election["taxonomies"]["elections"]->delete();
          $election["taxonomies"]["elections"] = NULL;
        }
        catch (\Exception $e) {}
      }
      if (!empty($election["nodes"]["election_report"])) {
        try {
          $election["nodes"]["election_report"]->delete();
          $election["nodes"]["election_report"] = NULL;
        }
        catch (\Exception $e) {}
      }
      return FALSE;
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
  protected function upsertElectionEntities(array &$election) {

    $data = $election["file"]["data"];
    /**
     * @var Node $node.
     */
    $node = $election["nodes"]["election_report"];
    $election_id = $election["nodes"]["election_report"]->get("field_election")->getValue()[0]["target_id"];

    // - Update the subtitle for the election in the taxonomy
    $tax = $election["taxonomies"]["elections"];
    if ($tax->get("field_election_subtitle", [])[0]->value != $data->election["report"]) {
      $tax->setNewRevision(FALSE);
      $tax->set("field_election_subtitle", $data->election["report"]);
      try {
        if ($tax->save() != SAVED_UPDATED) {
          throw new EntityStorageException("New 'elections' taxonomy created when update was expected.");
        }
      }
      catch (EntityStorageException $e) {
        $this->messenger()->addError(Markup::create("Error updating data in 'elections' (taxonomy). <br> {$e->getMessage()}. Error 9102."));
        $election["file"]["outcome"] = "Failed";
        return FALSE;
      }
    }

    // Check that we have got enough entries in the taxonomies.
    if (empty($election["taxonomies"]["election_areas"])
      || count($election["taxonomies"]["election_areas"]) != count($election["file"]["areas"])) {
      $result = $this->upsertAreas($election, $election_id);
      if ($result === FALSE) {
        $election["file"]["outcome"] = "Failed";
        return FALSE;
      }
    }
    if (empty($election["taxonomies"]["elector_groups"])
      || count($election["taxonomies"]["elector_groups"]) != count($data->electorgroups)) {
      $result = $this->upsertGroups($election, $election_id);
      if ($result=== FALSE) {
        $election["file"]["outcome"] = "Failed";
        return FALSE;
      }
    }
    if (empty($election["taxonomies"]["election_contests"])
      || count($election["taxonomies"]["election_contests"]) != count($data->contests)) {
      $result = $this->upsertContests($election, $election_id);
      if ($result === FALSE) {
        $election["file"]["outcome"] = "Failed";
        return FALSE;
      }

    }
    $result = $this->upsertContests($election, $election_id);
    if (empty($election["taxonomies"]["election_candidates"])
      || count($election["taxonomies"]["election_candidates"]) != count($data->choices)) {
      $result = $this->upsertCandidates($election, $election_id);
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
    $node->set("field_updated_date", $data->election["progcreate"]);
    $node->set("field_source_file", [
      'uri' => \Drupal::service('file_url_generator')->generate($election["file"]["path"])->getUri(),
      'title' => $el . " election on " . $election["file"]["election_date"]
    ]);
    $node->set("changed", strtotime("Now"));
    $node->set("uid", $this->currentUser()->id());
    // Ensure a new revision is created.
    $node->setNewRevision(TRUE);
    $node->set("revision_log", "Data for {$data->election["create"]} updated via upload form.");
    try {
      if ($node->save() != SAVED_UPDATED) {
        throw new EntityStorageException("New 'election_report' node created when update was expected.");
      }
    }
    catch (EntityStorageException $e) {
      $this->messenger()->addError(Markup::create("Error updating data in 'election_report' (node). <br> {$e->getMessage()}. Error 9103."));
      $election["file"]["outcome"] = "Failed";
      return FALSE;
    }

    // Take each area in update its paragraph.
    if (!$this->upsertAreaResults($election)) {
      $election["file"]["outcome"] = "Failed";
      return FALSE;
    }
    if (!$this->upsertContestResults($election)) {
      $election["file"]["outcome"] = "Failed";
      return FALSE;
    }
    if (!$this->upsertCandidateResults($election)) {
      $election["file"]["outcome"] = "Failed";
      return FALSE;
    }

    return TRUE;
  }

  private function upsertGroups(array &$election, int $id) {

    // There is nothing to change in the Group taxonomy after it has been created
    // for a particular election.

    foreach ($election["file"]["data"]->electorgroups as $group) {

      // Taxonomy is not found, so we need to create it.
      if (!$election["mapping"]["elector_groups"][$group["groupid"]]) {
        $tax = [
          "vid" => "elector_groups",
          "name" => $group["name"],
          "description" => "",
          "field_display_title" => $group["name"],
          "field_original_id" => $group["groupid"],
          "field_is_top" => $group["istop"],
          "field_short_name" => $group["abbreviation"],
          "field_election" => [
            "target_id" => $id,
          ],
        ];

        try {
          $term = Term::create($tax);
          if ($term->save() == SAVED_NEW) {
            $election["taxonomies"]["elector_groups"][] = $term;
            $election["mapping"]["elector_groups"][$group["groupid"]] = $term->id();
          }
          else {
            throw New EntityStorageException("Issue saving new Elector Group term.");
          }
        }
        catch (EntityStorageException $e) {
          $this->messenger()->addError("Error: {$e->getMessage()}. Error 9104");
          return FALSE;
        }

      }

    }

    foreach ($election["file"]["data"]->contestgroups as $cg) {
      // create a mapping for the contestgroups too.
      $eg_term_id = $election["mapping"]["elector_groups"][$cg["egid"]];
      $election["mapping"]["contest_groups"][$cg["conid"]] = $eg_term_id;
    }

    return TRUE;
  }

  private function upsertAreas(array &$election, int $id) {

    // There is nothing to change in the Area taxonomy after it has been created
    // for a particular election.

    foreach ($election["file"]["areas"] as $area) {

      if (!$election["mapping"]["election_areas"][$area["areaid"]]) {
        // This election_areas term is not found, so we need to create it.
        $tax = [
          "vid" => "election_areas",
          "name" => $area["areaname"],
          "description" => "",
          "field_display_title" => $area["areaname"],
          "field_original_id" => $area["areaid"],
          "field_election" => [
            "target_id" => $id,
          ],
        ];

        try {
          $term = Term::create($tax);
          if ($term->save() == SAVED_NEW) {
            $election["taxonomies"]["election_areas"][$term->id()] = $term;
            $election["mapping"]["election_areas"][$area["areaid"]] =  $term->id();
          }
          else {
            throw New EntityStorageException("Issue saving new Area term.");
          }
        }
        catch (EntityStorageException $e) {
          $this->messenger()->addError("Error: {$e->getMessage()}. Error 9105");
          return FALSE;
        }

      }

    }
    return TRUE;
  }

  private function upsertAreaResults(array &$election) {

    $data = $election["file"]["data"];
    $node = $election["nodes"]["election_report"];

    foreach ($data->pollprogress as $area_result) {
      // Find the result and update.
      if (array_key_exists($area_result["areaid"], $election["mapping"]["election_areas"])
        && $term_id = $election["mapping"]["election_areas"][$area_result["areaid"]]) {
        if (empty($election["mapping"]["election_area_results"][$term_id])) {
          try {
            $para_area_result = Paragraph::create([
              "type" => "election_area_results",
              "field_election_area" => $term_id,
              "field_precincts_total" => $area_result["total"],
              "field_precincts_reported" => $area_result["reported"],
            ]);
            $para_area_result->setParentEntity($node, "field_area_results");
            $para_area_result->save();
            $node_array = $node->get("field_area_results");
            $node_array->appendItem([
              "target_id" => $para_area_result->id(),
              "target_revision_id" => $para_area_result->getRevisionId(),
            ]);
            $node->save();
            $election["paragraphs"]["election_area_results"][$para_area_result->id()] = $para_area_result;
            $election["mapping"]["election_area_results"][$term_id] = $para_area_result->id();

          }
          catch (EntityStorageException $e) {
            $this->messenger()
              ->addError("Error: {$e->getMessage()}. Error 9106");
            return FALSE;
          }
        }

        else {
          $para_id = $election["mapping"]["election_area_results"][$term_id];
          $para_area_result = $election["paragraphs"]["election_area_results"][$para_id];
          $para_area_result->set("field_precincts_reported", $area_result["reported"]);
          $para_area_result->set("field_precincts_total", $area_result["total"]);
          try {
            $para_area_result->save();
          }
          catch (EntityStorageException $e) {
            $this->messenger()
              ->addError("Error: {$e->getMessage()}. Error 9107");
            return FALSE;
          }
        }
      }
      else {

      }

    }

    return TRUE;
  }

  private function upsertContests(array &$election, int $id) {

    foreach ($election["file"]["data"]->contests as $contest) {

      if (!array_key_exists($contest["contestid"], $election["mapping"]["election_contests"])) {
        // This election_contests term does not exist, so we need to create it.
        $orig_area_id = $election["file"]["areas"][$contest["areaid"]]["areaid"];
        $area_term_id = $election["mapping"]["election_areas"][$orig_area_id];
        $eg_term_id = $election["mapping"]["contest_groups"][$contest["contestid"]];
        $tax = [
          "vid" => "election_contests",
          "name" => $contest["name"],
          "description" => "",
          "field_display_title" => $contest["name"],
          "field_original_id" => $contest["contestid"],
          "field_contest_eligible" => $contest["eligible"],
          "field_contest_isacclaimed" => $contest["isacclaimed"],
          "field_contest_isdisabled" => $contest["isdisabled"],
          "field_contest_ismajor" => $contest["ismajor"],
          "field_contest_pos" => $contest["pos"],
          "field_contest_sortorder" => $contest["sortorder"],
          "field_has_writeins" => $contest["writeins"],
          "field_elector_group" => [
            "target_id" => $eg_term_id,
          ],
          "field_election" => [
            "target_id" => $id,
          ],
          "field_area" => [
            "target_id" => $area_term_id,
          ],
        ];

        try {
          $term = Term::create($tax);
          if ($term->save() == SAVED_NEW) {
            $election["taxonomies"]["election_contests"][$term->id()] = $term;
            $election["mapping"]["election_contests"][$contest["contestid"]] = $term;
          }
          else {
            throw New EntityStorageException("Issue saving new Contest term.");
          }
        }
        catch (EntityStorageException $e) {
          $this->messenger()->addError("Error: {$e->getMessage()}. Error 9108");
          return FALSE;
        }

      }

    }

    return TRUE;
  }

  private function upsertContestResults(array &$election) {

    $data = $election["file"]["data"];

    // Retrieve the precinct reporting info from pollprogress.
    $area_progress = [];
    foreach ($data->contests as $contest) {
      foreach ($data->pollprogress as $progress) {
        if ($contest["areaid"] == $progress["areaid"]) {
          $area_progress[$contest["contestid"]] = [
            "reported" => $progress["reported"],
            "total" => $progress["total"],
          ];
          break;
        }
      }
    }
    foreach ($data->conteststats as $contest_result) {
      // Find the result and update.
      $contest_term_id = $election["mapping"]["election_contests"][$contest_result["contestid"]];
      if (empty($election["mapping"]["election_contest_results"][$contest_term_id])) {
        try {
          $contest_result_para = Paragraph::create([
            "type" => "election_contest_results",
            "field_election_contest" => ["target_id" => $contest_term_id],
            "field_contest_ballots" => $contest_result["ballots"],
            "field_contest_numvoters" => $contest_result["numvoters"],
            "field_contest_overvotes" => $contest_result["overvotes"],
            "field_contest_undervotes" => $contest_result["undervotes"],
            "field_pushcontests" => $contest_result["pushcontests"],
            "field_precinct_reported" => $area_progress[$contest_result["contestid"]]["reported"],
            "field_precinct_total" => $area_progress[$contest_result["contestid"]]["total"],
          ]);
          // Need to work our way up the tree to find the Parent entity (which
          // is a paragraph type "election_area_results").
          $contest_term = $election["taxonomies"]["election_contests"][$contest_term_id]; // contest taxonomy_term entity
          $area_term_id = $contest_term->get("field_area")->getValue()[0]["target_id"];        // area taxonomy id
          $area_results_id = $election["mapping"]["election_area_results"][$area_term_id];  // area_results paragraph id
          $area_results_para = $election["paragraphs"]["election_area_results"][$area_results_id];  // area_results paragraph entity

          // Step 1: On the new "election_contest_result" paragraph, set the
          // parent entity to be the "election_area_results" paragraph entity.
          $contest_result_para->setParentEntity($area_results_para, "field_election_contest_results");
          $contest_result_para->save();

          // Step 2: On the existing parent ("election_area_result") paragraph,
          // create a new item in the "field_election_contest_results" field.
          $area_results_para
            ->get("field_election_contest_results")
            ->appendItem([
              "target_id" => $contest_result_para->id(),
              "target_revision_id" => $contest_result_para->getRevisionId(),
            ]);
          $area_results_para->save();

          // Update the $elections object with create entity and its map.
          $election["paragraphs"]["election_contest_results"][$contest_result_para->id()] = $contest_result_para;
          $election["mapping"]["election_contest_results"][$contest_term_id] =  $contest_result_para->id();

        }
        catch (EntityStorageException $e) {
          $this->messenger()->addError("Error: {$e->getMessage()}. Error 9109");
          return FALSE;
        }
      }

      else {
        $para_id = $election["mapping"]["election_contest_results"][$contest_term_id];
        $contest_result_para = $election["paragraphs"]["election_contest_results"][$para_id];
        $contest_result_para->set("field_contest_ballots", $contest_result["ballots"]);
        $contest_result_para->set("field_contest_numvoters", $contest_result["numvoters"]);
        $contest_result_para->set("field_contest_overvotes", $contest_result["overvotes"]);
        $contest_result_para->set("field_contest_undervotes", $contest_result["undervotes"]);
        $contest_result_para->set("field_contest_numvoters", $contest_result["pushcontests"]);
        $contest_result_para->set("field_precinct_reported", $area_progress[$contest_result["contestid"]]["reported"]);
        $contest_result_para->set("field_precinct_total", $area_progress[$contest_result["contestid"]]["total"]);
        try {
          $contest_result_para->save();
        }
        catch (EntityStorageException $e) {
          $this->messenger()->addError("Error: {$e->getMessage()}. Error 9110");
          return FALSE;
        }
      }

    }

    return TRUE;
  }

  private function upsertCandidates(array &$election, int $id) {
    // TODO: is the anything to change in the Candidate taxonomy after it has
    //   been created for a particular election.

    foreach ($election["file"]["data"]->choices as $choice) {

      // Find this area in the taxonomy (or don't).

      if (!isset($election["mapping"]["election_candidates"])
        || !array_key_exists($choice["chid"], $election["mapping"]["election_candidates"])) {
        // Taxonomy is not found, so we need to create it.
        $contest = $election["mapping"]["election_contests"][$choice["conid"]];
        $tax = [
          "vid" => "election_candidates",
          "name" => $choice["name"],
          "description" => "",
          "field_display_title" => $choice["name"],
          "field_original_id" => $choice["chid"],

          "field_candidate_dis" => $choice["dis"],
          "field_candidate_showvotes" => $choice["showvotes"],
          "field_candidate_wri" => $choice["wri"],
          "field_candidate_wrind" => $choice["wrind"],

          "field_contest" => [
            "target_id" => $contest,
          ],
        ];

        try {
          $term = Term::create($tax);
          if ($term->save() == SAVED_NEW) {
            $election["taxonomies"]["election_candidates"][] = $term->id();
            $election["mapping"]["election_candidates"][$choice["chid"]] = $term;
          }
          else {
            throw New EntityStorageException("Issue saving new Candidate term.");
          }
        }
        catch (EntityStorageException $e) {
          $this->messenger()->addError("Error: {$e->getMessage()}. Error 9111");
          return FALSE;
        }
      }

    }

    return TRUE;
  }

  private function upsertCandidateResults(array &$election) {

    $data = $election["file"]["data"];

    // Calculate and save the total votes counted per contest.
    $contest_count = [];
    foreach ($data->results as $candidate_result) {
      if (!array_key_exists($candidate_result["contid"], $contest_count)) {
        $contest_count[$candidate_result["contid"]] = intval($candidate_result["vot"]);
      }
      else {
        $contest_count[$candidate_result["contid"]] += intval($candidate_result["vot"]);
      }
    }

    // Process candidate/choice results.
    foreach ($data->results as $candidate_result) {
      // Work out the percentage for this candidate/choice.
      $pct = round(intval($candidate_result["vot"]) / $contest_count[$candidate_result["contid"]], 4);
      // Find the result and update.
      $cand_term_id = $election["mapping"]["election_candidates"][$candidate_result["chid"]];
      if (empty($election["mapping"]["election_candidate_results"][$cand_term_id])) {
        try {
          $candidate_result_para = Paragraph::create([
            "type" => "election_candidate_results",
            "field_election_candidate" => ["target_id" => $cand_term_id],
            "field_candidate_prtid" => $candidate_result["prtid"],
            "field_candidate_vot" => $candidate_result["vot"],
            "field_candidate_wrind" => $candidate_result["wrind"],
            "field_calc_percent" => $pct,
          ]);
          // Need to work our way up the tree to find the Parent entity (which
          // is a paragraph type "election_contest_results").
          $contest_term_id = $election["mapping"]["election_contests"][$candidate_result["contid"]];
          $contest_results_id = $election["mapping"]["election_contest_results"][$contest_term_id];
          $contest_results_para = $election["paragraphs"]["election_contest_results"][$contest_results_id];

          // Step 1: On the new "election_candidate_result" paragraph, set the
          // parent entity to be the "election_contest_results" paragraph entity.
          $candidate_result_para->setParentEntity($contest_results_para, "field_candidate_results");
          $candidate_result_para->save();

          // Step 2: On the existing parent ("election_contest_result") paragraph,
          // create a new item in the "field_election_contest_results" field.
          $contest_results_para
            ->get("field_candidate_results")
            ->appendItem([
              "target_id" => $candidate_result_para->id(),
              "target_revision_id" => $candidate_result_para->getRevisionId()
            ]);
          $contest_results_para->save();

          // Update the $elections object with create entity and its map.
          $election["paragraphs"]["election_area_results"][$candidate_result_para->id()] = $candidate_result_para;
          $election["mapping"]["election_area_results"][$cand_term_id] =  $candidate_result_para->id();

        }
        catch (EntityStorageException $e) {
          $this->messenger()->addError("Error: {$e->getMessage()}. Error 9112");
          return FALSE;
        }
      }

      else {
        $para_id = $election["mapping"]["election_candidate_results"][$cand_term_id];
        $candidate_result_para = $election["paragraphs"]["election_candidate_results"][$para_id];
        $candidate_result_para->set("field_candidate_prtid", $candidate_result["prtid"]);
        $candidate_result_para->set("field_candidate_vot", $candidate_result["vot"]);
        $candidate_result_para->set("field_candidate_wrind", $candidate_result["wrind"]);
        $candidate_result_para->set("field_calc_percent", $pct);

        try {
          $candidate_result_para->save();
        }
        catch (EntityStorageException $e) {
          $this->messenger()->addError("Error: {$e->getMessage()}. Error 9113");
          return FALSE;
        }
      }

    }

    return TRUE;

  }

  /**
   * Calculate the Election date from the report creation date.
   *
   * @param string|int $date
   *
   * @return int
   */
  private function setElectionDate(string|int $date) {

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

  /**
   * Converts the default xml file format for elections reports from XML into
   * an object.
   * This function could be cloned and adapted for other file formats or
   * protocols e.g. json or csv.  The caller
   *
   * @param \SimpleXMLElement $xml XML object to convert.
   *
   * @return ElectionResults Converted object.
   */
  private function xmltoobject(\SimpleXMLElement $xml) {

    $output = new ElectionResults();

    $map = [
      "choices" => "choices",
      "contests" => "contests",
      "conteststats" => "conteststats",
      "electorgroups" => "electorgroups",
      "contestgroups" => "contestgroups",
      "parties" => "parties",
      "pollprogress" => "pollprogress",
      "results" => "results",
      "settings" => "settings",
    ];

    foreach($xml->Report_Info->attributes() as $key => $value) {
      $output->addField("election", [
        "name" => (string) strtolower($key),
        "value" => (string) $value
      ]);
    }
    $prog_date = strtotime($output->election["create"]);
    $prog_date = date("Y-m-d\TH:i:s", $prog_date);
    $output->addField("election", [
      "name" => "progcreate" ,
      "value" => $prog_date
    ]);
    foreach($xml->Terminology->attributes() as $key => $value) {
      $output->addField("terminology", [
        "name" => (string) strtolower($key),
        "value" => (string) $value
      ]);
    }

    foreach ($xml as $base_element_name => $base_element) {
      if (in_array($base_element_name, $map)) {
        $id = 0;
        foreach ($base_element as $sub_element) {
          $sub = [];

          foreach ($sub_element->attributes() as $key => $value) {
            $sub[strtolower((string) $key)] = (string) $value;
          }

          $output->addField($map[$base_element_name], [
            "name" => $id,
            "value" => $sub,
          ]);
          $id += 1;
        }
      }
    }

    return $output;

  }

}

class ElectionResults {

  public $areas = [];

  public $election;

  public function create() {
    $election = new \stdClass();
    $election->election = [];
    $election->choices = new \stdClass();
    $election->contests = new \stdClass();
    $election->conteststats = new \stdClass();
    $election->electorgroups = new \stdClass();
    $election->parties = new \stdClass();
    $election->pollprogress = new \stdClass();
    $election->results = new \stdClass();
    $election->settings = new \stdClass();
    $election->terminology = new \stdClass();
    $this->election = $election;
  }

  public function addField(string $fieldType, array $field) {
    $this->{$fieldType}[strtolower($field["name"])] = $field["value"];
  }

  /**
   * Evaluate the list of areas defined within the contests.
   *
   * @param array|NULL $contests An array of contests (optional).
   *
   * @return array The array of areas.
   */
  public function extractAreas(array $contests = NULL) {
    // Use the class contests if an array is not passed.
    if (!$contests) {
      $contests = $this->contests;
    }
    // Find the area field in the contest and build new array.
    foreach ($contests as $contest) {
      $this->areas[$contest["areaid"]] = [
        "areaid" => $contest["areaid"],
        "areaname" => $contest["areaname"],
      ];
    }

    return $this->areas;

  }

}
