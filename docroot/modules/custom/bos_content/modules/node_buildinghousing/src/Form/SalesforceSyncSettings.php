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

    $nodes = \Drupal::entityTypeManager()
      ->getStorage("node")
      ->loadByProperties(["type" => "bh_project"]);
    $options = [];
    foreach($nodes as $node) {
      $options[$node->id()] = $node->get("field_bh_project_name")[0]->value;
    }
    unset($nodes);

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

        'cron' => [
          "#type" => "checkbox",
          "#title" => "Pause automated synchronization.",
          "#default_value" => $config->get("cron"),
          '#ajax' => [
            'callback' => '::submitForm',
            'event' => 'change',
            'wrapper' => "edit-cron",
            'progress' => [
              'type' => 'throbber',
            ]
          ],

        ],

        'remove' => [
          '#type' => 'fieldset',
          '#title' => 'Delete Specific Properties',
          '#description' => "Deletes one or more properties from the website.",
          'property' => [
            '#type' => 'select',
            '#attributes' => ["placeholder" => "Property Ids"],
            '#options' => $options,
          ],
          'remove' => [
            '#type' => 'button',
            "#value" => "Remove Property",
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
          'result' => [
            '#markup' => "<div id='remove-result' class='js-hide remove-result'></div>",
          ],
        ],
        'remove_all' => [
          '#type' => 'fieldset',
          '#title' => 'Delete All Properties',
          '#description' => "Deletes all Building Housing properties from the website.",
          'remove' => [
            '#type' => 'button',
            "#value" => "Remove All",
            '#tooltip' => 'foo',
            '#attributes' => [
              'class' => ['button', 'button--primary', "form-item"],
              'title' => "This will delete all Building Housing records, updates, meetings and documents."
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
          'result' => [
            '#markup' => "<div id='remove-all' class='js-hide remove-all'></div>",
          ],
        ],

        'update' => [
          '#type' => 'fieldset',
          '#title' => 'Update Existing Property Now',
          '#description' => "Updates a property already in Drupal with data from Salesforce.",
          'update_property' => [
            '#type' => 'select',
            '#attributes' => ["placeholder" => "Property Ids"],
            '#options' => $options,
          ],
          'update' => [
            '#type' => 'button',
            "#value" => "Update Property",
            '#attributes' => [
              'class' => ['button', 'button--primary', "form-item"],
              'title' => "This will sync the Property selected in Drupal with its value from Salesforce, along with its updates, meetings and documents."
            ],
            '#ajax' => [
              'callback' => '::updateProperty',
              'event' => 'click',
              'wrapper' => 'update-result',
              'disable-refocus' => FALSE,
              'progress' => [
                'type' => 'throbber',
                'message' => "Please wait: Syncing property & associated data.",
              ]
            ],
          ],
          'result' => [
            '#markup' => "<div id='update-result' class='js-hide update-result'></div>",
          ],
        ],
        'overwrite' => [
          '#type' => 'fieldset',
          '#title' => 'Overwrite Property',
          '#description' => "Overwrites a Property using data from SF.",
          'overwrite_property' => [
            '#type' => 'select',
            '#attributes' => ["placeholder" => "Property Ids"],
            '#options' => $sfoptions,
          ],
          'overwrite' => [
            '#type' => 'button',
            "#value" => "Overwite Property",
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
          'result' => [
            '#markup' => "<div id='overwrite-result' class='js-hide overwrite-result'></div>",
          ],
        ],
        'overwrite_all' => [
          '#type' => 'fieldset',
          '#title' => 'Overwrite All Properties',
          '#description' => "Overwrite ALL properties in SF, overwriting data in Drupal.",
          'overwrite' => [
            '#type' => 'button',
            "#value" => "overwrite All",
            '#attributes' => [
              'class' => ['button', 'button--primary', 'form-item'],
              'title' => "This will import all Salesforce Properties, along with their updates, meetings and documents."
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
          'result' => [
            '#markup' => "<div id='overwrite-all-result' class='js-hide overwrite-all-result'></div>",
          ],
        ],
      ],
    ];

    $form = parent::buildForm($form, $form_state);

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('node_buildinghousing.settings');
    $config->set('cron', $form_state->getValue('cron'));
    $config->save();
    parent::submitForm($form, $form_state);
  }

  public function deleteProperty(array &$form, FormStateInterface $form_state)  {
    if ($nid = $form_state->getValue("property")) {
      BuildingHousingUtils::delete_bh_project([$nid], TRUE);
      return ["#markup" => "<div id='remove-result' class='remove-result form-item' style='color: green;'><img src='/core/misc/icons/73b355/check.svg' /> Property removed</div>"];
    }

  }

  public function deleteAllProperties(array &$form, FormStateInterface $form_state)  {
    $existing_project_count = BuildingHousingUtils::delete_all_bh_objects(TRUE);
    return ["#markup" => "<div id='remove-result' class='remove-result form-item' style='color: green;'><img src='/core/misc/icons/73b355/check.svg' /> {$existing_project_count}  Properties removed</div>"];
  }

  public function updateProperty(array &$form, FormStateInterface $form_state)  {
    if ($nid = $form_state->getValue("update_property")) {
      $sf = \Drupal::entityTypeManager()
        ->getStorage("salesforce_mapped_object")
        ->loadByProperties(["drupal_entity" => $nid]);
      $sf = reset($sf);
      $sfid = $sf->get("salesforce_id")->value;
      $new_projects = $this->enqueueSfRecords($sfid);
      if ($new_projects == 1) {
        $count = $this->processSfQueue();
      }
    }
    return ["#markup" => "<div id='update-result' class='update-result form-item' style='color: green;'><img src='/core/misc/icons/73b355/check.svg' /> {$new_projects} Property updated using {$count} SF objects</div>"];
  }

  public function overwriteProperty(array &$form, FormStateInterface $form_state)  {
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
      return ["#markup" => "<div id='overwrite-all-result' class='overwrite-result form-item' style='color: green;'><img src='/core/misc/icons/73b355/check.svg' /> {$existing_project_count} Drupal Property overwritten with {$new_projects} Salesforce Property using {$count} Salesforce objects</div>"];
    }
  }

  public function overwriteAllProperties(array &$form, FormStateInterface $form_state)  {
    $existing_project_count = BuildingHousingUtils::delete_all_bh_objects(TRUE);
    $new_projects = $this->enqueueSfRecords();
    $count = $this->processSfQueue();
    return ["#markup" => "<div id='overwrite-all-result' class='overwrite-all-result form-item' style='color: green;'><img src='/core/misc/icons/73b355/check.svg' /> {$existing_project_count} Drupal Properties overwritten with {$new_projects} Salesforce Properties using {$count} Salesforce objects</div>"];
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

}
