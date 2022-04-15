<?php

namespace Drupal\bos_remote_search_box\Form;

use Drupal\bos_remote_search_box\RemoteSearchBoxFormInterface;
use Drupal\bos_sql\Controller\SQL;
use Drupal\Core\Form\FormStateInterface;
use PHPUnit\Exception;
use Drupal\bos_remote_search_box\Util\RemoteSearchBoxHelper as helper;

/**
 * Class StreetSweepingLookup.
 *
 * @package Drupal\bos_remote_search_box\Form
 */
class StreetSweepingLookup extends RemoteSearchBoxFormBase implements RemoteSearchBoxFormInterface {

  /**
   * Implements getFormId().
   */
  public function getFormId() {
    return 'street_sweeping_lookup';
  }

  /**
   * Implements buildForm()
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $this->form_name = "Street Sweeping Lookup";

    $form = parent::buildForm($form, $form_state);

    $form = helper::buildAddressSearch($form, $this, TRUE);
    $criteria = helper:: criteriaNeighborhoodSelect($this,1, FALSE);
    $form = helper::addManualCriteria($form, $criteria);
    $criteria = helper:: criteriaWeekdayCheckbox($this,2, FALSE);
    $form = helper::addManualCriteria($form, $criteria);
    $form = helper::addManualCriteria($form, [
      'ordinal' => [
        '#type' => 'checkboxes',
        '#label' => $this->t('Ordinal Number (Optional)'),
        '#weight' => 3,
        '#required' => FALSE,
        '#attributes' => [
          'class' => [
            'cb-f',
          ],
          'bundle' => $this->getFormId(),
        ],
        '#options' => [
          '1' => $this->t('1st'),
          '2' => $this->t('2nd'),
          '3' => $this->t('3rd'),
          '4' => $this->t('4th'),
          '5' => $this->t('5th'),
        ],
      ]
    ]);

    return $form;
  }

  /**
   * {@inheritDoc}
   */
  public function validateSearch(array &$form, FormStateInterface $form_state) {
    // Can be empty.
  }

  /**
   * {@inheritDoc}
   */
  public function submitToRemote(array &$form, FormStateInterface $form_state) {
    $values = $this->submitted_form;
    try {
      $sql = new SQL();
      $appname = $this->getFormId();
      //      $auth_token = $sql->getToken($appname)[SQL::AUTH_TOKEN];
      //      $conn_token = $sql->getToken($appname)[SQL::CONN_TOKEN];
      //      $sql_statement = "SELECT * FROM etc";
      //      $results = $sql->runQuery($auth_token, $conn_token, $sql_statement);
      $results = [
        '82 ocp' => [
          'address' => '82 Old Connecticut Path',
          'name' => 'David',
          'last' => 'Upton',
        ],
        '52 glen' => [
          'address' => '52 Glen Rd',
          'name' => 'Dana',
          'last' => 'Callow',
        ]
      ];

      $this->dataset = (array) $results;
    }
    catch (\Exception $e) {
      $this->errors[] = $e->getMessage();
    }
  }

  /**
   * Local function to handle reformatting of the results from remote database.
   * NOTE: expects a result set (array) in $this->dataset.
   *
   * @param array $form
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *
   * @return void
   */
  public function buildSearchResults(array &$form, FormStateInterface $form_state) {
    // Provide a summary message.
    $fmtResults = [
      '#markup' => 'Nothing found',
    ];
    if (!empty($this->dataset)) {
      $fmtResults = [
        '#markup' => 'Thanks for playing:',
      ];
    }
    // Add the search results summary message
    helper::addSearchResults($form, $fmtResults, helper::RESULTS_SUMMARY);

    // json encode the results, and insert into the form.
    if (!empty($this->dataset)) {
      $results = (array) $this->dataset;

      // Adding in the $results array will make the results available to the
      // twig template container--street-sweeping-lookup--results.html.twig.
      helper::addSearchResults($form, $results, helper::RESULTS_DATASET);
    }

    // Check for any errors
    if (!empty($this->errors)) {

      // Adding in the $results array will make the results available to the
      // twig template container--street-sweeping-lookup--errors.html.twig.
      helper::addSearchResults($form, $this->errors, helper::RESULTS_ERRORS);
    }

  }
}
