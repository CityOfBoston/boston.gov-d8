<?php

namespace Drupal\bos_mnl\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class MNLSettingsForm.
 *
 * @package Drupal\bos_mnl\Form
 */
class MNLSettingsForm extends ConfigFormBase {

  /**
   * Implements getFormId().
   */
  public function getFormId() {
    return 'mnl_admin_settings';
  }

  /**
   * Implements getEditableConfigNames().
   */
  protected function getEditableConfigNames() {
    return ["bos_mnl.settings"];
  }

  /**
   * Implements buildForm()
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('bos_mnl.settings');
    $mnl_import = \Drupal::queue("mnl_import")->numberOfItems();
    $mnl_cleanup = \Drupal::queue("mnl_cleanup")->numberOfItems();
    $mnl_update = \Drupal::queue("mnl_update")->numberOfItems();
    $form = [
      '#tree' => TRUE,
      'label1' => [
        "#markup" => "<h3>This module exposes REST endpoints at <b><i>/rest/mnl/xxxx</i>.</b></h3><p>See documentation below for REST endpoint syntax.</p>"
      ],
      'mnl_admin' => [
        '#type' => 'fieldset',
        '#title' => 'MNL API Endpoint',
        '#description' => 'Configuration for My Neighborhood Lookup custom API endpoints.',
        'auth_token' => [
          '#type' => 'textfield',
          '#title' => t('API KEY / Token'),
          '#description' => t('Enter a random string to authenticate API calls.'),
          '#default_value' => $config->get('auth_token'),
          '#required' => FALSE,
        ],
      ],
      'diagnostics' => [
        '#type' => 'fieldset',
        '#title' => 'Queue/process Diagnostics',
        'label' => [
          "#markup" => "<h4>Current Queue Status:</h4><table>
            <tr><th>Queue Name</th><th>Description</th><th>Queue Size</th></tr>
            <tr><td>mnl_update</td><td>SAM Records queued for process from update endpoint (incremental data import).</td><td>" . $mnl_update . "</td></tr>
            <tr><td>mnl_import</td><td>SAM Records queued for process from import endpoint (data universe import).</td><td>" . $mnl_import . "</td></tr>
            <tr><td>mnl_cleanup</td><td>Existing mnl entities queued for removal (due to last universe import).</td><td>" . $mnl_cleanup . "</td></tr>
            </table>"
        ],
        'label2' => [
          "#markup" => "<h4>Last Queue Worker Results:</h4>This is a report on processing from the last queue worker for each of the identified queues. The queue worker is activated by a scheduled task, or a manual execution of \"drush queue:run\".<br>
            <table>
            <tr><th>mnl_update<th>mnl_import</th><th>mnl_cleanup</th></tr>
            <tr><td>" . ($config->get("last_mnl_update") ?: "Never Run") . "</td><td>" . ($config->get("last_mnl_import") ?: "Never Run") . "</td><td>" . ($config->get("last_mnl_cleanup") ?: "Never Run") . "</td></tr>
            </table><br>Note: More logging available in Drupal log messages (if syslog is enabled)."
        ],
      ],
      'label2' => [
        "#markup" => "<h3>Notes:</h3><ol><li>The REST endpoint merely loads data into a queue.</li>
        <li>To perform and automate the update of MNL/SAM entities/nodes for use by the MNL module, 2 scheduled tasks must be created.<br><ul><li>The first task should execute the following command:<pre>drush queue:run mnl_import &> /dev/null && drush queue:run mnl_cleanup &> /dev/null</pre></li><li>and the second task should execute<pre>drush queue:run mnl_update &> /dev/null</pre></li></ul>Timing for scheduling those tasks should be some time after the expected completion of data submission via the REST endpoint.</li></ol>"
      ],
      'label3' => [
        "#markup" => "<h4>If the scheduled tasks above are not created then the website will not be updated until the commands above are run manually.</h4>"
      ],
      'label4' => [
        "#markup" => "<br><hr><h3>Documentation:</h3>
All POSTS to endpoint must have format:
  <ul><pre>/rest/mnl/YYY?api_key=XXX</pre></ul>
  <ul><li>Where YYYY is one of 'update', 'import' or 'manual'.</li>
  <li>Where api_key XXX is the <i><b>API KEY / token</b></i> defined above.</li></ul><br>
'import' and 'update' endpoint usage must include a json string in the
  body in the format:
<ul><pre>[
    {\"sam_address_id\":123,\"full_address\":\"address\",\"data\":jsonstring\"},
    {\"sam_address_id\":123,\"full_address\":\"address\",\"data\":jsonstring\"}
]</pre></ul>
  <ul><li>The 'jsonstring' must be a valid json object in string form and can contain
  any data provided it represents a single record and can be decoded by
  PHP's json_decode function.</li></ul><br>
'manual' endpoint requires no body (any body will be ignored) but must have an extended querystring in the format:
  <ul><pre>?api_key=XXX&path=/path-on-server/file.json&limit=N&mode=ZZZ</pre></ul>
  <i>Required:</i>
    <ul><li>'path' is path on remote server (or local container) to import file.</li></ul>
  <i>Optional:</i>
    <ul><li>'limit' process the first N records in the import file (defaults all).</li>
    <li>'mode' ZZZ can be either 'import' or 'update' (defaults to 'update').</li></ul>
<b>Note:</b>
 <ul><li><b>UPDATE</b> updates \"full address\" and \"data\" of SAM records in the DB with
   \"full address\" and \"data\" from queued records with a matching \"sam_address_id\"
   from the json payload. New records will be created if an existing
   \"sam_address_id\" is not found in the DB. If there are duplicate
   \"sam_address_id\"s in the json payload, only the first one of the duplicate records
   will be processed and the rest will be skipped. No records will be deleted or marked for deletion.</li>
 <li><b>IMPORT</b> also updates \"full address\" and \"data\" of SAM records in the DB with
   \"full address\" and \"data\" from queued records with a matching \"sam_address_id\"
   from the json payload. New records will also be created if an existing
   \"sam_address_id\" is not found in the DB. If there are duplicate
   \"sam_address_id\"s in the json payload, only the first one of the duplicate records
   will be processed and the rest will be skipped.  If the database contains a record (ie. a
   \"sam_address_id\") which is not in the import file, then after the import
   completes, those \"orphaned\" records will be cleaned, by removing them
   from the database.</li></ul>"
      ],
    ];
    return parent::buildForm($form, $form_state);
  }

  /**
   * Implements submitForm().
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $settings = $form_state->getValue('mnl_admin');

    $this->config('bos_mnl.settings')
      ->set('auth_token', $settings['auth_token'])
      ->save();
    parent::submitForm($form, $form_state);
  }

}
