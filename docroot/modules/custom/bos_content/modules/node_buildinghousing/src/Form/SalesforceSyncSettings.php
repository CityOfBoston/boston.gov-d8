<?php

namespace Drupal\node_buildinghousing\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Queue\DelayableQueueInterface;
use Drupal\Core\Queue\DelayedRequeueException;
use Drupal\Core\Queue\RequeueException;
use Drupal\Core\Queue\SuspendQueueException;
use Drupal\Core\Utility\Error;
use Drupal\node_buildinghousing\BuildingHousingUtils;
use Drupal\salesforce\Event\SalesforceErrorEvent;
use Drupal\salesforce\Event\SalesforceEvents;
use Drupal\salesforce\Rest\RestClientInterface;
use Drupal\salesforce\SelectQuery;
use Drupal\salesforce\SFID;
use Drupal\salesforce_mapping\Event\SalesforceQueryEvent;
use Drupal\salesforce_pull\QueueHandler;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Creates CoB settings form for Salesforce.
 */
class SalesforceSyncSettings extends ConfigFormBase {

  /**
   * The Salesforce REST client.
   *
   * @var \Drupal\salesforce\Rest\RestClientInterface
   */
  protected $client;

  protected $processor;

  /**
   * The sevent dispatcher service..
   *
   * @var \Drupal\Core\State\StateInterface
   */
  protected $eventDispatcher;

  /**
   * Constructs a \Drupal\system\ConfigFormBase object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The factory for configuration objects.
   * @param \Drupal\salesforce\Rest\RestClientInterface $salesforce_client
   *   The factory for configuration objects.
   * @param \Symfony\Component\EventDispatcher\EventDispatcherInterface $event_dispatcher
   *   The event dispatcher.
   */
  public function __construct(ConfigFactoryInterface $config_factory, RestClientInterface $salesforce_client, EventDispatcherInterface $event_dispatcher) {
    parent::__construct($config_factory);
    $this->client = $salesforce_client;
    $this->eventDispatcher = $event_dispatcher;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('salesforce.client'),
      $container->get('event_dispatcher')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'cob_salesforce_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'node_buildinghousing.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $config = $this->config('node_buildinghousing.settings');

    $query =  new SelectQuery('Project__c');
    $query->fields = [
      'Id',
      'Name',
    ];
    $query->addCondition("RecordTypeID", "('0120y0000007rw7', '012C0000000Hqw0')", "IN");
    $query->order = ["Name"=>"ASC"];
    $results = \Drupal::service('salesforce.client')->query($query);
    $sfoptions = ["" => "Select Project"];
    foreach ($results->records() as $sfid => $data) {
      $sfoptions[$sfid] = $data->field("Name");
    }
    unset($results);

    $mapoptions = [0 => "Select mapping"];
    $mappings = \Drupal::entityTypeManager()->getStorage("salesforce_mapping")->loadMultiple();
    foreach($mappings as $key => $mapping) {
      $mapoptions[$key] = $key;
    }
    unset($mappings);


    $form = [
      'pm' => [
        '#type' => 'fieldset',
        '#title' => 'Project Management',

        'explanation' => [
          '#markup' => "<div class='form-item'>The Salesforce synchronization polls Salesforce every
            few minutes and copies newly added, changed or updated Building
            Housing Project records from Salesforce to Drupal.<br>
            Some information (chatter messages and attachments) are not directly
            monitored and are only updated when their parent Website Update
            record is updated in Salesforce.<br>
            In some cases information may not sync properly between Drupal and
            Salesforce.  This page allows you to manually re-sync data as
            needed.<br>
            <i><b>Note:</b> Buttons on this page ONLY change data in Drupal.
            No records are changed or removed from Salesforce.</i></div>",
        ],

        'pause_auto' => [
          "#type" => "checkbox",
          "#title" => "Pause automated synchronization.",
          "#description" => "This will pause automated salesforce synchronizations until unchecked.<br>
                             <i><b>Note: </b>Synchronizations are temporarily paused while actions are run from this form. This avoids conficts between automated processess and 'manual' processes adding and removing elements from the Salesforce processing queues.</i>",
          '#description_display' => "after",
          "#default_value" => $config->get("pause_auto") ?? 0,
          '#ajax' => [
            'callback' => '::submitForm',
            'event' => 'change',
            'disable-refocus' => TRUE,
            'wrapper' => "edit-cron",
            'progress' => [
              'type' => 'throbber',
            ]
          ],
        ],
        'delete_parcel' => [
          "#type" => "checkbox",
          "#title" => "Delete Project-Parcel Associations.",
          "#description" => "This will also remove/update the project parcel mapping which dramatically slows the remove/update processes. It is best to leave this unchecked.",
          '#description_display' => "after",
          "#default_value" => $config->get("delete_parcel") ?? 0,
          '#ajax' => [
            'callback' => '::submitForm',
            'event' => 'change',
            'disable-refocus' => TRUE,
            'wrapper' => "edit-cron",
            'progress' => [
              'type' => 'throbber',
            ]
          ],

        ],
        'log_actions' => [
          "#type" => "checkbox",
          "#title" => "Log the actions performed from this page.",
          "#description" => "This will append actions to the log file <a href='/sites/default/files/buildinghousing/cleanup.log' target='bhlog'>found here</a>.",
          '#description_display' => "after",
          "#default_value" => $config->get("log_actions") ?? 0,
          '#ajax' => [
            'callback' => '::submitForm',
            'event' => 'change',
            'disable-refocus' => TRUE,
            'wrapper' => "edit-cron",
            'progress' => [
              'type' => 'throbber',
            ]
          ],
        ],
        'del' => [
          "#type" => "button",
          "#value" => "Reset Logfile",
          "#disabled" => !\Drupal::currentUser()->hasPermission('Administer Salesforce mapping'),
          '#attributes' => [
            'class' => ['button--danger'],
            'title' => "This will Erase the contents of the logfile."
          ],
          '#ajax' => [
            'callback' => '::deleteLogfile',
            'event' => 'click',
            'disable-refocus' => FALSE,
            'progress' => [
              'type' => 'throbber',
            ]
          ],
        ],

        'remove' => [
          '#type' => 'fieldset',
          '#title' => 'Remove Specific BH Project from Website',
          '#description' => "Remove one or more Building Housing Projects from the website.",
          '#description_display' => "before",

          'select-container--remove' => [
            "#type" => "container",
            '#attributes' => [
              "class" => "layout-container container-inline",
              "style" => ["display: inline-flex;", "align-items: flex-end; column-gap: 20px;"]
            ],
            'project' => [
              '#type' => "entity_autocomplete",
              '#title' => "Select Project (on Website)",
              "#target_type" => 'node',
              '#selection_settings' => [
                'target_bundles' => ['bh_project'],
                'sort' => ["field" => "field_bh_project_name", "direction" => "ascending"],
              ],
            ],
            'remove' => [
              '#type' => 'button',
              "#value" => "Remove Project",
              "#disabled" =>  !\Drupal::currentUser()->hasPermission('View Salesforce mapping'),
              '#attributes' => [
                'class' => ['button', 'button--primary', "form-item"],
                'title' => "This will remove the Project selected from the website, along with its updates, meetings and documents."
              ],
              '#ajax' => [
                'callback' => '::removeProject',
                'event' => 'click',
                'wrapper' => 'edit-remove',
                'disable-refocus' => FALSE,
                'progress' => [
                  'type' => 'throbber',
                  'message' => "Please wait: Deleting Project & associated data.",
                ]
              ],
            ],
          ],
          'remove-result' => [
            '#markup' => "<div class='js-hide'></div>",
          ],
        ],
        'remove-all' => [
          '#type' => 'fieldset',
          '#title' => 'Remove ALL BH Projects from Website',
          '#description' => "Remove all Building Housing Projects from the website.",
          '#description_display' => "before",

          'select-container--remove-all' => [
            "#type" => "container",
            '#attributes' => [
              "class" => "layout-container container-inline",
              "style" => ["display: inline-flex;", "align-items: flex-end; column-gap: 20px;"]
            ],
            'remove' => [
              '#type' => 'button',
              "#value" => "Remove All",
              "#disabled" => !\Drupal::currentUser()->hasPermission('Administer Salesforce mapping'),
              '#attributes' => [
                'class' => ['button', 'button--primary', "form-item"],
                'title' => "This will remove all Building Housing records, updates, meetings and documents from the website."
              ],
              '#ajax' => [
                'callback' => '::removeAllProjects',
                'event' => 'click',
                'wrapper' => 'edit-remove-all',
                'disable-refocus' => FALSE,
                'progress' => [
                  'type' => 'throbber',
                  'message' => "Please wait: Deleting Project & associated data.",
                ]
              ],
            ],
          ],
          'remove-all-result' => [
            '#markup' => "<div class='js-hide'></div>",
          ],
        ],

        'update' => [
          '#type' => 'fieldset',
          '#title' => 'Update Existing BH Project',
          '#description' => "Update a Building Housing Project already on the website.<br>This forces an immediate website Project sync'd with data in Salesforce.",
          '#description_display' => "before",

          'select-container--update' => [
            "#type" => "container",
            '#attributes' => [
              "class" => "layout-container container-inline",
              "style" => ["display: inline-flex;", "align-items: flex-end; column-gap: 20px;"]
            ],

            'update-project' => [
              '#type' => "entity_autocomplete",
              '#title' => "Select Project (on Website)",
              "#target_type" => 'node',
              '#selection_settings' => [
                'target_bundles' => ['bh_project'],
                'sort' => ["field" => "field_bh_project_name", "direction" => "ascending"],
              ],
            ],
            'update' => [
              '#type' => 'button',
              "#value" => "Update Project",
              "#disabled" =>  !\Drupal::currentUser()->hasPermission('View Salesforce mapping'),
              '#attributes' => [
                'class' => ['button', 'button--primary', "form-item"],
                'title' => "This will sync the Project selected in Drupal with its most recent values from Salesforce, along with new updates, meetings and documents."
              ],
              '#ajax' => [
                'callback' => '::updateProject',
                'event' => 'click',
                'wrapper' => 'edit-update',
                'progress' => [
                  'type' => 'throbber',
                  'message' => "Please wait: Syncing Project & associated data.",
                ]
              ],
            ],
          ],
          'update-result' => [
            '#markup' => "<div class='js-hide'></div>",
          ],
        ],

        'overwrite' => [
          '#type' => 'fieldset',
          '#title' => 'Overwrite BH Project',
          '#description' => "Overwrite (delete then re-import) a Building Housing Project with current data from Salesforce.",
          '#description_display' => "before",

          'select-container--overwrite' => [
            "#type" => "container",
            '#attributes' => [
              "class" => "layout-container container-inline",
              "style" => ["display: inline-flex;", "align-items: flex-end; column-gap: 20px;"]
            ],
            'overwrite_project' => [
              '#type' => 'select',
              '#attributes' => ["placeholder" => "Project Ids"],
              "#title" => "Select Salesforce Project",
              '#options' => $sfoptions,
            ],
            'overwrite' => [
              '#type' => 'button',
              "#value" => "Overwite Project",
              "#disabled" =>  !\Drupal::currentUser()->hasPermission('View Salesforce mapping'),
              '#attributes' => [
                'class' => ['button', 'button--primary', 'form-item'],
                'title' => "This will preform a full re-import the Selected SF Project, along with its updates, meetings and documents (replacing the existing data in Drupal)."
              ],
              '#ajax' => [
                'callback' => '::overwriteProject',
                'event' => 'click',
                'wrapper' => 'edit-overwrite',
                'disable-refocus' => FALSE,
                'progress' => [
                  'type' => 'throbber',
                  'message' => "Please wait: Ovewriting Project & associated data.",
                ]
              ],
            ],
          ],
          'overwrite-result' => [
            '#markup' => "<div class='js-hide'></div>",
          ],
        ],
        'overwrite-all' => [
          '#type' => 'fieldset',
          '#title' => 'Overwrite All BH Projects',
          '#description' => "Overwrite (delete then re-import) ALL Building Housing Projects from Salesforce.",
          '#description_display' => "before",
          'select-container--overwrite-all' => [
            "#type" => "container",
            '#attributes' => [
              "class" => "layout-container container-inline",
              "style" => ["display: inline-flex;", "align-items: flex-end; column-gap: 20px;"]
            ],
            'overwrite' => [
              '#type' => 'button',
              "#value" => "Overwrite All",
              "#disabled" =>  !\Drupal::currentUser()->hasPermission('Administer Salesforce mapping'),
              '#attributes' => [
                'class' => ['button', 'button--primary', 'form-item'],
                'title' => "This will preform a full re-import for all SF Projects, along with updates, meetings and documents (replacing the existing data in Drupal)."
              ],
              '#ajax' => [
                'callback' => '::overwriteAllProjects',
                'event' => 'click',
                'wrapper' => 'edit-overwrite-all',
                'disable-refocus' => FALSE,
                'progress' => [
                  'type' => 'throbber',
                  'message' => "Please wait: Overwriting All Projects & associated data.",
                ]
              ],
            ],
          ],
          'overwrite-all-result' => [
            '#markup' => "<div class='js-hide'></div>",
          ],
        ],

        'pull-management' => [
          '#type' => 'fieldset',
          '#title' => 'Salesforce Pull Management',
          '#description' => "Allows some control over the last updated date for sync's.",
          '#description_display' => "before",

          'select-container--pull-management' => [
            "#type" => "container",
            '#attributes' => [
              "class" => "layout-container container-inline",
              "style" => ["display: inline-flex;", "align-items: flex-end; column-gap: 20px;"]
            ],
            'mapping' => [
              '#type' => 'select',
              '#attributes' => ["placeholder" => "Mapping"],
              '#options' => $mapoptions,
            ],
            'time' => [
              '#type' => 'textfield',
              '#attributes' => ["style" => ["width:150px"]],
              '#default_value' => date("Y-m-d H:i:s", strtotime("now")),
            ],
            'reset' => [
              '#type' => 'button',
              "#value" => "Set Last Run Time",
              "#disabled" =>  !\Drupal::currentUser()->hasPermission('View Salesforce mapping'),
              '#attributes' => [
                'class' => ['button', 'button--primary', "form-item"],
                'title' => "This will set the Last Run Date (high water mark) for the mapping.<br>The next sync of this mapping will only import records updated after this date."
              ],
              '#ajax' => [
                'callback' => '::resetLastRunDate',
                'event' => 'click',
                'wrapper' => 'edit-pull-management',
                'disable-refocus' => FALSE,
                'progress' => [
                  'type' => 'throbber',
                  'message' => "Please wait: Resetting last-run-time.",
                ]
              ],
            ],
          ],
          'pull-result' => [
            '#markup' => "<div class='js-hide'></div>",
          ],
        ],
      ],
    ];

//    $form = parent::buildForm($form, $form_state);

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('node_buildinghousing.settings');
    $config->set('pause_auto', $form_state->getValue('pause_auto'));
    $config->set('delete_parcel', $form_state->getValue('delete_parcel'));
    $config->set('log_actions', $form_state->getValue('log_actions'));
    $config->save();
    parent::submitForm($form, $form_state);
  }

  public function deleteLogfile(array &$form, FormStateInterface $form_state) {
    unlink("public://buildinghousing/cleanup.log");
    BuildingHousingUtils::log("cleanup", "File Reset.\n", TRUE);
  }

  public function removeProject(array &$form, FormStateInterface $form_state)  {

    \Drupal::messenger()->deleteAll();
    $config = $this->config('node_buildinghousing.settings');
    $log = $config->get("log_actions");

    if ($nid = $form_state->getValue("project")) {

      $log && BuildingHousingUtils::log("cleanup", "START Single Project Removal.\n", TRUE);

      BuildingHousingUtils::delete_bh_project([$nid], TRUE, $log);

      $form["pm"]["remove"]["remove-result"] = ["#markup" => "
      <div class='form-item color-warning'>
        Project: {$form["pm"]["remove"]["select-container--remove"]["project"]["#value"]}<br/>
        <img src='/core/misc/icons/73b355/check.svg' /> <b>Project removed</b>
      </div>"];
    }
    else {
      $form["pm"]["remove"]["remove-result"] = ["#markup" => "
        <div class='form-item color-warning'>
            Project: {$form["pm"]["remove"]["select-container--remove"]["project"]["#value"]}<br/>
          <img src='/core/misc/icons/e29700/warning.svg' /> <b>Project not found: Nothing Done</b>
        </div>"];
    }

    $log && BuildingHousingUtils::log("cleanup", "END Single Project Removal.\n", TRUE);

    $form["pm"]["remove"]["select-container--remove"]["project"]["#value"] = "";
    $form["pm"]["remove"]["#id"] = "edit-remove";
    return $form["pm"]["remove"];
  }

  public function removeAllProjects(array &$form, FormStateInterface $form_state)  {

    $existing_project_count = 0;

    \Drupal::messenger()->deleteAll();
    $config = $this->config('node_buildinghousing.settings');
    $log = $config->get("log_actions");

    $log && BuildingHousingUtils::log("cleanup", "START ALL Project Removal.\n", TRUE);

    $existing_project_count = BuildingHousingUtils::delete_all_bh_objects(TRUE, $log);

    $log && BuildingHousingUtils::log("cleanup", "END ALL Project Removal.\n", TRUE);

    $form["pm"]["remove-all"]["remove-all-result"] = ["#markup" => "
      <div class='form-item color-success'>
        <img src='/core/misc/icons/73b355/check.svg' /> <b>All Projects ({$existing_project_count}) removed</b>
       </div>"];
    $form["pm"]["remove-all"]["#id"] = "edit-remove-all";
    return $form["pm"]["remove-all"];
  }

  public function updateProject(array &$form, FormStateInterface $form_state)  {

    $existing_project_count = 0;
    $new_projects = 0;
    $count = 0;

    \Drupal::messenger()->deleteAll();
    $config = $this->config('node_buildinghousing.settings');
    $log = $config->get("log_actions");

    $log && BuildingHousingUtils::log("cleanup", "START Project Update.\n", TRUE);

    $rem_cron = $this->config('node_buildinghousing.settings')->get('pause_auto');
    $this->toggleAuto(0);

    if ($nid = $form_state->getValue("update-project")) {
      if ($sf = \Drupal::entityTypeManager()
        ->getStorage("salesforce_mapped_object")
        ->loadByProperties(["drupal_entity" => $nid])) {
        $sf = reset($sf);
        $sfid = $sf->get("salesforce_id")->value;
        $new_projects = $this->enqueueSfRecords($sfid, $log);
      }

      if ($new_projects == 1) {
        $count = $this->processSfQueue($log);
        $form["pm"]["update"]["update-result"] = ["#markup" => "
          <div class='form-item color-success;'>
            Project: {$form["pm"]["update"]["select-container--update"]["update-project"]["#value"]}<br/>
            <img src='/core/misc/icons/73b355/check.svg' /> <b>{$new_projects} Project updated using {$count} SF objects</b>
          </div>"];
      }
    }

    if ($new_projects == 0) {
      $form["pm"]["update"]["update-result"] = ["#markup" => "
        <div class='form-item color-warning'>
          Project: {$form["pm"]["update"]["select-container--update"]["update-project"]["#value"]}<br/>
          <img src='/core/misc/icons/e29700/warning.svg' /> <b>Project not found: Nothing Done</b>
        </div>"];
    }

    $log && BuildingHousingUtils::log("cleanup", "END Project Update.\n", TRUE);

    $this->toggleAuto($rem_cron);
    $form["pm"]["update"]["select-container--update"]["update-project"]["#value"] = "";
    $form["pm"]["update"]["#id"] = "edit-update";
    return $form["pm"]["update"];
  }

  public function overwriteProject(array &$form, FormStateInterface $form_state)  {

    $existing_project_count = 0;
    $new_projects = 0;
    $count = 0;
    $rem_cron = $this->config('node_buildinghousing.settings')->get('pause_auto');
    $this->toggleAuto(0);

    \Drupal::messenger()->deleteAll();
    $config = $this->config('node_buildinghousing.settings');
    $log = $config->get("log_actions");

    $log && BuildingHousingUtils::log("cleanup", "START Project Overwrite.\n", TRUE);

    if ($sfid = $form_state->getValue("overwrite_project")) {
      if ($nid = \Drupal::entityTypeManager()
        ->getStorage("salesforce_mapped_object")
        ->loadByProperties(["salesforce_id" => $sfid])) {
        $nid = reset($nid);
        $existing_project_count = BuildingHousingUtils::delete_bh_project([$nid->get("drupal_entity")[0]->target_id], TRUE, $log);
      }
      $sf_project_name = "{$form["pm"]["overwrite"]["select-container--overwrite"]["overwrite_project"]["#options"][$sfid]} ({$sfid})";
      $sfid = new SFID($sfid);

      $new_projects = $this->enqueueSfRecords($sfid, $log);
      if ($new_projects > 0) {
        $count = $this->processSfQueue($log);
      }

      if ($existing_project_count == 0) {
        $form["pm"]["overwrite"]["overwrite-result"] = ["#markup" => "
          <div class='form-item color-success;'>
            Project: {$sf_project_name}<br/>
            <img src='/core/misc/icons/73b355/check.svg' />
            <b>New Drupal Project created from {$new_projects} Salesforce Project using {$count} Salesforce objects</b>
          </div>"];
      }
      elseif ($new_projects > 0) {
        $form["pm"]["overwrite"]["overwrite-result"] = ["#markup" => "
          <div class=' form-item color-success;'>
            Project: {$sf_project_name}<br/>
            <img src='/core/misc/icons/73b355/check.svg' />
            <b>{$existing_project_count} Drupal Project overwritten with {$new_projects} Salesforce Project using {$count} Salesforce objects</b>
          </div>"];
      }
    }

    if ($new_projects == 0) {
      $form["pm"]["overwrite"]["overwrite-result"] = ["#markup" => "<div class='form-item color-warning'>
        Project: {$sf_project_name}<br/>
        <img src='/core/misc/icons/e29700/warning.svg' /> <b>Project not found: Nothing Done</b>
      </div>"];
    }

    $log && BuildingHousingUtils::log("cleanup", "END Project Overwrite.\n", TRUE);

    $this->toggleAuto($rem_cron);
    $form["pm"]["overwrite"]["select-container--pull-management"]["mapping"]["#value"] = "";
    $form["pm"]["overwrite"]["#id"] = "edit-overwrite";
    return $form["pm"]["overwrite"];

  }

  public function overwriteAllProjects(array &$form, FormStateInterface $form_state)  {

    $existing_project_count = 0;
    $new_projects = 0;
    $count = 0;

    \Drupal::messenger()->deleteAll();
    $config = $this->config('node_buildinghousing.settings');
    $log = $config->get("log_actions");

    $rem_cron = $this->config('node_buildinghousing.settings')->get('pause_auto');
    $this->toggleAuto(0);

    BuildingHousingUtils::log("cleanup", "START ALL Project Overwrite.\n", TRUE);

    $existing_project_count = BuildingHousingUtils::delete_all_bh_objects(TRUE, $log);
    $new_projects = $this->enqueueSfRecords(NULL, $log);

    if ($new_projects > 0 || $count > 0) {
      $count = $this->processSfQueue($log);
      $form["pm"]["overwrite-all"]["overwrite-all-result"] = ["#markup" => "
        <div class='form-item color-warning'>
          <img src='/core/misc/icons/e29700/warning.svg' />
          <b>{$existing_project_count} Drupal Projects overwritten with {$new_projects} Salesforce Projects using {$count} Salesforce objects</b>
        </div>"];
    }

    if ($new_projects == 0) {
      $form["pm"]["overwrite-all"]["overwrite-all-result"] = ["#markup" => "
        <div class='form-item color-warning'>
          <img src='/core/misc/icons/e29700/warning.svg' />
          <b>No projects found! - Nothing Done</b>
        </div>"];
    }

    BuildingHousingUtils::log("cleanup", "END ALL Project Overwrite.\n", TRUE);

    $this->toggleAuto($rem_cron);
    $form["pm"]["overwrite-all"]["#id"] = "edit-overwrite-all";
    return $form["pm"]["overwrite-all"];
  }

  public function resetLastRunDate(array &$form, FormStateInterface $form_state) {

    if (($mapping = $form_state->getValue("mapping"))
      && ($time = $form_state->getValue("time"))) {

      if (strtotime($time) === FALSE) {
        $form["pm"]["pull-management"]["pull-result"] = ["#markup" => "
            <div class='form-item color-warning'>
              Mapping: {$mapping}<br/>
              <img src='/core/misc/icons/e29700/warning.svg' /> <b>Time needs to be in the format Y-M-D H:M:S</b>
            </div>",];

      }
      if ($pull_info = \Drupal::state()->get('salesforce.mapping_pull_info')) {

        if (isset($pull_info[$mapping])) {
          $pull_info[$mapping]["last_pull_timestamp"] = strtotime($time);
          \Drupal::state()->set('salesforce.mapping_pull_info', $pull_info);

          $newtime = \Drupal::state()
            ->get('salesforce.mapping_pull_info')[$mapping]["last_pull_timestamp"];
          $newtime = date("Y-m-d H:i:s", $newtime);

          $form["pm"]["pull-management"]["pull-result"] = ["#markup" => "
            <div class='form-item color-success'>
              Mapping: {$mapping}<br/>
              <img src='/core/misc/icons/73b355/check.svg' /> <b>Last Run Time reset to {$newtime}</b>
            </div>",];

        }
        else {
          $form["pm"]["pull-management"]["pull-result"] = ["#markup" => "
            <div class='form-item color-warning'>
              Mapping: {$mapping}<br/>
              <img src='/core/misc/icons/e29700/warning.svg' /> <b>Mapping not found: Nothing Done</b>
            </div>",];
        }
      }

    }
    else {
      $form["pm"]["pull-management"]["pull-result"] = ["#markup" => "
        <div class='form-item color-warning'>
          <img src='/core/misc/icons/e29700/warning.svg' /> <b>Mapping or time missing</b>
        </div>",];
    }

    $form["pm"]["overwrite"]["select-container--overwrite"]["overwrite_project"]["#value"] = "";
    $form["pm"]["pull-management"]["#id"] = "edit-pull-management";
    return $form["pm"]["pull-management"];
  }

  private function enqueueSfRecords($sfid = NULL) {

    $container = \Drupal::getContainer();
    $this->processor = new QueueHandler(
      $this->client,
      $container->get('entity_type.manager'),
      $container->get('queue.database'),
      $container->get('config.factory'),
      $container->get('event_dispatcher'),
      $container->get('datetime.time')
    );

    $map = \Drupal::entityTypeManager()->getStorage("salesforce_mapping");

    if (!empty($sfid)) {

      $id = new SFID($sfid);

      $count = $this->getSingleRecord($map->load("building_housing_projects"), (string) $id, TRUE, $log);

      if ($count == 1) {

        $query = new SelectQuery('ParcelProject_Association__c');
        $query->fields = ['Id', "Project__c", "Parcel__c"];
        $query->addCondition("Project__c", "'{$sfid}'", "=");
        $results = $this->client->query($query);
        foreach ($results->records() as $data) {
          $this->getSingleRecord($map->load("building_housing_parcels"), $data->field("Parcel__c"), TRUE, $log);
          $this->getSingleRecord($map->load("bh_parcel_project_assoc"), $data->field("Id"), TRUE, $log);
        }

        $query = new SelectQuery('Website_Update__c');
        $query->fields = ['Id', "Project__c"];
        $query->addCondition("Project__c", "'{$sfid}'", "=");
        $results = $this->client->query($query);
        foreach ($results->records() as $data) {
          $this->getSingleRecord($map->load("bh_website_update"), $data->field("Id"), TRUE, $log);

          $query1 = new SelectQuery('Community_Meeting_Event__c');
          $query1->fields = ['Id', "website_update__c"];
          $query1->addCondition("website_update__c", "'{$data->field("Id")}'", "=");
          $results1 = $this->client->query($query1);
          foreach ($results1->records() as $data1) {
            $this->getSingleRecord($map->load("bh_community_meeting_event"), $data1->field("Id"), TRUE, $log);
          }

        }

      }

    }

    else {
      $count = $this->processor->getUpdatedRecordsForMapping($map->load("building_housing_projects"), TRUE, 1420070400, strtotime("now"));
      $log && BuildingHousingUtils::log("cleanup", "QUEUED {$count} record/s from Salesforce using 'building_housing_projects' mapping.\n");

      $config = \Drupal::config('node_buildinghousing.settings');
      if ($config->get('delete_parcel') ?? FALSE) {
        $c = $this->processor->getUpdatedRecordsForMapping($map->load("building_housing_parcels"), TRUE, 1420070400, strtotime("now"));
        $log && BuildingHousingUtils::log("cleanup", "QUEUED {$c} record/s from Salesforce using 'building_housing_parcels' mapping.\n");
        $c = $this->processor->getUpdatedRecordsForMapping($map->load("bh_parcel_project_assoc"), TRUE, 1420070400, strtotime("now"));
        $log && BuildingHousingUtils::log("cleanup", "QUEUED {$c} record/s from Salesforce using 'bh_parcel_project_assoc' mapping.\n");
      }
      $c = $this->processor->getUpdatedRecordsForMapping($map->load("bh_website_update"), TRUE, 1420070400, strtotime("now"));
      $log && BuildingHousingUtils::log("cleanup", "QUEUED {$c} record/s from Salesforce using 'bh_website_update' mapping.\n");
      $c = $this->processor->getUpdatedRecordsForMapping($map->load("bh_community_meeting_event"), TRUE, 1420070400, strtotime("now"));
      $log && BuildingHousingUtils::log("cleanup", "QUEUED {$c} record/s from Salesforce using 'bh_community_meeting_event' mapping.\n");
    }

    return $count;
  }

  private function processSfQueue($log) {
    $queue_factory = \Drupal::service('queue');
    $queue_manager = \Drupal::service('plugin.manager.queue_worker');
    $queue_worker = $queue_manager->createInstance('cron_salesforce_pull');
    $queue = $queue_factory->get('cron_salesforce_pull');
    $count = 0;
    while($item = $queue->claimItem()) {
      try {
        $queue_worker->processItem($item->data);
        $queue->deleteItem($item);
        $count++;
      }
      catch (DelayedRequeueException $e) {
        if ($queue instanceof DelayableQueueInterface) {
          $queue->delayItem($item, $e->getDelay());
        }
      }
      catch (RequeueException $e) {
        $queue->releaseItem($item);
      }
      catch (SuspendQueueException $e) {
        $queue->releaseItem($item);
        continue;
      }
    }
    return $count;
  }

  private function getSingleRecord($mapping, $id, $force_pull, $log) {

    try {
      $soql = $mapping->getPullQuery();
      $_SERVER["argv"] = ($force_pull ? ["--force-pull"] : []);
      $this->eventDispatcher->dispatch(
        new SalesforceQueryEvent($mapping, $soql),
        SalesforceEvents::PULL_QUERY
      );
      $soql->conditions[] = ["field" => "Id", "operator" => "=", "value" => "'{$id}'"];
      $results = $this->client->query($soql);
      if ($results) {
        $this->processor->enqueueAllResults($mapping, $results, $force_pull);
        $log && BuildingHousingUtils::log("cleanup", "QUEUED {$results->size()} record/s from Salesforce using {$mapping->id()} mapping.\n");
        return $results->size();
      }
    }
    catch (\Exception $e) {
      $message = '%type: @message in %function (line %line of %file).';
      $args = Error::decodeException($e);
      $this->eventDispatcher->dispatch(new SalesforceErrorEvent($e, $message, $args), SalesforceEvents::ERROR);
    }
    return 0;
  }

  private function toggleAuto($enabled) {
    if (is_bool($enabled)) {
      $enabled = $enabled ? 1 : 0;
    }
    $this->config('node_buildinghousing.settings')
      ->set('pause_auto', $enabled)
      ->save();
  }

}
