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
    $results = \Drupal::service('salesforce.client')->query($query);
    $sfoptions = [];
    foreach ($results->records() as $sfid => $data) {
      $sfoptions[$sfid] = $data->field("Name");
    }

    $form = [
      'pm' => [
        '#type' => 'fieldset',
        '#title' => 'Property Management',

        'explanation' => [
          '#markup' => "<div class='form-item'>The Salesforce synchronization polls Salesforce every
            few minutes and copies newly added, changed or updated Building
            Housing Property records from Salesforce to Drupal.<br>
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
          "#decription" => "This will delete the project parcel mapping which dramatically slows the remove/update processes.",
          '#description_display' => "before",
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

        'remove' => [
          '#type' => 'fieldset',
          '#title' => 'Remove Specific BH Property from Website',
          '#description' => "Remove one or more Building Housing Properties from Drupal (the website).",
          '#description_display' => "before",

          'select-container--remove' => [
            "#type" => "container",
            '#attributes' => [
              "class" => "layout-container container-inline",
              "style" => ["display: inline-flex;", "align-items: flex-end; column-gap: 20px;"]
            ],

            'property' => [
              '#type' => "entity_autocomplete",
              '#title' => "Select Property (on Website)",
              "#target_type" => 'node',
              '#selection_settings' => [
                'target_bundles' => ['bh_project'],
                'sort' => ["field" => "field_bh_project_name", "direction" => "ascending"],
              ],
            ],
            'remove' => [
              '#type' => 'button',
              "#value" => "Remove Property",
              "#disabled" =>  !\Drupal::currentUser()->hasPermission('View Salesforce mapping'),
              '#attributes' => [
                'class' => ['button', 'button--primary', "form-item"],
                'title' => "This will delete the Property selected, along with its updates, meetings and documents."
              ],
              '#ajax' => [
                'callback' => '::deleteProperty',
                'event' => 'click',
                'wrapper' => 'remove-result',
                'disable-refocus' => FALSE,
                'progress' => [
                  'type' => 'throbber',
                  'message' => "Please wait: Deleting property & associated data.",
                ]
              ],
            ],
          ],
          'result1' => [
            '#markup' => "<div id='remove-result' class='js-hide remove-result'></div>",
          ],
        ],
        'remove_all' => [
          '#type' => 'fieldset',
          '#title' => 'Remove ALL BH Properties from Website',
          '#description' => "Remove all Building Housing Properties from the website.",
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
              'callback' => '::deleteAllProperties',
              'event' => 'click',
              'wrapper' => 'remove-all',
              'disable-refocus' => FALSE,
              'progress' => [
                'type' => 'throbber',
                'message' => "Please wait: Deleting property & associated data.",
              ]
            ],
          ],
          ],
          'result' => [
            '#markup' => "<div id='remove-all' class='js-hide remove-all'></div>",
          ],
        ],

        'update' => [
          '#type' => 'fieldset',
          '#title' => 'Update Existing BH Property',
          '#description' => "Update a Building Housing Property already in Drupal with a complete import from Salesforce.",
          '#description_display' => "before",

          'select-container--update' => [
            "#type" => "container",
            '#attributes' => [
              "class" => "layout-container container-inline",
              "style" => ["display: inline-flex;", "align-items: flex-end; column-gap: 20px;"]
            ],

            'update_property' => [
              '#type' => "entity_autocomplete",
              '#title' => "Select Property (on Website)",
              "#target_type" => 'node',
              '#selection_settings' => [
                'target_bundles' => ['bh_project'],
                'sort' => ["field" => "field_bh_project_name", "direction" => "ascending"],
              ],
            ],
            'update' => [
              '#type' => 'button',
              "#value" => "Update Property",
              "#disabled" =>  !\Drupal::currentUser()->hasPermission('View Salesforce mapping'),
              '#attributes' => [
                'class' => ['button', 'button--primary', "form-item"],
                'title' => "This will sync the Property selected in Drupal with its value from Salesforce, along with its updates, meetings and documents."
              ],
              '#ajax' => [
                'callback' => '::updateProperty',
                'event' => 'click',
                'wrapper' => 'update-result',
                'progress' => [
                  'type' => 'throbber',
                  'message' => "Please wait: Syncing property & associated data.",
                ]
              ],
            ],
          ],
          'result' => [
            '#markup' => "<div id='update-result' class='js-hide update-result'></div>",
          ],
        ],
        'overwrite' => [
          '#type' => 'fieldset',
          '#title' => 'Overwrite BH Property',
          '#description' => "Overwrite (delete then re-import) a Building Housing Property from Salesforce.",
          '#description_display' => "before",

          'select-container--overwrite' => [
            "#type" => "container",
            '#attributes' => [
              "class" => "layout-container container-inline",
              "style" => ["display: inline-flex;", "align-items: flex-end; column-gap: 20px;"]
            ],
            'overwrite_property' => [
            '#type' => 'select',
            '#attributes' => ["placeholder" => "Property Ids"],
            "#title" => "Select Salesforce Property",
            '#options' => $sfoptions,
          ],
            'overwrite' => [
            '#type' => 'button',
            "#value" => "Overwite Property",
            "#disabled" =>  !\Drupal::currentUser()->hasPermission('View Salesforce mapping'),
            '#attributes' => [
              'class' => ['button', 'button--primary', 'form-item'],
              'title' => "This will import the Selected SF Property, along with its updates, meetings and documents overwriting any data in Drupal."
            ],
            '#ajax' => [
              'callback' => '::overwriteProperty',
              'event' => 'click',
              'wrapper' => 'overwrite-result',
              'disable-refocus' => FALSE,
              'progress' => [
                'type' => 'throbber',
                'message' => "Please wait: Ovewriting property & associated data.",
              ]
            ],
          ],
          ],
          'result' => [
            '#markup' => "<div id='overwrite-result' class='js-hide overwrite-result'></div>",
          ],
        ],
        'overwrite_all' => [
          '#type' => 'fieldset',
          '#title' => 'Overwrite All BH Properties',
          '#description' => "Overwrite (delete then re-import) ALL Building Housing Properties from Salesforce.",
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
              'title' => "This will re-import all Salesforce Properties, along with their updates, meetings and documents."
            ],
            '#ajax' => [
              'callback' => '::overwriteAllProperties',
              'event' => 'click',
              'wrapper' => 'overwrite-all-result',
              'disable-refocus' => FALSE,
              'progress' => [
                'type' => 'throbber',
                'message' => "Please wait: Overwriting All Properties & associated data.",
              ]
            ],
          ],
          ],
          'result' => [
            '#markup' => "<div id='overwrite-all-result' class='js-hide overwrite-all-result'></div>",
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
    $config->save();
    parent::submitForm($form, $form_state);
  }

  public function deleteProperty(array &$form, FormStateInterface $form_state)  {
    if ($nid = $form_state->getValue("property")) {
      BuildingHousingUtils::delete_bh_project([$nid], TRUE);
      return ["#markup" => "
          <div id='remove-result' class='remove-result form-item color-success;'>
            <img src='/core/misc/icons/73b355/check.svg' />
            Property removed
          </div>",
      ];
    }
    return ["#markup" => "
      <div id='remove-result' class='remove-result form-item color-warning'>
        <img src='/core/misc/icons/e29700/warning.svg' />
        Nothing Done.
      </div>"];
  }

  public function deleteAllProperties(array &$form, FormStateInterface $form_state)  {
    $existing_project_count = BuildingHousingUtils::delete_all_bh_objects(TRUE);
    return ["#markup" => "<div id='remove-all' class='remove-all form-item' style='color: green;'><img src='/core/misc/icons/73b355/check.svg' /> {$existing_project_count}  Properties removed</div>"];
  }

  public function updateProperty(array &$form, FormStateInterface $form_state)  {

    $text = "";
    $existing_project_count = 0;
    $new_projects = 0;
    $count = 0;

    $rem_cron = $this->config('node_buildinghousing.settings')->get('pause_auto');
    $this->toggleAuto(0);

    if ($nid = $form_state->getValue("update_property")) {
      if ($sf = \Drupal::entityTypeManager()
        ->getStorage("salesforce_mapped_object")
        ->loadByProperties(["drupal_entity" => $nid])) {
        $sf = reset($sf);
        $sfid = $sf->get("salesforce_id")->value;
        $new_projects = $this->enqueueSfRecords($sfid);
      }

      if ($new_projects == 1) {
        $count = $this->processSfQueue();
        $text = "<div id='update-result' class='update-result form-item color-success;'>
            <img src='/core/misc/icons/73b355/check.svg' />
            {$new_projects} Property updated using {$count} SF objects
          </div>";
      }
    }

    if ($text == "") {
      $text = "<div id='update-result' class='update-result form-item color-warning'>
        <img src='/core/misc/icons/e29700/warning.svg' />
        Nothing Done.
      </div>";
    }

    $this->toggleAuto($rem_cron);
    return ["#markup" =>  $text];
  }

  public function overwriteProperty(array &$form, FormStateInterface $form_state)  {

    $text = "";
    $existing_project_count = 0;
    $new_projects = 0;
    $count = 0;
    $rem_cron = $this->config('node_buildinghousing.settings')->get('pause_auto');
    $this->toggleAuto(0);

    if ($sfid = $form_state->getValue("overwrite_property")) {
      if ($nid = \Drupal::entityTypeManager()
        ->getStorage("salesforce_mapped_object")
        ->loadByProperties(["salesforce_id" => $sfid])) {
        $nid = reset($nid);
        $existing_project_count = BuildingHousingUtils::delete_bh_project([$nid->get("drupal_entity")[0]->target_id], TRUE);
      }
      $sfid = new SFID($sfid);
      $new_projects = $this->enqueueSfRecords($sfid);
      $count = $this->processSfQueue();

      if ($existing_project_count == 0) {
        $text = "<div id='overwrite-result' class='overwrite-result form-item color-success;'>
            <img src='/core/misc/icons/73b355/check.svg' />
            New Drupal Property overwritten with {$new_projects} Salesforce Property using {$count} Salesforce objects
          </div>";
      }
      elseif ($new_projects > 0) {
        $text = "<div id='overwrite-result' class='overwrite-result form-item color-success;'>
            <img src='/core/misc/icons/73b355/check.svg' />
            {$existing_project_count} Drupal Property overwritten with {$new_projects} Salesforce Property using {$count} Salesforce objects
          </div>";
      }
    }

    if ($text == "" ) {
      $text = "<div id='overwrite-result' class='overwrite-result form-item color-warning'>
        <img src='/core/misc/icons/e29700/warning.svg' />
        Nothing Done.
      </div>";
    }

    $this->toggleAuto($rem_cron);
    return ["#markup" =>  $text];

  }

  public function overwriteAllProperties(array &$form, FormStateInterface $form_state)  {

    $text = "";
    $existing_project_count = 0;
    $new_projects = 0;
    $count = 0;
    $rem_cron = $this->config('node_buildinghousing.settings')->get('pause_auto');
    $this->toggleAuto(0);

    $existing_project_count = BuildingHousingUtils::delete_all_bh_objects(TRUE);
    $new_projects = $this->enqueueSfRecords();
    if ($new_projects > 0 ) {
      $count = $this->processSfQueue();
      $text = "<div id='overwrite-all-result' class='overwrite-all-result form-item color-warning'>
        <img src='/core/misc/icons/e29700/warning.svg' />
        {$existing_project_count} Drupal Properties overwritten with {$new_projects} Salesforce Properties using {$count} Salesforce objects.</div>";
    }

    if ($text == "" ) {
      $text = "<div id='overwrite-all-result' class='overwrite-all-result form-item color-warning'>
        <img src='/core/misc/icons/e29700/warning.svg' />
        Nothing Done.
      </div>";
    }

    $this->toggleAuto($rem_cron);
    return ["#markup" =>  $text];

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

      $count = $this->getSingleRecord($map->load("building_housing_projects"), (string) $id, TRUE);

      if ($count == 1) {

        $query = new SelectQuery('ParcelProject_Association__c');
        $query->fields = ['Id', "Project__c"];
        $query->addCondition("Project__c", "'{$sfid}'", "=");
        $results = $this->client->query($query);
        foreach ($results->records() as $data) {
          $this->getSingleRecord($map->load("bh_parcel_project_assoc"), $data->field("Id"), TRUE);
        }

        $query = new SelectQuery('Website_Update__c');
        $query->fields = ['Id', "Project__c"];
        $query->addCondition("Project__c", "'{$sfid}'", "=");
        $results = $this->client->query($query);
        foreach ($results->records() as $data) {
          $this->getSingleRecord($map->load("bh_website_update"), $data->field("Id"), TRUE);

          $query1 = new SelectQuery('Community_Meeting_Event__c');
          $query1->fields = ['Id', "website_update__c"];
          $query1->addCondition("website_update__c", "'{$data->field("Id")}'", "=");
          $results1 = $this->client->query($query1);
          foreach ($results1->records() as $data1) {
            $this->getSingleRecord($map->load("bh_community_meeting_event"), $data1->field("Id"), TRUE);
          }

        }

      }

    }

    else {
      $count = $this->processor->getUpdatedRecordsForMapping($map->load("building_housing_projects"), TRUE);
      $this->processor->getUpdatedRecordsForMapping($map->load("bh_parcel_project_assoc"), TRUE);
      $this->processor->getUpdatedRecordsForMapping($map->load("bh_website_update"), TRUE);
      $this->processor->getUpdatedRecordsForMapping($map->load("bh_community_meeting_event"), TRUE);
    }

    return $count;
  }

  private function processSfQueue() {
    $queue_factory = \Drupal::service('queue');
    $queue_manager = \Drupal::service('plugin.manager.queue_worker');
    $queue_worker = $queue_manager->createInstance('cron_salesforce_pull');
    $queue = $queue_factory->get('cron_salesforce_pull');
    $count = $queue->numberOfItems();
    while($item = $queue->claimItem()) {
      try {
        $queue_worker->processItem($item->data);
        $queue->deleteItem($item);
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

  private function getSingleRecord($mapping, $id, $force_pull) {
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
