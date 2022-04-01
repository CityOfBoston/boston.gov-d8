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
   * Builds and posts to the remote search service.
   * Uses the variable $this->submitted_form to access submitted values.
   *
   * @return mixed Json string or array with results from the remote query.
   *              should be wrapped as array with a status field (ok or error)
   *              and the results in a data field.
   *              eg. ['status' => 'ok', 'data' => [results]]
   *
   */
  public function submitToRemote();

  /**
   * Takes the results from the search and builds the form to post back to
   * ajax caller.
   *
   * @param array $form The Drupal form
   * @param \Drupal\Core\Form\FormStateInterface $form_state The submitted form state.
   * @param array $result The results from the remote search (if any)
   *
   * @return void
   */
  public function buildResponseForm(array &$form, FormStateInterface $form_state);

}
