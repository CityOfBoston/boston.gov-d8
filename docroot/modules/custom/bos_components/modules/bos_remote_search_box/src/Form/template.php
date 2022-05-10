<?php

namespace Drupal\bos_remote_search_box\Form;

/**
 *  TEMPLATE FILE
 *  Use this template file as a template for all search forms associated with a
 *  remote search function.
 *  @package Drupal\bos_remote_search_box\Form
 *  @see notes here http://huuiacvc
 */

use Drupal\bos_remote_search_box\RemoteSearchBoxFormInterface;
use Drupal\bos_sql\Controller\SQL;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\bos_remote_search_box\Util\RemoteSearchBoxHelper as helper;

class TemplateLookup extends RemoteSearchBoxFormBase implements RemoteSearchBoxFormInterface {

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
    // TODO: Step 1 - rename form_title to an apropriate value for the new lookup
    $this->form_title = "My Lookup";

    // Build the basic search form
    $form = parent::buildForm($form, $form_state);

    // ToDo: Step2 - Add in the main search textarea - in this case an address lookup.
    // $form = helper::buildAddressSearch($form, $this, true);
    // Optionally, add in other criteria
    // $form = helper::addManualCriteria($form, helper::criteriaNeighborhoodSelect($this, 1, false));
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
    helper::addManualCriteria($form, $form_citeria);
    */

    // You can tweak the completed $form object here if you wish
    // $form['search_criteria_wrapper']['#attributes']['class'][] = "sf--mb";
    return $form;
  }

  /**
   * {@inheritDoc}
   */
  public function validateSearch(array &$form, FormStateInterface $form_state) {
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

    // NOTE: To set a validation error, you need to use the following syntax.
    // Checkout RemoteSearchBoxFormBase->validateForm for examples.
    // $form_state->setErrorByName('plate', $this->t("This does not appear to be a valid license plate."));
  }

  /**
   * {@inheritDoc}
   */
  public function submitToRemote(array &$form, FormStateInterface $form_state) {
    // Create a query to send to the remote search service.
    // The form's submitted values are in the $this->submitted_values variable.
    //
    // This function should update the BuildInfo variable in the $form_state
    // with an array of search results from the remote service.
    // Can also be empty array if nothing was returned.
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
      $results = $sql->runQuery($auth_token, $conn_token, $sql_statement);

      $build_info = $form_state->getBuildInfo();
      $build_info["dataset"] = (array) $results;
      $form_state->setBuildInfo($build_info);
      */

      // Call out to buildResponseForm() where you can write the formatting for
      // the form displayed to the user who initated the search.
      $this->buildSearchResults($form, $form_state);

    }
    catch (\Exception $e) {
      $build_info = $form_state->getBuildInfo();
      $build_info["dataset"] = [
        'status' => 'error',
        'data' => $e->getMessage(),
      ];
      $form_state->setBuildInfo($build_info);
    }
  }

  /**
   * {@inheritDoc}
   */
  public function buildSearchResults(array &$form, FormStateInterface $form_state) {

    // Provide a summary message.
    $fmtResults = [
      '#markup' => 'Nothing found',
    ];
    if (!empty($form_state->getBuildInfo()["dataset"])) {
      $fmtResults = [
        '#markup' => 'The reslts of you search are as follows:',
      ];
    }
    // Add the search results summary message
    helper::addSearchResults($form, $fmtResults, helper::RESULTS_SUMMARY);

    // json encode the results, and insert into the form.
    if (!empty($form_state->getBuildInfo()["dataset"])) {
      $results = (array) $form_state->getBuildInfo()["dataset"];

      // Adding in the $results array will make the results available to the
      // twig template container--rsb--results.html.twig.
      //      helper::addSearchResults($form, $results, helper::RESULTS_DATASET);

      // Creating a form element will allow callbacks so deep links to records
      // can be created
      //      $fm = [ .. form elements per formsAPI ..];
      //      helper::addSearchResults($form, $fm, helper::RESULTS_RECORDLIST_FORM);

    }

    // Adding in the $results array will make the results available to the
    // twig template container--street-sweeping-lookup--results.html.twig.
    helper::addSearchResults($form, $fmtResults, helper::RESULTS_SUMMARY);

    // Check for any errors
    if (!empty($this->errors)) {

      // Adding in the $results array will make the results available to the
      // twig template container--street-sweeping-lookup--errors.html.twig.
      helper::addSearchResults($form, $this->errors, helper::RESULTS_ERROR);
    }
  }

  /**
   * {@inheritDoc}
   */
  public function buildRecord(array &$form, FormStateInterface $form_state) {
    // TODO: Implement buildRecord() method.

    /*    if (empty($this->record_id)) {
          foreach ($this->user_input as $key => $value) {
            if (is_string($value) && substr($key, 0, 3) == "id_") {
              $this->record_id = explode("_", $key, 2)[1];
              break;
            }
          }

          if (empty($this->record_id)) {
            // Could not find a record to search for.
            // todo: raise an error.
            return;
          }
        }

        // This is the second call to SQL using same creds etc, so there should
        // be no errors.
        $sql = new SQL();
        $appname = $this->getFormId();
        $tokens = $sql->getToken($appname);
        if ($tokens) {
          $auth_token = $tokens[SQL::AUTH_TOKEN];
          $conn_token = $tokens[SQL::CONN_TOKEN];

          $sql_statement = "SELECT *\n  FROM PwdSweeping";
          $sql_statement .= "\nWHERE MainID = " . $this->record_id;
          $results = $sql->runQuery($auth_token, $conn_token, $sql_statement);

          // Adding in the $results array will make the results available to the
          // twig template container--street-sweeping-lookup--results.html.twig.
          helper::addSearchResults($form, $results, helper::RESULTS_RECORD);
        }
        return $form;*/
  }

  public function linkCallback($action) {
    // TODO: Implement linkCallback() method.
  }

}

/**
 *  TEMPLATE FILE
 *  Use this template file as a template for results forms (if needed) associated
 *  with a remote search function.
 *
 * @package Drupal\bos_remote_search_box\Form
 */
class TemplateRecord extends FormBase {

  /**
   * {@inheritDoc}
   */
  public function getFormId() {
    // TODO: Implement getFormId() method.
  }

  /**
   * {@inheritDoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    // TODO: Implement buildForm() method.
  }

  /**
   * {@inheritDoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // TODO: Implement submitForm() method.
  }

}
