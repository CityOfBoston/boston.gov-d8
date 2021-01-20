<?php

namespace Drupal\node_buildinghousing\EventSubscriber;


use Drupal\Component\Utility\UrlHelper;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\salesforce\Event\SalesforceEvents;
use Drupal\salesforce\Exception;
use Drupal\salesforce\SelectQuery;
use Drupal\salesforce_mapping\Event\SalesforcePullEvent;
use Drupal\salesforce_mapping\Event\SalesforcePushOpEvent;
use Drupal\salesforce_mapping\Event\SalesforcePushAllowedEvent;
use Drupal\salesforce_mapping\Event\SalesforcePushParamsEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Drupal\salesforce_mapping\Event\SalesforceQueryEvent;

/**
 * Class SalesforceExampleSubscriber.
 *
 * Trivial example of subscribing to salesforce.push_params event to set a
 * constant value for Contact.FirstName.
 *
 * @package Drupal\salesforce_example
 */
class SalesforceBuildingHousingUpdateSubscriber implements EventSubscriberInterface
{

  use StringTranslationTrait;

//  /**
//   * SalesforcePushAllowedEvent callback.
//   *
//   * @param \Drupal\salesforce_mapping\Event\SalesforcePushAllowedEvent $event
//   *   The push allowed event.
//   */
//  public function pushAllowed(SalesforcePushAllowedEvent $event) {
//    /** @var \Drupal\Core\Entity\Entity $entity */
//    $entity = $event->getEntity();
//    if ($entity && $entity->getEntityTypeId() == 'unpushable_entity') {
//      $event->disallowPush();
//    }
//  }

//  /**
//   * SalesforcePushParamsEvent callback.
//   *
//   * @param \Drupal\salesforce_mapping\Event\SalesforcePushParamsEvent $event
//   *   The event.
//   */
//  public function pushParamsAlter(SalesforcePushParamsEvent $event) {
//    $mapping = $event->getMapping();
//    $mapped_object = $event->getMappedObject();
//    $params = $event->getParams();
//
//    /** @var \Drupal\Core\Entity\Entity $entity */
//    $entity = $event->getEntity();
//    if ($entity->getEntityTypeId() != 'user') {
//      return;
//    }
//    if ($mapping->id() != 'salesforce_example_contact') {
//      return;
//    }
//    if ($mapped_object->isNew()) {
//      return;
//    }
//    $params->setParam('FirstName', 'SalesforceExample');
//  }

//  /**
//   * SalesforcePushParamsEvent push success callback.
//   *
//   * @param \Drupal\salesforce_mapping\Event\SalesforcePushParamsEvent $event
//   *   The event.
//   */
//  public function pushSuccess(SalesforcePushParamsEvent $event) {
//    switch ($event->getMappedObject()->getMapping()->id()) {
//      case 'mapping1':
//        // Do X.
//        break;
//
//      case 'mapping2':
//        // Do Y.
//        break;
//    }
//    \Drupal::messenger()->addStatus('push success example subscriber!: ' . $event->getMappedObject()->sfid());
//  }

//  /**
//   * SalesforcePushParamsEvent push fail callback.
//   *
//   * @param \Drupal\salesforce_mapping\Event\SalesforcePushOpEvent $event
//   *   The event.
//   */
//  public function pushFail(SalesforcePushOpEvent $event) {
//    \Drupal::messenger()->addStatus('push fail example: ' . $event->getMappedObject()->id());
//  }

  /**
   * SalesforceQueryEvent pull query alter event callback.
   *
   * @param \Drupal\salesforce_mapping\Event\SalesforceQueryEvent $event
   *   The event.
   */
  public function pullQueryAlter(SalesforceQueryEvent $event)
  {
    $mapping = $event->getMapping();
    switch ($mapping->id()) {
      case 'building_housing_projects':

        //$query = $event->getQuery();
        //$query->fields[] = "(SELECT Id, Name FROM Project_Manager__r LIMIT 2)";

        break;
      case 'bh_website_update':
        $query = $event->getQuery();
        $query->fields[] = "(SELECT Id, ContentType, Name, Description FROM Attachments LIMIT 20)";
//        $query->fields[] = "(SELECT Id, ContentType, Name FROM Attachments LIMIT 20)";
        $query->limit = 5;

        break;
      case 'building_housing_project_update':
        // Add attachments to the Contact pull mapping so that we can save
        // profile pics. See also ::pullPresave.
        $query = $event->getQuery();
        // Add a subquery:
        $query->fields[] = "(SELECT Id, ContentType, Name, Description FROM Attachments LIMIT 20)";
//        $query->fields[] = "(SELECT Id FROM Attachments WHERE Name = 'example.jpg' LIMIT 1)";
        // Add a field from lookup:
//        $query->fields[] = "Account.Name";
        // Add a condition:
//        $query->addCondition('Email', "''", '!=');
        // Add a limit:
        $query->limit = 5;
        break;
    }
  }

  /**
   * Pull presave event callback.
   *
   * @param \Drupal\salesforce_mapping\Event\SalesforcePullEvent $event
   *   The event.
   */
  public function pullPresave(SalesforcePullEvent $event)
  {
    $mapping = $event->getMapping();
    switch ($mapping->id()) {
      case 'building_housing_projects':

        $project = $event->getEntity();
        $sf_data = $event->getMappedObject()->getSalesforceRecord();
        $client = \Drupal::service('salesforce.client');
        $authProvider = \Drupal::service('plugin.manager.salesforce.auth_providers');

        //$project->set()
        try {
          $projectManagerId = $sf_data->field('Project_Manager__c') ?? null;
          if ($projectManagerId) {
            $projectManager = $client->objectRead('User', $projectManagerId);
          } else {
            $projectManager = null;
          }
        } catch (Exception $exception) {
          $projectManager = null;
        };


        if ($projectManager) {
          $project->set('field_bh_project_manager_name', $projectManager->field('Name'));
          $project->set('field_project_manager_email', $projectManager->field('Email'));
          $project->set('field_bh_project_manger_phone', $projectManager->field('Phone'));
        }

        break;
      case 'building_housing_project_update':
      case 'bh_website_update':
        // In this example, given a Contact record, do a just-in-time fetch for
        // Attachment data, if given.
        $update = $event->getEntity();
        $sf_data = $event->getMappedObject()->getSalesforceRecord();
        $client = \Drupal::service('salesforce.client');
        $authProvider = \Drupal::service('plugin.manager.salesforce.auth_providers');

        // Fetch the attachment URL from raw sf data.
        $attachments = [];
        try {
          $attachments = $sf_data->field('Attachments');
        } catch (\Exception $e) {
          // noop, fall through.
        }
        if (@$attachments['totalSize'] < 1) {
          // If Attachments field was empty, do nothing.
          return;
        }

        foreach ($attachments['records'] as $key => $attachment) {

          // If Attachments field was set, it will contain a URL from which we can
          // fetch the attached binary. We must append "body" to the retreived URL
          // https://developer.salesforce.com/docs/atlas.en-us.api_rest.meta/api_rest/dome_sobject_blob_retrieve.htm
          $attachment_url = $attachment['attributes']['url'];
          $attachment_url = $authProvider->getProvider()->getInstanceUrl() . $attachment_url . '/Body';

          // Fetch the attachment body, via RestClient::httpRequestRaw.
          try {
            $file_data = $client->httpRequestRaw($attachment_url);
          } catch (\Exception $e) {
            // Unable to fetch file data from SF.
            \Drupal::logger('db')->error($this->t('Failed to fetch attachment for Update @update', ['@update' => $update->id()]));
            return;
          }

          // Fetch file destination from account settings.

          if ($projectName = $update->get('field_bh_project_ref')->referencedEntities()[0]->getTitle()) {

            $fileTypeToDirMappings = [
              'image/jpeg' => 'image',
              'image/png' => 'image',
              'application/pdf' => 'document',
            ];

            $fileType = $fileTypeToDirMappings[$attachment['ContentType']] ?? 'other';



            $storageDirPath = "public://buildinghousing/project/" . $projectName . "/attachment/" . $fileType . "/" . date('Y-m', time()) . "/";
            $fileName = $attachment['Name'];

            if (file_prepare_directory($storageDirPath, FILE_CREATE_DIRECTORY)) {
              $destination = $storageDirPath . $fileName;
            } else {
              continue;
            }
          }


          // Attach the new file id to the user entity.
          /* var \Drupal\file\FileInterface */
          if ($file = file_save_data($file_data, $destination, FileSystemInterface::EXISTS_REPLACE)) {
            //$update->field_bh_attachment->target_id = $file->id();
            if ($key == 0) {
              $update->set('field_bh_attachment', ['target_id' => $file->id()]);
            } else {
              $update->get('field_bh_attachment')->appendItem(['target_id' => $file->id()]);
            }

          } else {
            \Drupal::logger('db')->error('failed to save Attachment file for BH Update ' . $update->id());
            continue;
          }

        }
        //$update->save();

        break;
    }
  }

  /**
   * PULL_PREPULL event subscriber example.
   */
  public function pullPrepull(SalesforcePullEvent $event)
  {
    // For the "contact" mapping, if the SF record is marked "Inactive", do not
    // pull the record and block the user account.
    $mapping = $event->getMapping();
//    switch ($mapping->id()) {
//      case 'contact':
//        $sf_data = $event->getMappedObject()->getSalesforceRecord();
//        /** @var \Drupal\user\Entity\User $account */
//        $account = $event->getEntity();
//        try {
//          if (!$sf_data->field('Inactive__c')) {
//            // If the SF record is not marked "Inactive", proceed as normal.
//            return;
//          }
//        }
//        catch (\Exception $e) {
//          // Fall through if "Inactive" field was not found.
//        }
//        // If we got here, SF record is marked inactive. Don't pull it.
//        $event->disallowPull();
//        if (!$account->isNew()) {
//          // If this is an update to an existing account, block the account.
//          // If this is a new account, it won't be created.
//          $account->block()->save();
//        }
//    }
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents()
  {
    $events = [
//      SalesforceEvents::PUSH_ALLOWED => 'pushAllowed',
//      SalesforceEvents::PUSH_PARAMS => 'pushParamsAlter',
//      SalesforceEvents::PUSH_SUCCESS => 'pushSuccess',
//      SalesforceEvents::PUSH_FAIL => 'pushFail',
      SalesforceEvents::PULL_PRESAVE => 'pullPresave',
      SalesforceEvents::PULL_QUERY => 'pullQueryAlter',
      SalesforceEvents::PULL_PREPULL => 'pullPrepull',
    ];
    return $events;
  }

}
