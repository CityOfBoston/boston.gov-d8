<?php

namespace Drupal\bos_remote_search_box\Form;

use Drupal\bos_remote_search_box\RemoteSearchBoxFormInterface;
use Drupal\bos_sql\Controller\SQL;
use Drupal\Core\Form\FormStateInterface;
use PHPUnit\Exception;

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
    $form = parent::buildAddressSearch($form, TRUE);
    $criteria = parent:: criteriaNeighborhoodSelect(1, FALSE);
    $form = parent::addManualCriteria($form, $criteria);
    $criteria = parent:: criteriaWeekdayCheckbox(2, FALSE);
    $form = parent::addManualCriteria($form, $criteria);
    $form = parent::addManualCriteria($form, [
      'ordinal' => [
        '#type' => 'checkboxes',
        '#label' => $this->t('Ordinal Number (Optional)'),
        '#weight' => 3,
        '#required' => FALSE,
        '#attributes' => [
          'class' => [
            'cb-f',
          ],
          'bundle' => $this->bundle,
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
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    // @see https://www.drupal.org/docs/drupal-apis/form-api/introduction-to-form-api#fapi-validation
    //
    // Drupal core forms utilities will automatically validate for required
    // fields - if a required field is not provided validation will always fail.
    //
    // Provided the form array was not heavily modified during builForm()
    // calling validateForm() on the parent (RemoteSearchBoxFormBase) will
    // automatically validate form elements which were built by that class.
    // You should only need to add validation code for form elements that were
    // created in this classes buildForm(), or where validation required differs
    // from that already in the RemoteSearchBoxFormBase class.
    parent::validateForm($form, $form_state);
  }

  /**
   * Implements submitForm().
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // This reformats the submitted form into a single dimensional array.
    parent::submitForm($form, $form_state);

    // Reformat the form ($this->submitted_form) into a query and send it to
    // the SQL Connector object
    if (!empty($this->submitted_form)) {
      // Query remote service
      $this->submitToRemote();
    }
    else {
      $this->errors[] = "No Search was submitted";
    }
  }

  /**
   * {@inheritDoc}
   */
  public function submitToRemote() {
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
          'address' => '82 Old Cionnecticut Path',
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
   * {@inheritDoc}
   */
  public function buildResponseForm(array &$form, FormStateInterface $form_state) {
    parent::prepResponseForm($form, $form_state);
    // ToDo: enable disable fields on the form based on dataset returned.
  }

  /**
   * Handles the AJAX callack when the search button is clicked on the form.
   *
   * @param array $form
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *
   * @return array An updated form to display on AJAX return.
   */
  public function searchButtonCallback(array &$form, FormStateInterface $form_state) {
    // re-configure the form based on search results.
    $this->buildResponseForm($form, $form_state);

    // Provide a summary message.
    $fmtResults = [
      '#markup' => 'Nothing found',
    ];
    if (!empty($this->dataset)) {
      $fmtResults = [
        '#markup' => 'Thanks for playing:',
      ];
      // Add the search results summary message
      parent::buildSearchResults($form, $fmtResults, self::RESULTS_SUMMARY);
    }

    // json encode the results, and insert into the form.
    if (!empty($this->dataset)) {
      $results = (array) $this->dataset;
      parent::buildSearchResults($form, $results, self::RESULTS_DATASET);
    }

    // Check for any errors
    if (!empty($this->errors)) {
      parent::buildSearchResults($form, $this->errors, self::RESULTS_ERRORS);
    }

    return $form;
  }

}
