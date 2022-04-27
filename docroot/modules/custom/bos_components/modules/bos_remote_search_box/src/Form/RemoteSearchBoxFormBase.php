<?php

namespace Drupal\bos_remote_search_box\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;
use Drupal\bos_remote_search_box\Util\RemoteSearchBoxHelper as helper;

class RemoteSearchBoxFormBase extends FormBase {

  public $form_title = "";

  /**
   * @var array Stores the submitted search form fields in flattened state.
   * (sort of like a posted form)
   */
  protected $submitted_form = [];

  /**
   * @var array Array of results from the remote system.
   */
  protected $dataset = [];

  /**
   * @var array Array of plain text strings, each element is an error
   * encountered.
   */
  public $errors = [];

  /**
   * @inheritDoc
   */
  public function getFormId() { }

  /**
   * @inheritDoc
   */
  function __construct() { }

  /**
   * @inheritDoc
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    // Create and return the form stub.
    helper::makeFormStub($form, $this);

    return $form;

  }

  /**
   * @inheritDoc
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    // Extract a more manageable array of submitted values.
    // Saves the flattened array in $this->submitted_form.
    $this->errors = [];
    $this->submitted_form = [];
    $this->flattenArray($form_state->getValues(), $this->submitted_form);

    // Validate the search text box
    if ($array = $this->deepFind("searchbox", $form)) {
      if (isset($array["#attributes"]["bundle"]) && $array["#attributes"]["bundle"] == $this->getFormId()) {
        if (strlen($this->submitted_form["searchbox"]) > 0 && strlen($this->submitted_form["searchbox"]) < 4) {
          $form_state->setErrorByName("searchbox","Search must be more than 4 characters");
        }
      }
    }

    // Call back to the custom search class for customized validation.
    $this->validateSearch($form, $form_state);

    // Redirect errors into the error container.
    $errs = $form_state->getErrors();
    if (!empty($errs)) {
      helper::addSearchResults($form, $errs, helper::RESULTS_ERRORS);
      \Drupal::messenger()->deleteAll();
    }

  }

  /**
   * @inheritDoc
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Extract a more manageable array of submitted values.
    // Saves the flattened array in $this->submitted_form.
    $this->dataset = [];

    // Query remote service
    $form_state->setRebuild(TRUE);
    $this->submitToRemote($form, $form_state);

  }

  /**
   * Handles the AJAX callack when the search button is clicked on the form.
   * This is called after submitform().
   *
   * @param array $form
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *
   * @return array An updated form to display on AJAX return.
   */
  public function searchButtonCallback(array &$form, FormStateInterface $form_state) {
    /*
     * Implement
     *    function buildResponseForm($form, $form_state)
     * in your custom search class, and place all cusom code in there to format
     * the search output / errors.
     */

    // Prepare the form to accomodate the search results
    if (!$form_state->getErrors()) {

      $this->prepResponseForm($form, $form_state);

      // Re-configure the form based on search results.
      $this->buildSearchResults($form, $form_state);

    }

    return $form;

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
   */
  static public function prepResponseForm(array &$form, FormStateInterface $form_state) {
    // ToDo: hide various search sections on the form.

    // Reset any results/errors already on the form
    helper::clearForm($form);

  }

}
