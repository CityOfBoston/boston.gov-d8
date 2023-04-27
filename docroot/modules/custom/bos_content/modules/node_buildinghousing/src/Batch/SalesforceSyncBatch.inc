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
  $delete_parcel = ($config->get('delete_parcel') === 1) ?? FALSE;
  $lock = \Drupal::lock();
  $node_storage = \Drupal::entityTypeManager()->getStorage('node');

  if ($entity == "bh_parcel" && !$delete_parcel) {
    // Won't fprocess parcels if settings form has this disabled.
    $context["finished"] = 1;
    return;
  }

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

  if ($count) {

    if (!isset($context["sandbox"]["{$entity}_total"])) {
      $context["sandbox"]["{$entity}_total"] = $count;
      $context["sandbox"]["{$entity}_deleted"] = 0;
      $context["sandbox"]["results"] = "Removed  {$count} {$info["type"]} Records";
      $log && BuildingHousingUtils::log("cleanup", "Removing {$count} {$info["type"]} records. \n");
    }

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
          BuildingHousingUtils::deleteProject($node, $delete_parcel, TRUE, $log);
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
          BuildingHousingUtils::deleteParcelAssoc($node, TRUE, $log, $delete_parcel);
          break;
        case "bh_parcel":
          // Delete orphaned Parcels.
          BuildingHousingUtils::deleteParcel($node, TRUE, $log);
          break;
      }
      $context["sandbox"]["{$entity}_deleted"]++;
    }
    $node = NULL;

    $context['message'] = "Deleted {$context["sandbox"]["{$entity}_deleted"]} of {$context["sandbox"]["{$entity}_total"]} {$info["type"]} records";
    $context['sandbox']['progress'] = $context["sandbox"]["{$entity}_deleted"] / $context["sandbox"]["{$entity}_total"];
    $context['finished'] = ($context["sandbox"]["{$entity}_deleted"] / $context["sandbox"]["{$entity}_total"]);

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
function bh_queueAllBatch($mapping, &$context) {

  $config = \Drupal::config('node_buildinghousing.settings');
  $log = ($config->get("log_actions") === 1) ?? FALSE;
  $delete_parcel = ($config->get('delete_parcel') === 1) ?? FALSE;
  $lock = \Drupal::lock();
  $node_storage = \Drupal::entityTypeManager()->getStorage('node');
  $map = \Drupal::entityTypeManager()->getStorage("salesforce_mapping");

  if (!$lock->lockMayBeAvailable(SalesforceSyncSettings::lockname)) {
    $context["finished"] = 1;
    \Drupal::messenger()->addError("There is already an Salesforce management process runnning");
    return;
  }

  if ($mapping == "building_housing_parcels" && !$delete_parcel) {
    $context['finished'] = 1;
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

  try {

    $lock->acquire(SalesforceSyncSettings::lockname, 90);

    $count = $processor->getUpdatedRecordsForMapping($map->load($mapping), TRUE, 1420070400, strtotime("now"));
    $count && $log && BuildingHousingUtils::log("cleanup", "QUEUED {$count} record/s from Salesforce using '{$mapping}' mapping.\n");

    $context["sandbox"]["{$mapping}_queued"] = $count;

    $context['message'] = "Queuing Salesforce records";
    $context['sandbox']['progress'] = 1;
    $context['finished'] = 1;
    $context["results"][] = "Queued {$count} Salesforce Items from {$mapping}";
    $lock->release(SalesforceSyncSettings::lockname);

  }
  catch (\Exception $e) {
    // Have an error, so log it and then proceed.
    $context['results'][] = "ERROR Queuing record/s from Salesforce using '{$mapping}' mapping: {$e->getMessage()}";
    $log && BuildingHousingUtils::log("cleanup", "***** ERROR Queuing record/s from Salesforce using '{$mapping}' mapping.\n");
    \Drupal::logger("BuildingHousing")->error("***** ERROR Queuing record/s from Salesforce using '{$mapping}' mapping.");
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
    $status = "warning";
  }

  if (!isset($context["sandbox"]["queue_total"])) {
    $context["sandbox"]["queue_total"] = $queue->numberOfItems();
    $context["sandbox"]["queue_processed"] = 0;
  }

  for ($loop = 1; $loop <= max(10, $queue->numberOfItems()); $loop++ ) {

    if ($item = $queue->claimItem()) {

      $lock->acquire(SalesforceSyncSettings::lockname, 30);

      try {
        $queue_worker->processItem($item->data);
        $queue->deleteItem($item);
        $context["sandbox"]["queue_processed"]++;
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
        $queue->delayItem($item, 900);
        $log && BuildingHousingUtils::log("cleanup", "Queue Item {$item->data->getSObject()->field("Name")} (a {$item->data->getSObject()->type()}) from {$item->data->getMappingId()} could not be processed.\n    Error: {$e->getMessage()}\n    - Retry item in 15mins.", TRUE);
        \Drupal::logger("BuildingHousing")->error("Queue Item {$item->data->getSObject()->field("Name")} (a {$item->data->getSObject()->type()}) from {$item->data->getMappingId()} could not be processed. ERROR: {$e->getMessage()}");
      }
    }
  }

  $lock->release(SalesforceSyncSettings::lockname);

  $context['message'] = "Imported {$context["sandbox"]["queue_processed"]} of {$context["sandbox"]["queue_total"]} queued entities";
  $context['sandbox']['progress'] = $context["sandbox"]["queue_processed"] / $context["sandbox"]["queue_total"];
  $context['finished'] = ($context["sandbox"]["queue_processed"] / $context["sandbox"]["queue_total"]);
  if ($context["finished"] == 1) {
    $context["results"][] = "Imported {$context["sandbox"]["queue_processed"]} Salesforce items";
  }

}

function bh_initializeBatch($op, &$context) {

  $config = \Drupal::config('node_buildinghousing.settings');
  $log = ($config->get("log_actions") === 1) ?? FALSE;
  $lock = \Drupal::lock();
  \Drupal::messenger()->deleteAll();

  if (!$lock->lockMayBeAvailable(SalesforceSyncSettings::lockname)) {
    $context["finished"] = 1;
    \Drupal::messenger()->addError("There is already an Salesforce management process runnning");
    return;
  }

  $context['message'] = ucwords($op);
  $context['finished'] = 1;

  $log && BuildingHousingUtils::log("cleanup", "==={$op} STARTS\n");

}

function bh_finalizeBatch($op, &$context) {
  $config = \Drupal::config('node_buildinghousing.settings');
  $log = ($config->get("log_actions") === 1) ?? FALSE;
  $lock = \Drupal::lock();

  foreach ($context["results"] as $result) {
    \Drupal::messenger()->addStatus($result);
  }

  $context['message'] = "Complete";
  $context['finished'] = 1;

  $lock->release(SalesforceSyncSettings::lockname);

  $log && BuildingHousingUtils::log("cleanup", "==={$op} ENDS\n");
}
