<?php

namespace Drupal\bos_remote_search_box\Form;

/**
 *  TEMPLATE FILE
 *  Use this template file as a template for all new forms associated with a
 *  remote search function.
 *  @see notes here http://huuiacvc
 */

use Drupal\bos_remote_search_box\RemoteSearchBoxFormInterface;
use Drupal\bos_sql\Controller\SQL;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class template.
 *
 * @package Drupal\bos_remote_search_box\Form
 */
class template extends RemoteSearchBoxFormBase implements RemoteSearchBoxFormInterface {

  /**
   * Implements getFormId().
   */
  public function getFormId() {
    // TODO: rename form_id to an apropriate value for the new lookup
    return 'my_lookup';
  }

  /**
   * Implements buildForm()
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    // TODO: Step 1 - rename form_name to an apropriate value for the new lookup
    $this->form_name = "My Lookup";

    // Build the basic search form
    $form = parent::buildForm($form, $form_state);

    // ToDo: Step2 - Add in the main search textarea - in this case an address lookup.
    // $form = parent::buildAddressSearch($form, true);
    // Optionally, add in other criteria
    // $form = parent::addManualCriteria($form, parent:: criteriaNeighborhoodSelect(1, false));
    // Optional, define custom criteria and add manually
    /*
    $form_citeria = [
      'ordinalLabel' => [
        '#type' => 'label',
        '#title' => $this->t('Ordinal Number (Optional)'),
        '#weight' => 3,
        '#attributes' => [
          'class' => ['txt-l'],
        ],
      ],
      'ordinal' => [
        '#type' => 'checkboxes',
        '#label_display' => 'invisible',
        '#weight' => 3,
        '#required' => false,
        '#attributes' => [
          'bundle' => 'remote_search_box',
          'class' => [
            'cb-f',
          ],
        ],
        '#options' => [
          '1' => $this->t('1st'),
          '2' => $this->t('2nd'),
          '3' => $this->t('3rd'),
          '4' => $this->t('4th'),
          '5' => $this->t('5th'),
        ],
      ]
    ];
    parent::addManualCriteria($form, $form_citeria);
    */

    // You can tweak the completed $form object here if you wish
    // $form['search_criteria_wrapper']['#attributes']['class'][] = "sf--mb";
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

    // NOTE: To set a validation error, you need to use the following syntax.
    // Checkout RemoteSearchBoxFormBase->validateForm for examples.
    // $form_state->setErrorByName('plate', $this->t("This does not appear to be a valid license plate."));

  }

  /**
   * Implements submitForm().
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Call the RemoteSearchBoxFormBase class which will flatten the form
    // array down and provide a more usable set of form values submitted by the
    // user in $this->submitted_form.
    parent::submitForm($form, $form_state);

    // Reformat the form ($this->submitted_form) into a query and send it to
    // the SQL Connector object
    if (!empty($this->submitted_form)) {
      $results = $this->submitToRemote();
      $this->buildResponseForm($form, $form_state, $results);
    }

  }

  /**
   * {@inheritDoc}
   */
  public function submitToRemote() {
    // Create a query to send to the remote search service.
    // The form's submitted values are in the $this->submitted_values variable.
    //
    // This function should return an unformatted array or json object from the
    // remote service.
    //
    // This function should handle errors passed back from the remote service,
    // or raised by the SQL class.
    try{
      // example using \Drupal\bos_sql\Controller\SQL
/*
      $sql = new SQL();
      $appname = $this->getFormId();
      $auth_token = $sql->getToken($appname)[SQL::AUTH_TOKEN];
      $conn_token = $sql->getToken($appname)[SQL::CONN_TOKEN];
      $sql_statement = "SELECT * FROM etc";
      return $sql->runQuery($auth_token, $conn_token, $sql_statement);
*/

    }
    catch (\Exception $e) {
      return [
        'status' => 'error',
        'data' => $e->getMessage(),
      ];
    }
  }

  /**
   * {@inheritDoc}
   */
  public function buildResponseForm(array &$form, FormStateInterface $form_state, array $result) {
    // Allow the parent to rebuild the base form a bit so that the
    // results can be pasted back in cleanly.
    parent::prepResponseForm($form, $form_state);

    // Build the search results into this array.
    // ToDo could we consider using twig here somehow? Then could just insert into a twig template ... :)
    $fmtResults = [
      '#theme' => "rsb-results",
      '#markup' => '<div>Results:</div>',
    ];

    // This function now only has to be concerned with formatting the results
    // and then calling
    parent::buildSearchResults($form, $fmtResults);
  }

}
