<?php

namespace Drupal\bos_remote_search_box\Util;

use Drupal\bos_remote_search_box\Form\RemoteSearchBoxFormBase;
use Drupal\Core\Render\Markup;

class RemoteSearchBoxHelper {

  /**
   * Constants used to indicated Remote System responses to be returned to the
   * form.
   */
  const RESULTS_SUMMARY = 0;
  const RESULTS_DATASET = 1;
  const RESULTS_RECORDLIST_FORM = 2;
  const RESULTS_RECORDLIST_FOOTER = 3;
  const RESULTS_RECORD = 4;
  const RESULTS_ERROR = 5;
  const RESULTS_MESSAGE = 6;

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

    $form = array_merge($form, [
      "#theme" => "remote_search_box",
      "#title" => $cb->form_title,
      '#tree' => TRUE,
      '#attributes' => [
        'class' => [
          $cb->getFormId()
        ],
        'id' => $cb->getFormId(),
        'bundle' => $cb->getFormId(),
        'onsubmit' => 'return false;',
      ],
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
            'class' => ['co', 'sf',],
            'bundle' => $cb->getFormId(),
          ],
          'search_filters' => [
            '#tree' => TRUE,
            '#type' => 'container',
            '#weight' => 2,
            '#attributes' => [
              'class' => ['co', 'sf',],
              'bundle' => $cb->getFormId(),
            ],
          ],
          'search_button' => [
            '#type' => 'submit',
            '#value' => t('Search'),
            '#weight' => 10,
            '#ajax' => [ // @see https://www.drupal.org/docs/drupal-apis/javascript-api/ajax-forms
              'callback' => [$cb, "searchButtonCallback"], // put callback code here @see https://www.drupal.org/docs/drupal-apis/javascript-api/ajax-forms#s-ajax-commands-ajaxresponse
              'disable-refocus' => TRUE,
              'event' => "click",   // Allows clicking enter key to submit form.
              'progress' => ['type' => 'throbber'],
              'effect' => "fade",
              'wrapper' => $cb->getFormId(),  // this element is updated with ajax results
            ],
            '#disabled' => FALSE,
            '#attributes' => [
              'class' => [
                'form__button--bos-remote_search_box',
                'button--submit',
              ],
              'bundle' => $cb->getFormId(),
            ],
          ],
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
          ],
          'output' => [],
        ],
        'record' => [
          '#type' => 'container',
          "#weight" => 14,
          '#attributes' => [
            'class' => [
              'remote-search-box-record'
            ],
            'id' => [
              'remote-search-box-record'
            ],
            'bundle' => $cb->getFormId(),
          ],
          'item' => [],
        ],
        'errors' => [
          '#type' => 'container',
          '#weight' => 16,
          '#attributes' => [
            'class' => [
              'remote-search-box-errors'
            ],
            'id' => [
              'remote-search-box-errors'
            ],
            'bundle' => $cb->getFormId(),
          ]
        ],
        'messages' => [
          '#type' => 'container',
          '#weight' => 18,
          '#attributes' => [
            'class' => [
              'remote-search-box-messages'
            ],
            'id' => [
              'remote-search-box-messages'
            ],
            'bundle' => $cb->getFormId(),
          ]
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
            'autofocus' => 'TRUE',
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
    $form['search_criteria_wrapper']['search']['search_filters'] = array_merge(
      $form['search_criteria_wrapper']['search']['search_filters'],
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
  static public function addSearchResults(array &$form, Markup|array $resultElements, int $type = self::RESULTS_DATASET) {
    switch ($type) {

      case self::RESULTS_SUMMARY:
        // Merge the supplied output elements into the existing array.
        // Typically, this is markup. By default the message specified in the
        // component instance is passed and does not need to be altered in most
        // cases.
        // The instance text can however be added by using this function with
        // this (RESULTS_SUMMARY) constant.

        if (!isset($form['search_criteria_wrapper']['results']['output']['intro'])) {
          $form['search_criteria_wrapper']['results']['output']['intro'] = [];
        }
        $form['search_criteria_wrapper']['results']['output']['intro'] = array_merge(
          $form['search_criteria_wrapper']['results']['output']['intro'],
          $resultElements
        );
        break;

      case self::RESULTS_DATASET:
        // Merge the data found $resultElements into the form output.
        // NOTE: will add to existing data, this may not be what you wish -
        // if you want to replace data in the variable, then clear the
        // BuildInfo["dataset"] variable in calling class.
        //
        // This will pass a raw dataset array to the twig engine, and the twig
        // template container--rsb--results can be used to process the results
        // using the dataset object.

        if (!isset($form['search_criteria_wrapper']['results']['#dataset'])) {
          $data = [];
        }
        else {
          $data = (array) $form['search_criteria_wrapper']['results']['#dataset'];
        }
        // Merge $results
        $data = array_merge($data, $resultElements);
        // Pass $resultElements to twig in a variable called "dataset".
        $form['search_criteria_wrapper']['results']['#dataset'] = $data;
        break;

      case self::RESULTS_RECORDLIST_FORM:
        // $resultsElements must be an array complying with the forms API.
        // NOTE: It does not need to be a complete form, b/c it is being
        // embedded in a form.
        // This will pass the form to the twig engine and the form will be
        // available in the element.record_listing variable in the twig template

        $form['search_criteria_wrapper']['results']['output']['recordlisting'] = $resultElements;
        break;

      case self::RESULTS_RECORDLIST_FOOTER:
        // $resultsElements must be an array complying with the forms API.
        // NOTE: It does not need to be a complete form, b/c it is being
        // embedded in a form.
        // This will pass the form to the twig engine and the form will be
        // available in the element.record_listing variable in the twig template

        $form['search_criteria_wrapper']['results']['output']['recordlistingfooter'] = $resultElements;
        break;

      case self::RESULTS_RECORD:
        // Merge the data found $resultElements into the form output.
        // NOTE: will add to existing data, this may not be what you wish -
        // if you want to replace data in the variable, then clear the
        // BuildInfo["dataset"] variable in calling class.
        //
        // This will pass a raw dataset array to the twig engine, and the twig
        // template container--rsb--record can be used to process the results
        // using the dataset object.

        if (!isset($form['search_criteria_wrapper']['record']['#dataset'])) {
          $data = [];
        }
        else {
          $data = (array) $form['search_criteria_wrapper']['record']['#dataset'];
        }
        // Merge $results
        $data = array_merge($data, $resultElements);
        // Pass $resultElements to twig in a variable called "dataset".
        $form['search_criteria_wrapper']['record']['#dataset'] = $data;
        break;

      case self::RESULTS_ERROR:
        // This adds text or markup found in the array $resultElements to the
        // errors section of the output form.
        // Use to provide custom error messages from validation or after a
        // search has been performed.

        foreach ($resultElements as $errorElement) {
          if (!isset($form['search_criteria_wrapper']['errors']['#items'])) {
            $form['search_criteria_wrapper']['errors']['#items'] = [];
          }
          $form['search_criteria_wrapper']['errors']['#items'][] = [
            "#markup" => $errorElement,
            '#allowed_tags' => ['div'],

          ];
        }
        break;

      case self::RESULTS_MESSAGE:
        // This adds text or markup found in the array $resultElements to the
        // messages section of the output form.
        // Use to provide custom messages back to the user.

        foreach ($resultElements as $msgElement) {
          if (!isset($form['search_criteria_wrapper']['messages']['#items'])) {
            $form['search_criteria_wrapper']['messages']['#items'] = [];
          }
          $form['search_criteria_wrapper']['messages']['#items'][] = [
            "#markup" => $msgElement,
            '#allowed_tags' => ['div'],
          ];
        }
        break;
    }
  }

  static public function clearForm(array &$form) {
//    $form['search_criteria_wrapper']['results']['output'] = [];
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
    $outputLong = strlen($weekday) <= 4 ? TRUE : FALSE;

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

  /**
   * This function converts an address provided so it is more likely to match
   * streets in the database.
   *
   * @param string $streetname A streetname to be converted.
   *
   * @return string Modified street address.
   */
  static public function santizeStreetName(string $streetname) {

    $ordinal_abbrev = [
      "1ST" => "FIRST",
      "2ND" => "SECOND",
      "3RD" => "THIRD",
      "4TH" => "FOURTH",
      "5TH" => "FIFTH",
      "6TH" => "SIXTH",
      "7TH" => "SEVENTH",
      "8TH" => "EIGTH",
      "9TH" => "NINTH",
      "10TH" => "TENTH"];

    $street_abbrev = [
      'AVENUE' => 'AVE', 'AV' => 'AVE',
      'ALLEY' => 'AL',
      'BOULEVARD' => 'BLV', 'BOULVARD' => 'BLV', 'BLVD' => 'BLV',
      'BRIDGE' => 'BRG',
      'CIRCLE' => 'CIR',
      'COURT' => 'CT',
      'CRESCENT'=> 'CR', 'CRE'=> 'CR', 'CRES' => 'CR',
      'DRIVE' => 'DR', 'DRV' => 'DR',
      'EXTENSION' => 'EXT',
      'FREEWAY' => 'FWY',
      'HIGHWAY'=> 'HWY', 'HWAY' => 'HWY',
      'LANE' => 'LN',
      'PARK' => 'PK',
      'PARKWAY' => 'PW', 'PKWY'=> 'PW', 'PKW' => 'PW',
      'PLACE' => 'PL',
      'PLAZA' => 'PLZ',
      'ROAD' => 'RD',
      'ROW' => 'RO',
      'SQUARE' => 'SQ',
      'STREET' => 'ST', 'STR' => 'ST',
      'WHARF' => 'WH',
      'WAY' => 'WY',
    ];

    $compass_abbrev = [
      "N" => "NORTH",
      "S" => "SOUTH",
      "E" => "EAST",
      "W" => "WEST",
    ];

    $street_prefix = [
      "MOUNT" => "MT",
      "SAINT" => "ST",
    ];

    $streetname = strtoupper(trim($streetname));
    $streetname = preg_replace("/\t/", ' ', $streetname);
    $streetname = preg_replace("/[^A-Z\s]/", 'z', $streetname);
    $streetname = preg_replace("/^\s+|\s+$/", '', $streetname);

    // Replace numeric numbered streets with text numbering
    foreach ($ordinal_abbrev as $key => $value) {
      $streetname = preg_replace("/${key}\s/", $value, $streetname);
    }

    // Replace common street-type abbreviations with standard abbreviation
    foreach ($street_abbrev as $key => $value) {
      $streetname = preg_replace("/\s${key}$/", " ${value}", $streetname);
    }

    // Replace common street directional abbreviations
    foreach ($compass_abbrev as $key => $value) {
      $streetname = preg_replace("/^${key}\s(?!ST$)/", $value, $streetname);
    }

    // Replace common streetname prefix abbreviations
    foreach ($street_prefix as $key => $value) {
      $streetname = preg_replace("/^${key}\s/", $value, $streetname);
    }

    return ucwords(trim(strtolower($streetname)));

  }
}
