<?php

namespace Drupal\bos_remote_search_box\Util;

use Drupal\bos_remote_search_box\Form\RemoteSearchBoxFormBase;
use Drupal\Core\Form\FormStateInterface;

class RemoteSearchBoxHelper {

  /**
   * Constants used to indicated Remote System responses to be returned to the
   * form.
   */
  const RESULTS_SUMMARY = 0;
  const RESULTS_DATASET = 1;
  const RESULTS_ERRORS = 2;

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
   * Return the stub of a form for use by the module.
   * This stub can be extended by other functions in this class.
   *
   * @param $form
   * @param \Drupal\bos_remote_search_box\Form\RemoteSearchBoxFormBase $cb
   *
   * @return void
   */
  static public function makeFormStub(&$form, RemoteSearchBoxFormBase $cb) {

    $form['#attributes']['onsubmit'] = 'return false';

    $form = array_merge($form, [
      "#theme" => "remote_search_box",
      "#title" => $cb->form_title,
      '#tree' => TRUE,
      '#attributes' => array_merge($form["#attributes"], [
        'class' => [
          $cb->getFormId()
        ],
        'id' => $cb->getFormId(),
        'bundle' => $cb->getFormId(),
      ]),
      'search_criteria_wrapper' => [
        '#type' => 'container',
        '#attributes' => [
          'class' => [
            $cb->getFormId() . '-container',
          ],
          'bundle' => $cb->getFormId(),
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
            'bundle' => $cb->getFormId(),
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
            'callback' => [$cb, "searchButtonCallback"], // put callback code here @see https://www.drupal.org/docs/drupal-apis/javascript-api/ajax-forms#s-ajax-commands-ajaxresponse
            'disable-refocus' => FALSE,
            'event' => 'click',
            'progress' => ['type' => 'throbber'],
            'wrapper' => $cb->getFormId(),  // this element is updated with ajax results
          ],
          '#attributes' => [
            'class' => [
              'form__button--bos-remote_search_box',
              'button--submit',
            ],
            'bundle' => $cb->getFormId(),
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
            'bundle' => $cb->getFormId(),
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
            'bundle' => $cb->getFormId(),
          ]
        ],
        'record' => [
          '#type' => 'container',
        ],
      ],
    ]);

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
  static public function buildAddressSearch($form, RemoteSearchBoxFormBase $cb, bool $required = TRUE) {
    $form['search_criteria_wrapper']['search'] = array_merge(
      $form['search_criteria_wrapper']['search'],
      [
        'searchbox' => [
          '#type' => 'textfield',
          '#title' => t('Address, Neighborhood or Intersection'),
          '#description' => "",
          '#attributes' => [
            'class' => [
              'form__input--bos-remote_search_box',
              'sf--md',
              "sf-i-f",
            ],
            'bundle' => $cb->getFormId(),
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
  static public function buildAutoFillAddressSearch($form, RemoteSearchBoxFormBase $cb, bool $required = TRUE) {
    $form['search_criteria_wrapper']['search'] = array_merge(
      $form['search_criteria_wrapper']['search'],
      [
        'searchlabel' => [
          '#type' => 'label',
          '#title' => t('Address, Neighborhood or Intersection'),
          '#attributes' => [
            'bundle' => $cb->getFormId(),
            'class' => ['txt-l'],
          ],
        ],
        'searchbox' => [
          '#type' => 'textfield',
          '#title' => t('Address, Neighborhood or Intersection'),
          '#description' => "",
          '#ajax' => [ // @see https://www.drupal.org/docs/drupal-apis/javascript-api/ajax-forms
            'callback' => [$cb, "searchTextBoxCallback"], // put callback code here @see https://www.drupal.org/docs/drupal-apis/javascript-api/ajax-forms#s-ajax-commands-ajaxresponse
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
            'bundle' => $cb->getFormId(),
            'placeholder' => t('Enter Address to Lookup'),
            'autofocus' => '',
          ],
          '#size' => 100,
          '#maxlength' => 100,
          '#required' => $required,
        ],
        'searchicon' => [
          '#type' => 'button',
          '#attributes' => [
            'class' => [
              'sf-i-b',
            ],
            'bundle' => $cb->getFormId(),
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
  static public function addManualCriteria(array $form, array $criteria) {
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
  static public function criteriaNeighborhoodSelect(RemoteSearchBoxFormBase $cb, int $weight = 0, bool $required = FALSE) {

    $neighborhoods = \Drupal::entityTypeManager()->getStorage("taxonomy_term")->loadTree('neighborhoods');
    $options = [];
    foreach ($neighborhoods as $neighborhood) {
      $sanitized_name = t($neighborhood->name)->render();
      $options[$sanitized_name] = $sanitized_name;
    }

    $form = [
      'neighborhood' => [
        '#type' => 'select',
        '#weight' => $weight,
        '#title' => t('District/Neighborhood (optional)'),
        '#required' => $required,
        '#attributes' => [
          'class' => [
            'sel-f',
            'sel-f--fw',
            'form__input--bos-remote_search_box',
          ],
          'bundle' => $cb->getFormId(),
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
  static public function criteriaWeekdayCheckbox(RemoteSearchBoxFormBase $cb, int $weight = 0, bool $required = FALSE) {
    $form = [
      'day' => [
        '#type' => 'checkboxes',
        "#label" => t('Day (optional)'),
        '#weight' => $weight,
        '#required' => $required,
        '#attributes' => [
          'class' => [
            'cb-f',
          ],
          'bundle' => $cb->getFormId(),
        ],
        '#options' => [
          'MON' => t('Monday'),
          'TUE' => t('Tuesday'),
          'WED' => t('Wednesday'),
          'THU' => t('Thursday'),
          'FRI' => t('Friday'),
          'SAT' => t('Saturday'),
          'SUN' => t('Sunday'),
        ],
      ],
    ];
    return $form;
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
  static public function addSearchResults(array &$form, array $resultElements, int $type = self::RESULTS_DATASET) {
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

  static public function clearForm(array &$form) {
    $form['search_criteria_wrapper']['results']['output'] = [];
    $form['search_criteria_wrapper']['errors'] = [];
  }

  /**
   * Converts a long or short weekday string out of the passed string. If the
   * string is 4 chars or less will return a long string based on first 3 chars,
   * else returns the standard 3 letter shortening for english weekdays.
   *
   * @param string $weekday The weekday to convert
   * @param bool $capitalize_output If true, then the results first letter is caps
   *
   * @return string
   */
  static public function weekdayConvert(string $weekday, bool $capitalize_output = FALSE) {
    $outputLong = len($weekday) <= 4 ? TRUE : FALSE;

    switch (strtolower(substr($weekday,0,3))) {
      case "sun":
        $output = $outputLong ? "sunday" : "sun";
        break;
      case "mon":
        $output = $outputLong ? "monday" : "mon";
        break;
      case "tue":
        $output = $outputLong ? "tuesday" : "tue";
        break;
      case "wed":
        $output = $outputLong ? "wednesday" : "wed";
        break;
      case "thu":
        $output = $outputLong ? "thursday" : "thu";
        break;
      case "fri":
        $output = $outputLong ? "friday" : "fri";
        break;
      case "sat":
        $output = $outputLong ? "saturday" : "sat";
        break;
      default:
        $output = strtolower($weekday);
    }
    return $capitalize_output ? ucfirst($output) : $output;
  }
}
