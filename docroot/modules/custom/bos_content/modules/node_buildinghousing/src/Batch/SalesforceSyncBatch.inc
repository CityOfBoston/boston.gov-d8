<?php

use Drupal\Core\Queue\DelayableQueueInterface;
use Drupal\Core\Queue\DelayedRequeueException;
use Drupal\Core\Queue\RequeueException;
use Drupal\Core\Queue\SuspendQueueException;
use Drupal\node_buildinghousing\BuildingHousingUtils;
use Drupal\node_buildinghousing\Form\SalesforceSyncSettings;
use Drupal\salesforce_pull\QueueHandler;

/**
 * Batch process to completely deletes all objects imported during previous
 * Salesforce Sync processes.
 *
 * @param $context
 *
 * @return int|null
 * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
 * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
 */
function bh_removeAllBatch($entity, &$context) {

  $config = \Drupal::config('node_buildinghousing.settings');
  $log = ($config->get("log_actions") === 1) ?? FALSE;
  $lock = \Drupal::lock();
  $node_storage = \Drupal::entityTypeManager()->getStorage('node');

  // Delete the requested entities
  $infos = [
    "bh_project" => [
      "chunk" => 10,
      "type" => "Project"
    ],
    "bh_update" => [
      "chunk" => 10,
      "type" => "orphaned Project Update"
    ],
    "bh_meeting" => [
      "chunk" => 15,
      "type" => "orphaned Community Meeting"
    ],
    "bh_parcel_project_assoc" => [
      "chunk" => 25,
      "type" => "orphaned Parcel-Project Association"
    ],
    "bh_parcel" => [
      "chunk" => 50,
      "type" => "orphaned Parcel"
    ],
  ];
  $info = $infos[$entity];

  $count = \Drupal::entityQuery('node')
    ->accessCheck(FALSE)
    ->condition("type", $entity)
    ->count()
    ->execute();

  // Initialize cleanup message for this node type.
  if (!isset($context["sandbox"]["{$entity}_total"])) {
    $context["sandbox"]["{$entity}_total"] = $count;
    $context["sandbox"]["{$entity}_deleted"] = 0;
    $context['message'] = t("Initializing cleanup for @type", [
      "@type" => $info["type"] . "s",
    ]);
    $context['sandbox']['progress'] = 0;
    $context['finished'] = 0;
    $log && BuildingHousingUtils::log("cleanup", "Removing {$count} {$info["type"]} records. \n");
    return;
  }

  if ($count) {

    $nodes = \Drupal::entityQuery('node')
      ->accessCheck(FALSE)
      ->condition("type", $entity)
      ->range(0, $info["chunk"])
      ->execute();
    $nodes = $node_storage->loadMultiple($nodes);

    foreach ($nodes as $node) {
      $lock->acquire(SalesforceSyncSettings::lockname, 30);
      switch ($entity) {
        case "bh_project":
          BuildingHousingUtils::deleteProject($node, TRUE, $log);
          break;
        case "bh_update":
          // Delete any orphaned Updates and linked items.
          BuildingHousingUtils::deleteUpdate(NULL, $node, TRUE, $log);
          break;
        case "bh_meeting":
          // Delete any Community Meetings.
          BuildingHousingUtils::deleteMeeting($node, TRUE, $log);
          break;
        case "bh_parcel_project_assoc":
          // Delete orphaned Parcel Assocs.
          BuildingHousingUtils::deleteParcelAssoc($node, TRUE, $log);
          break;
        case "bh_parcel":
          // Delete orphaned Parcels.
          BuildingHousingUtils::deleteParcel($node, TRUE, $log);
          break;
      }
      $context["sandbox"]["{$entity}_deleted"]++;
    }
    $node = NULL;

    $context['message'] = t("Deleted @progress of @total @type records", [
      "@progress" => number_format($context["sandbox"]["{$entity}_deleted"], 0),
      "@total" => number_format($context["sandbox"]["{$entity}_total"], 0),
      "@type" => $info["type"],
    ]);
    $context['sandbox']['progress'] = $context["sandbox"]["{$entity}_deleted"] / $context["sandbox"]["{$entity}_total"];
    $context['finished'] = ($context["sandbox"]["{$entity}_deleted"] / $context["sandbox"]["{$entity}_total"]);
  }

  if ($context["sandbox"]["{$entity}_total"] == 0) {
    $context['message'] = t("No @type records to delete", [
      "@type" => $info["type"],
    ]);
    $context['finished'] = 1;
  }

  if ($context['finished'] == 1) {
    \Drupal::messenger()->addMessage("Removed  {$context["sandbox"]["{$entity}_deleted"]} {$info["type"]} Records");
  }

  $lock->release(SalesforceSyncSettings::lockname);

}

/**
 * Batch Process to import all records from Salesforce.
 *
 * @param $context
 *
 * @return void
 */
function bh_queueAllBatch($mapping, $force, &$context) {

  $config = \Drupal::config('node_buildinghousing.settings');
  $log = ($config->get("log_actions") === 1) ?? FALSE;
  $lock = \Drupal::lock();
  $map = \Drupal::entityTypeManager()->getStorage("salesforce_mapping");

  if (!$lock->lockMayBeAvailable(SalesforceSyncSettings::lockname)) {
    $context["finished"] = 1;
    \Drupal::messenger()->addError("There is already an Salesforce management process runnning");
    return;
  }

  $container = \Drupal::getContainer();
  $processor = new QueueHandler(
    $container->get('salesforce.client'),
    $container->get('entity_type.manager'),
    $container->get('queue.database'),
    $container->get('config.factory'),
    $container->get('event_dispatcher'),
    $container->get('datetime.time')
  );

  $text = [
    "building_housing_projects" => "Project",
    "bh_website_update" => "Website Update",
    "bh_community_meeting_event" => "Community Meeting",
    "building_housing_parcels" => "Parcel",
    "bh_parcel_project_assoc" => "Parcel-Project Association",
  ];
  $object = $text[$mapping];

  // Do this in an initial pass to update the progress page.
  if (!isset($context["sandbox"]["{$object}_queued"])) {
    $context["sandbox"]["{$object}_queued"] = 0;
    $context['message'] = "Queueing {$object}s from Salesforce";
    $context['sandbox']['progress'] = 0;
    $context['finished'] = 0;
    return;
  }

  try {

    $lock->acquire(SalesforceSyncSettings::lockname, 90);

    if ($force) {
      $count = $processor->getUpdatedRecordsForMapping($map->load($mapping), TRUE, 1420070400, strtotime("now"));
    }
    else {
      $count = $processor->getUpdatedRecordsForMapping($map->load($mapping), FALSE);
    }
    $context["sandbox"]["{$object}_queued"] = $count;
    $count = number_format($count, 0);

    $context['message'] = "Queued {$count} {$object}s from Salesforce";
    $context['sandbox']['progress'] = 1;
    $context['finished'] = 1;

    $count && $log && BuildingHousingUtils::log("cleanup", "QUEUED {$count} {$object} record/s from Salesforce using '{$mapping}' mapping.\n");
    \Drupal::messenger()->addMessage("Queued {$count} Salesforce {$object}s from {$mapping}");

    $lock->release(SalesforceSyncSettings::lockname);

  }
  catch (\Exception $e) {
    // Have an error, so log it and then proceed.
    \Drupal::messenger()->addWarning("ERROR Queueing {$object} record/s from Salesforce using '{$mapping}' mapping: {$e->getMessage()}");
    $log && BuildingHousingUtils::log("cleanup", "***** ERROR Queueing {$object} record/s from Salesforce using '{$mapping}' mapping.\n");
    \Drupal::logger("BuildingHousing")->error("***** ERROR Queueing {$object} record/s from Salesforce using '{$mapping}' mapping.");
  }

}

function bh_processQueueBatch(&$context) {
  /**
   * @var \Drupal\Core\Queue\QueueInterface $queue
   */

  $config = \Drupal::config('node_buildinghousing.settings');
  $log = ($config->get("log_actions") === 1) ?? FALSE;
  $lock = \Drupal::lock();

  try {
    $lock->acquire(SalesforceSyncSettings::lockname, 15);
    $queue_factory = \Drupal::service('queue');
    $queue_manager = \Drupal::service('plugin.manager.queue_worker');
    $queue_worker = $queue_manager->createInstance('cron_salesforce_pull');
    $queue = $queue_factory->get('cron_salesforce_pull');
  }
  catch (\Exception $e) {
    \Drupal::messenger()->addWarning("ERROR attaching queue: {$e->getMessage()}");
  }

  if (!isset($context["sandbox"]["queue_total"])) {
    $context["sandbox"]["queue_total"] = $queue->numberOfItems();
    $context["sandbox"]["queue_processed"] = 0;
    $context['message'] = t("Preparing Queue");
    $context['sandbox']['progress'] = 0;
    $context['finished'] = 0;
    return;
  }

  for ($loop = 1; $loop <= min(25, $queue->numberOfItems()); $loop++ ) {

    $lock->acquire(SalesforceSyncSettings::lockname, 30);

    if ($item = $queue->claimItem()) {

      $item_type = str_replace("__c", "", $item->data->getSobject()->type());
      if (!isset($context["sandbox"]['q'])) {
        $context["sandbox"]['q'] = [];
      }
      if (!isset($context["sandbox"]['q'][$item_type])) {
        $context["sandbox"]['q'][$item_type] = 0;
      }

      try {
        $queue_worker->processItem($item->data);
        $queue->deleteItem($item);
        $context["sandbox"]["queue_processed"]++;
        $context["sandbox"]['q'][$item_type]++;
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
      catch (\Exception $e) {
        // Some other sort of error - delay this item for 15mins
        \Drupal::messenger()->addError("Queue Item {$item->data->getSObject()->field("Name")} (a {$item->data->getSObject()->type()}) from {$item->data->getMappingId()} could not be processed.\n    Error: {$e->getMessage()}");
        $queue->delayItem($item, 900);
        $log && BuildingHousingUtils::log("cleanup", "Queue Item {$item->data->getSObject()->field("Name")} (a {$item->data->getSObject()->type()}) from {$item->data->getMappingId()} could not be processed.\n    Error: {$e->getMessage()}\n    - Retry item in 15mins.", TRUE);
        \Drupal::logger("BuildingHousing")->error("Queue Item {$item->data->getSObject()->field("Name")} (a {$item->data->getSObject()->type()}) from {$item->data->getMappingId()} could not be processed. ERROR: {$e->getMessage()}");
      }
    }
  }

  $lock->release(SalesforceSyncSettings::lockname);

  $context['message'] = t("Imported @processed of @total queued entities -currently importing @type (@count)", [
    "@processed" => number_format($context["sandbox"]["queue_processed"], 0),
    "@total" => number_format($context["sandbox"]["queue_total"], 0),
    "@count" => number_format($context["sandbox"]['q'][$item_type], 0),
    "@type" => "{$item_type}s",
  ]);
  $context['sandbox']['progress'] = $context["sandbox"]["queue_processed"] / $context["sandbox"]["queue_total"];
  $context['finished'] = ($context["sandbox"]["queue_processed"] / $context["sandbox"]["queue_total"]);

  if ($context["finished"] == 1) {
    \Drupal::messenger()->addMessage("Imported {$context["sandbox"]["queue_processed"]} Salesforce items");
    foreach($context["sandbox"]['q'] as $key => $val) {
      \Drupal::messenger()->addMessage(" => {$context["sandbox"]["queue_processed"]} {$key}s");
    }
  }

}

function bh_initializeBatch($op, &$context) {

  $config = \Drupal::config('node_buildinghousing.settings');
  $log = ($config->get("log_actions") === 1) ?? FALSE;
  $lock = \Drupal::lock();
  \Drupal::messenger()->deleteAll();

  if (!$lock->lockMayBeAvailable(SalesforceSyncSettings::lockname)) {
    $context["finished"] = 1;
    \Drupal::messenger()->addError("There is already an {$op} Salesforce process runnning");
    return;
  }

  $context['message'] = ucwords($op);
  $_SESSION['sandbox']['start'] = strtotime("now");
  $_SESSION['sandbox']['op'] = $op;
  $context['finished'] = 1;

  $log && BuildingHousingUtils::log("cleanup", "==={$op} STARTS\n");

}

function bh_finalizeBatch(&$context) {
  $config = \Drupal::config('node_buildinghousing.settings');
  $log = ($config->get("log_actions") === 1) ?? FALSE;
  $lock = \Drupal::lock();
  $op = $_SESSION['sandbox']['op'] ?? "UNKNOWN";
  \Drupal::messenger()->addStatus("Process took " . number_format((strtotime("now") - $_SESSION['sandbox']['start']) / 60, 0) . " minutes");

  $context['message'] = "Complete";
  $context['finished'] = 1;

  $lock->release(SalesforceSyncSettings::lockname);

  $log && BuildingHousingUtils::log("cleanup", "==={$op} ENDS\n");
}
