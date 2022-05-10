<?php

namespace Drupal\bos_remote_search_box\Form;

use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\ReplaceCommand;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;
use Drupal\bos_remote_search_box\Util\RemoteSearchBoxHelper as helper;

class RemoteSearchBoxFormBase extends FormBase {

  public $form_title = "";

  /**
   * @var array Stores the user_info.
   */
  protected $user_input;

  /**
   * @var array Array of plain text strings, each element is an error
   * encountered.
   */
  public $errors = [];

  /**
   * @var \Drupal\paragraphs\Entity\Paragraph which holds current search box
   */
  protected $paragraph;

  /**
   * @var string|int Flag to keep track of what stage a multistage form is at.
   */
  protected $step;

  /**
   * @inheritDoc
   */
  public function getFormId() { }

  /**
   * @inheritDoc
   */
  function __construct() {
    if ($para_id = \Drupal::request()->getSession()->get("paragraph", FALSE)) {
      $this->paragraph = \Drupal::entityTypeManager()
        ->getStorage("paragraph")
        ->load($para_id);
    }
    // Default the initial step if multi-step.
    $this->step = "initial";
  }

  /**
   * @inheritDoc
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    // Determine the step we are on, so we can quickly refer to this flag
    // in the class.
    if (!empty($form_state->getUserInput())) {
      $this->user_input = $form_state->getUserInput();
      if (!empty($this->user_input["_triggering_element_name"]) && $this->user_input["_triggering_element_name"] == "op") {
        $this->step = strtolower($this->user_input["_triggering_element_value"]);
      }
      elseif (!empty($this->user_input["op"])) {
        $this->step = strtolower($this->user_input["op"]);
      }
      else {
        // Custom class can interrogate the user input to see what step is valid.
        $this->step = NULL;
      }
      // Extract a more manageable array of submitted values.
      // Saves the flattened array in $this->user_info.
      if (!empty($this->user_input) && !empty($this->user_input["search_criteria_wrapper"])) {
        $this->flattenArray([$this->user_input["search_criteria_wrapper"]], $this->user_input);
      }
    }
    else {
      $this->step = "initial";
    }

    // Create and return the form stub.
    helper::makeFormStub($form, $this);

    return $form;

  }

  /**
   * @inheritDoc
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $this->errors = [];

    // Validate the search text box.
    // Searchbox is not a required field (multi-step issues), so manually
    // validate here.
    if ($this->step == "search") {
      if (empty($this->user_input['searchbox'])) {
        $form_state->setErrorByName("search_criteria_wrapper][search][searchbox", t("Street to search for field is required."));
      }
      else {
        if (strlen($this->user_input["searchbox"]) > 0 && strlen($this->user_input["searchbox"]) < 4) {
          $form_state->setErrorByName("search_criteria_wrapper][search][searchbox", t("Search must be more than 4 characters"));
        }
      }
    }

    // Call back to the custom search class for customized validation.
    $this->validateSearch($form, $form_state);

    // Redirect errors into the error container.
    $errs = $form_state->getErrors();
    if (!empty($errs)) {
      helper::addSearchResults($form, $errs, helper::RESULTS_ERROR);
      \Drupal::messenger()->deleteAll();
    }

  }

  /**
   * @inheritDoc
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

    if (!empty($form_state->getUserInput()["_triggering_element_name"]) && $form_state->getUserInput()["_triggering_element_name"] == "op") {
      // We don't need to do anything here because the submit process we want
      // is contained in the AJAX callback ($this->searchButtonCallback).
      // Clear out any previous results datasets.
      $build_info = $form_state->getBuildInfo();
      $build_info["dataset"] = [];
      $form_state->setBuildInfo($build_info);
      return;
    }

    // This form submit will be the result of some clickable link or button on
    // the form.  Most likely from the results listing.
    // Usually this will be a second cycle to try to retrieve the actual record
    // which was clicked upon.
    if ($this->step == "search") {
      $form['search_criteria_wrapper']['record']['#dataset'] = NULL;
      $form = $this->buildRecord($form, $form_state);
    }

  }

  /**
   * Handles the AJAX callack when the search button is clicked on the form.
   * This is called after submitform().
   *
   * @param array $form
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *
   * @return \Drupal\Core\Ajax\AjaxResponse An updated form to display on AJAX return.
   */
  public function searchButtonCallback(array &$form, FormStateInterface $form_state) {
    /*
     * Implement
     *    function buildResponseForm($form, $form_state)
     * in your custom search class, and place all cusom code in there to format
     * the search output / errors.
     */

    if ($form_state->getErrors()) {
      return $form;
    }

    $build_info = $form_state->getBuildInfo();
    $build_info["dataset"] = [];
    $form_state->setBuildInfo($build_info);

    $form['search_criteria_wrapper']['results']['#dataset'] = NULL;

    // Query remote service
    $this->submitToRemote($form, $form_state);

    // Prepare the form to accomodate the search results
    if ($this->getErrors() || $form_state->getErrors()) {
      if ($this->getErrors()) {
        helper::addSearchResults($form, $this->getErrors(), helper::RESULTS_ERROR);
        \Drupal::messenger()->deleteAll();
        return $form;
      }
    }
    else  {

      // Set up the form based on search results.
      $this->prepResponseForm($form, $form_state);

      // Now pass back to the custom class to finish off the presentation of
      // the results.
      $this->buildSearchResults($form, $form_state);

      $form_state->setRebuild(TRUE);
      return $form;
    }

  }

  /**
   * This function is called from
   * \Drupal\bos_remote_search_box\Controller\RemoteSearchBoxCallback::processor
   *
   * It extracts the querystring places into an array and then calls the endpoint
   * function in the custom form class.
   *
   * @param $action string The action part of the url.
   *
   * @return \http\Client\Response a response to return to caller.
   */
  public function linkCallback($action) {
    // Extract the querystring portion of the request.
    $query = [];
    if (\Drupal::request()->getQueryString()) {
      foreach(explode("&", \Drupal::request()->getQueryString()) as $querypair) {
        $q = explode("=", $querypair,2);
        $query[urldecode($q[0])] = urldecode($q[1]);
      }
    }
    return $this->endpoint($action, $query);
  }

  /**
   * Fetch the component instance class name.
   *
   * @return mixed|null
   */
  public function getClassName() {
    if (isset($this->paragraph)) {
      return $this->paragraph->get("field_remote_search_control")->getValue()[0]["value"];
    }
    return NULL;
  }

  /**
   * Fetch the component Results header/intro text.
   *
   * @return mixed|null
   */
  public function getResultsText() {
    if (isset($this->paragraph)) {
      return trim($this->paragraph->get("field_message")->getValue()[0]["value"], "\r\n");
    }
    return NULL;
  }

  /**
   * Fetch the component instance class name.
   *
   * @return mixed|null
   */
  public function getNoResultMessage() {
    if (isset($this->paragraph)) {
      return trim($this->paragraph->get("field_rsb_no_results_message")->getValue()[0]["value"], "\r\n");
    }
    return NULL;
  }

  /**********************
   * INTERNAL HELPER FUNCTIONS
   */

  /**
   * Searches a Drupal render-style array looking for a particular child
   * element.
   *
   * @param string $string (needle) the element to be found
   * @param array $array (haystack) a Drupal render object (e.g a Form)
   *
   * @return false|mixed
   */
  private function deepFind(string $string, array $array) {
    if ($children = Element::children($array)) {
      if (in_array($string, $children)) {
        return $array[$string];
      }
      foreach ($children as $child) {
        if ($out = $this->deepFind($string, $array[$child])) {
          return $out;
        }
      }
    }
    return FALSE;
  }

  /**
   * This function takes an array of nested form values and flattens them out,
   * returning the result in the $flattened variable/parameter.
   *
   * @param array $array original nested array.
   * @param array $flattened the array flattened down to be easier to handle.
   *
   * @return array An flattened array of submitted form values.
   */
  private function flattenArray(array $array, array &$flattened = [], $parent = "") {
    foreach ($array as $key => $child) {
      if (is_object($child) || empty($child)) {
      }
      elseif (is_array($child)) {
        $this->flattenArray($child, $flattened, $key);
      }
      else {
        if (!empty($parent) && $key == $child) {
          $a = $parent;
        } else {
          $a = $key;
        }

        if (empty($flattened[$a])) {
          $flattened[$a] = $child;
        }
        else {
          if (is_array($flattened[$a])) {
            $flattened[$a][] = $child;
          }
          else {
            $flattened[$a] = array_merge([$flattened[$a]], [$child]);
          }
        }
      }
    }
  }

  /**
   * Hides the search area and prepares to display the results.
   *
   * @param array $form
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *
   * @return array An flattened array of submitted form values.
   *
   * @see: https://www.drupal.org/docs/drupal-apis/javascript-api/ajax-forms
   * @see: https://www.drupal.org/docs/drupal-apis/form-api/conditional-form-fields
   */
  private function prepResponseForm(array &$form, FormStateInterface $form_state) {
    // Reset any results/errors already on the form
    helper::clearForm($form);

    // Inject content-managed results text depedned on results returned.
    if (empty($form_state->getBuildInfo()["dataset"])) {
      // No results found... inject the default no results text.
      $fmtResults = [
        "#type" => "markup",
        '#markup' => "<div class='supporting-text'><div class='t--req'>" . $this->getNoResultMessage() . "</div></div>",
        '#allowed_tags' => ['div'],
      ];
    }
    else {
      $fmtResults = [
        "#type" => "markup",
        '#markup' => "<div class='supporting-text'><div class='t--ob'>" . $this->getResultsText() . "</div></div>",
        '#allowed_tags' => ['div'],
      ];
    }
    helper::addSearchResults($form, $fmtResults, helper::RESULTS_SUMMARY);

    // Re-configure the form based on search results.
    if (!empty($form_state->getBuildInfo()["dataset"])) {
      $form = NestedArray::mergeDeep($form, ["search_criteria_wrapper" => ["search" => ["searchbox" => ['#attributes' => ["disabled" => ""]]]]]);
      $form = NestedArray::mergeDeep($form, ["search_criteria_wrapper" => ["search" => ["search_filters" => ['#attributes' => ["class" => ["visually-hidden"]]]]]]);
      $form = NestedArray::mergeDeep($form, ["search_criteria_wrapper" => ["search" => ["search_button" => ['#attributes' => ["disabled" => ""]]]]]);
    }


  }

  /**
   * Fetch any errors.
   *
   * @return array|false Array of errors, or FALSE.
   */
  protected function getErrors() {
    if ($this->errors === []) {
      return FALSE;
    }
    else {
      return $this->errors;
    }
  }
}
