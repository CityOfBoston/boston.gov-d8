<?php

namespace Drupal\bos_remote_search_box\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;

class RemoteSearchBoxFormBase extends FormBase {

  protected $form_name = "";
  protected $bundle = "remote-search-box";

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
  protected $errors = [];

  /**
   * Constants used to indicated Remote System responses to be returned to the
   * form.
   */
  const RESULTS_SUMMARY = 0;
  const RESULTS_DATASET = 1;
  const RESULTS_ERRORS = 2;

  /**
   * @inheritDoc
   */
  public function getFormId() { }

  /**
   * @inheritDoc
   */
  function __construct() {
    $this->bundle = $this->getFormId();
  }

  /**
   * @inheritDoc
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    // This function will return a basic search box, configured for an address
    // lookup.
    // In the extending class, you can alter the form
    $form['#attributes']['onsubmit'] = 'return false';
    $form = array_merge($form, [
      "#theme" => "remote_search_box",
      "#title" => $this->form_name,
      '#tree' => TRUE,
      '#attributes' => array_merge($form["#attributes"], [
        'class' => [
          $this->getFormId()
        ],
        'bundle' => $this->bundle,
      ]),
      'search_criteria_wrapper' => [
        '#type' => 'container',
        '#attributes' => [
          'class' => [
            $this->getFormId() . '-container',
          ],
          'bundle' => $this->bundle,
        ],
        'search' => [
          '#tree' => TRUE,
          '#type' => 'container',
          '#weight' => -10,
          '#attributes' => [
            'class' => [
              'co',
              'sf',
            ],
            'bundle' => $this->bundle,
          ],
        ],
        'other_criteria' => [
          '#weight' => 2,
        ],
        'search_button' => [
          '#type' => 'submit',
          '#value' => t('Search'),
          '#weight' => 10,
          '#ajax' => [ // @see https://www.drupal.org/docs/drupal-apis/javascript-api/ajax-forms
            'callback' => [$this, "searchButtonCallback"], // put callback code here @see https://www.drupal.org/docs/drupal-apis/javascript-api/ajax-forms#s-ajax-commands-ajaxresponse
            'disable-refocus' => FALSE,
            'event' => 'click',
            'progress' => ['type' => 'throbber'],
            'wrapper' => 'street-sweeping-lookup',  // this element is updated with ajax results
          ],
          '#attributes' => [
            'class' => [
              'form__button--bos-remote_search_box',
              'button--submit',
            ],
            'bundle' => $this->bundle,
          ],
        ],
        'errors' => [
          '#type' => 'container',
          '#weight' => 11,
          '#attributes' => [
            'class' => [
              'remote-search-box-error'
            ],
            'id' => [
              'remote-search-box-error'
            ],
            'bundle' => $this->bundle,
          ]
        ],
        'results' => [
          '#type' => 'container',
          '#weight' => 12,
          '#attributes' => [
            'class' => [
              'remote-search-box-results'
            ],
            'id' => [
              'remote-search-box-results'
            ],
            'bundle' => $this->bundle,
          ]
        ],
      ],
    ]);

    return $form;
  }

  /**
   * @inheritDoc
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Extract a more manageable array of submitted values.
    // Saves the flattened array in $this->submitted_form.
    $this->dataset = [];
    $this->flattenArray($form_state->getValues(), $this->submitted_form);
  }

  /**
   * @inheritDoc
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    // Extract a more manageable array of submitted values.
    // Saves the flattened array in $this->submitted_form.
    $this->errors = [];
    $this->flattenArray($form_state->getValues(), $this->submitted_form);

    // Validate the search text box
    if ($array = $this->deepFind("searchbox", $form)) {
      if (isset($array["#attributes"]["bundle"]) && $array["#attributes"]["bundle"] == $this->bundle) {
        if (strlen($this->submitted_form["searchbox"]) < 4) {
          $form_state->setErrorByName("searchbox","Search must be more than 4 characters");
        }
      }
    }

    // Redirect errors into the error container.
    if (FALSE) {
      $errs = $form_state->getErrors();
      $err_str = "";
      while (!empty($errs)) {
        $err = array_pop($errs);
        if (is_array($err)) {
          $err = $err->render();
        }
        $err_str .= ($err . "<br/>");
      }
      if (!empty($err_str)) {
        $form["search_criteria_wrapper"]["errors"]["#markup"] = $err_str;
        \Drupal::messenger()->deleteAll();
      }
    }

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
   * This function takes an array of nested form values and flattenes them out.
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
          $flattened[$parent] = $child;
        } else {
          $flattened[$key] = $child;
        }
      }
    }
  }

  /************************
   * FORM ELEMENT BUILDERS
   *
   * Use the following naming conventions:
   *
   * For the main search text box that will be used in a form, use the convention:
   *      buildXxxxSearch() - where Xxxx is a name like User / Address / Dog / Plate etc
   *
   * For additional form elements which may be optional or required, use:
   *      criteriaXxxxYyyy() - where Xxxx describes the search content (day / region etc)
   *                            and Yyyy describes the input method (select / Checkbox etc)
   */

  /**
   * Adds an address search textbox as the main search element in the form
   * elements array previously built from stub at buildForm()..
   *
   * @see https://api.drupal.org/api/drupal/elements
   *
   * @param $form array The partially completed form array built from stub at buildForm().
   *
   * @return array updated form elements array
   */
  protected function buildAddressSearch($form, bool $required = TRUE) {
    $form['search_criteria_wrapper']['search'] = array_merge(
      $form['search_criteria_wrapper']['search'],
      [
        'searchbox' => [
          '#type' => 'textfield',
          '#title' => $this->t('Address, Neighborhood or Intersection'),
          '#description' => "",
          '#attributes' => [
            'class' => [
              'form__input--bos-remote_search_box',
              'sf--md',
              "sf-i-f",
            ],
            'bundle' => $this->bundle,
            'placeholder' => t('Enter Address to Lookup'),
            'autofocus' => '',
          ],
          '#size' => 100,
          '#maxlength' => 100,
          '#required' => $required,
        ],
      ]);
    return $form;
  }

  /**
   * Adds an address search textbox as the main search element in the form
   * elements array previously built from stub at buildForm()..
   *
   * @see https://api.drupal.org/api/drupal/elements
   *
   * @param $form array The partially completed form array built from stub at buildForm().
   *
   * @return array updated form elements array
   */
  protected function buildAutoFillAddressSearch($form, bool $required = TRUE) {
    $form['search_criteria_wrapper']['search'] = array_merge(
      $form['search_criteria_wrapper']['search'],
      [
        'searchlabel' => [
          '#type' => 'label',
          '#title' => $this->t('Address, Neighborhood or Intersection'),
          '#attributes' => [
            'bundle' => $this->bundle,
            'class' => ['txt-l'],
          ],
        ],
        'searchbox' => [
          '#type' => 'textfield',
          '#title' => $this->t('Address, Neighborhood or Intersection'),
          '#description' => "",
          '#ajax' => [ // @see https://www.drupal.org/docs/drupal-apis/javascript-api/ajax-forms
            'callback' => [$this, "searchTextBoxCallback"], // put callback code here @see https://www.drupal.org/docs/drupal-apis/javascript-api/ajax-forms#s-ajax-commands-ajaxresponse
            'disable-refocus' => FALSE,
            'event' => 'change',
            'progress' => ['type' => 'throbber'],
            'wrapper' => 'edit-output',  // this element is updated with ajax results
          ],
          '#attributes' => [
            'class' => [
              'form__input--bos-remote_search_box',
              'sf-i-f'
            ],
            'bundle' => $this->bundle,
            'placeholder' => t('Enter Address to Lookup'),
            'autofocus' => '',
          ],
          '#size' => 100,
          '#maxlength' => 100,
          '#required' => $required,
//          '#theme' => self::getFormId(),
        ],
        'searchicon' => [
          '#type' => 'button',
          '#attributes' => [
            'class' => [
              'sf-i-b',
            ],
            'bundle' => $this->bundle,
            'tabindex' => -1,
          ],
        ],
      ]);
    return $form;
  }

  /**
   * Adds additional criteria selection form elements into the form array.
   *
   * @see https://api.drupal.org/api/drupal/elements
   *
   * @param $form array The partially completed form array built from stub at buildForm().
   * @param $criteria array A properly formatted form array containing additional search elements
   *
   * @return array updated form elements array
   */
  protected function addManualCriteria(array $form, array $criteria) {
    $form['search_criteria_wrapper']['other_criteria'] = array_merge(
      $form['search_criteria_wrapper']['other_criteria'],
      $criteria
    );
    return $form;
  }

  /**
   * Adds a neighborhood lookup (select) to the form.
   * The neighborhoods are populated from the neighborhood taxonomy.
   *
   * @param int $weight This controls the placement of the element in the list
   *
   * @return array updated form elements array
   */
  protected function criteriaNeighborhoodSelect(int $weight = 0, bool $required = FALSE) {
    $neighborhoods = \Drupal::entityTypeManager()->getStorage("taxonomy_term")->loadTree('neighborhoods');
    $options = [];
    foreach ($neighborhoods as $neighborhood) {
      $sanitized_name = $this->t($neighborhood->name)->render();
      $options[$sanitized_name] = $sanitized_name;
    }

    $form = [
      'neighborhood' => [
        '#type' => 'select',
        '#weight' => $weight,
        '#title' => $this->t('District/Neighborhood (optional)'),
        '#required' => $required,
        '#attributes' => [
          'class' => [
            'sel-f',
            'sel-f--fw',
            'form__input--bos-remote_search_box',
          ],
          'bundle' => $this->bundle,
        ],
        '#options' => $options,
        '#empty_option' => 'Your Neighborhood (optional)',
      ],
    ];
    return $form;
  }

  /**
   * Adds a checkbox group for week days to the form.
   *
   * @param int $weight This controls the placement of the element in the list
   *
   * @return array updated form elements array
   */
  protected function criteriaWeekdayCheckbox(int $weight = 0, bool $required = FALSE) {
    $form = [
      'day' => [
        '#type' => 'checkboxes',
        "#label" => $this->t('Day (optional)'),
        '#weight' => $weight,
        '#required' => $required,
        '#attributes' => [
          'class' => [
            'cb-f',
          ],
          'bundle' => $this->bundle,
        ],
        '#options' => [
          'MON' => $this->t('Monday'),
          'TUE' => $this->t('Tuesday'),
          'WED' => $this->t('Wednesday'),
          'THU' => $this->t('Thursday'),
          'FRI' => $this->t('Friday'),
          'SAT' => $this->t('Saturday'),
          'SUN' => $this->t('Sunday'),
        ],
      ],
    ];
    return $form;
  }

  /**
   * Hides the search area and prepares to display the results.
   *
   * @param array $form
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *
   * @return array An flattened array of submitted form values.
   */
  protected function prepResponseForm(array &$form, FormStateInterface $form_state) {
  }

  /**
   * Inserts a form api object into the $form array in the correct place to
   * display search results.
   *
   * @param array $form The form.
   * @param array $resultElements an array using acceptable Forms API elements
   *
   * @return void
   */
  protected function buildSearchResults(array &$form, array $resultElements, int $type = self::RESULTS_DATASET) {
    switch ($type) {

      case self::RESULTS_SUMMARY:
        $form['search_criteria_wrapper']['results']['output'] = $resultElements;
        break;

      case self::RESULTS_DATASET:
        $form['search_criteria_wrapper']['results']['#dataset'] = json_encode($resultElements);
        break;

      case self::RESULTS_ERRORS:
        foreach ($resultElements as $errorElement) {
          if (!isset($form['search_criteria_wrapper']['errors']['#items'])) {
            $form['search_criteria_wrapper']['errors']['#items'] = [];
          }
          $form['search_criteria_wrapper']['errors']['#items'][] = [
            "#markup" => $errorElement
          ];
        }
        break;
    }
  }

}
