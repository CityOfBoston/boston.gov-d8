<?php

namespace Drupal\bos_remote_search_box;

use Drupal\Core\Form\FormStateInterface;

/**
 * Defines the interface for search box implementations.
 *
 * Extension of the RemoteSearchBoxFormBase and \Drupal\Core\Form\FormBase
 * to drive developers to use seperate query and response functions during
 * form submission.
 *
 * @see \Drupal\bos_remote_search_box\Form\template
 */
interface RemoteSearchBoxFormInterface {

  /**
   * Completes and customized validation required by the class prior to
   * submission.
   * This is called from RemoteSearchBoxFormBase->validateForm()
   * The form_state parameter can be manipulated to fail validation as per usual
   * form validation on Drupal\Core\Form\FormStateInterface objects.
   *
   * @param array $form
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *
   * @return mixed
   */
  public function validateSearch(array &$form, FormStateInterface $form_state);

  /**
   * Builds and posts to the remote search service.
   * Uses the variable $this->submitted_form to access submitted values.
   *
   * @return mixed Json string or array with results from the remote query.
   *              should be wrapped as array with a status field (ok or error)
   *              and the results in a data field.
   *              eg. ['status' => 'ok', 'data' => [results]]
   *
   */
  public function submitToRemote(array &$form, FormStateInterface $form_state);

  /**
   * Reformat the results from remote database.
   * NOTE: expects a result set (array) in $this->dataset.
   * (use parent::addSearchResults() to correctly add markup to form)
   *
   * @param array $form
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *
   * @return void
   */
  public function buildSearchResults(array &$form, FormStateInterface $form_state);

}
