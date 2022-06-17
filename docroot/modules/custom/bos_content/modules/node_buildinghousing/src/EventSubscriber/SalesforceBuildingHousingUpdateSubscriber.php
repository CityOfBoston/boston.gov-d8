<?php

namespace Drupal\node_buildinghousing\EventSubscriber;

use Drupal\Core\File\Exception\DirectoryNotReadyException;
use Drupal\Core\File\Exception\FileException;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\salesforce\Event\SalesforceEvents;
use Drupal\salesforce\Exception;
use Drupal\salesforce_mapping\Event\SalesforcePullEvent;
use Drupal\salesforce_mapping\Event\SalesforceQueryEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Class SalesforceExampleSubscriber.
 *
 * Trivial example of subscribing to salesforce.push_params event to set a
 * constant value for Contact.FirstName.
 *
 * @package Drupal\salesforce_example
 */
class SalesforceBuildingHousingUpdateSubscriber implements EventSubscriberInterface {

  use StringTranslationTrait;

  // /**
  // * SalesforcePushAllowedEvent callback.
  // *
  // * @param \Drupal\salesforce_mapping\Event\SalesforcePushAllowedEvent $event
  // *  The push allowed event.
  // */
  // public function pushAllowed(SalesforcePushAllowedEvent $event) {
  // /** @var \Drupal\Core\Entity\Entity $entity */
  // $entity = $event->getEntity();
  // if ($entity && $entity->getEntityTypeId() == 'unpushable_entity') {
  // $event->disallowPush();
  // }
  // }

  // /**
  // * SalesforcePushParamsEvent callback.
  // *
  // * @param \Drupal\salesforce_mapping\Event\SalesforcePushParamsEvent $event
  // *   The event.
  // */
  // public function pushParamsAlter(SalesforcePushParamsEvent $event) {
  // $mapping = $event->getMapping();
  // $mapped_object = $event->getMappedObject();
  // $params = $event->getParams();
  //
  // /** @var \Drupal\Core\Entity\Entity $entity */
  // $entity = $event->getEntity();
  // if ($entity->getEntityTypeId() != 'user') {
  // return;
  // }
  // if ($mapping->id() != 'salesforce_example_contact') {
  // return;
  // }
  // if ($mapped_object->isNew()) {
  // return;
  // }
  // $params->setParam('FirstName', 'SalesforceExample');
  // }

  // /**
  // * SalesforcePushParamsEvent push success callback.
  // *
  // * @param \Drupal\salesforce_mapping\Event\SalesforcePushParamsEvent $event
  // *   The event.
  // */
  // public function pushSuccess(SalesforcePushParamsEvent $event) {
  // switch ($event->getMappedObject()->getMapping()->id()) {
  // case 'mapping1':
  // // Do X.
  // break;
  //
  //  case 'mapping2':
  //  // Do Y.
  //  break;
  // }
  // \Drupal::messenger()->addStatus('push success example subscriber!: ' . $event->getMappedObject()->sfid());
  // }

  // /**
  // * SalesforcePushParamsEvent push fail callback.
  // *
  // * @param \Drupal\salesforce_mapping\Event\SalesforcePushOpEvent $event
  // *   The event.
  // */
  // public function pushFail(SalesforcePushOpEvent $event) {
  // \Drupal::messenger()->addStatus('push fail example: ' . $event->getMappedObject()->id());
  // }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events = [
      // SalesforceEvents::PUSH_ALLOWED => 'pushAllowed',
      // SalesforceEvents::PUSH_PARAMS => 'pushParamsAlter',
      // SalesforceEvents::PUSH_SUCCESS => 'pushSuccess',
      // SalesforceEvents::PUSH_FAIL => 'pushFail',.
      SalesforceEvents::PULL_PRESAVE => 'pullPresave',
      SalesforceEvents::PULL_QUERY => 'pullQueryAlter',
      SalesforceEvents::PULL_PREPULL => 'pullPrepull',
    ];
    return $events;
  }

  /**
   * SalesforceQueryEvent pull query alter event callback.
   *
   * @param \Drupal\salesforce_mapping\Event\SalesforceQueryEvent $event
   *   The event.
   */
  public function pullQueryAlter(SalesforceQueryEvent $event) {
    $mapping = $event->getMapping();
    switch ($mapping->id()) {
      case 'building_housing_projects':

        $query = $event->getQuery();
        // $query->fields[] = "(SELECT Id, Name FROM Project_Manager__c LIMIT 1)";
        // $query->fields[] = "Project_Manager__c";
        $query->fields['Project_Manager__c'] = 'Project_Manager__c';

        break;

      case 'bh_website_update':
        $query = $event->getQuery();
        $query->fields[] = "(SELECT Id, ContentType, Name, Description FROM Attachments LIMIT 20)";
        $query->fields[] = "(SELECT ContentDocumentId, ContentDocument.ContentModifiedDate, ContentDocument.FileExtension, ContentDocument.Title, ContentDocument.FileType, ContentDocument.LatestPublishedVersionId FROM ContentDocumentLinks LIMIT 20)";
        // $query->fields[] = "(SELECT Id, ContentType, Name FROM Attachments LIMIT 20)";
        // $query->limit = 5;

        break;

      case 'building_housing_project_update':
        // Add attachments to the Contact pull mapping so that we can save
        // profile pics. See also ::pullPresave.
        $query = $event->getQuery();
        // Add a subquery:
        $query->fields[] = "(SELECT Id, ContentType, Name, Description FROM Attachments LIMIT 20)";
        // $query->fields[] = "(SELECT Id FROM Attachments WHERE Name = 'example.jpg' LIMIT 1)";
        // Add a field from lookup:
        // $query->fields[] = "Account.Name";
        // Add a condition:
        // $query->addCondition('Email', "''", '!=');
        // Add a limit:
        // $query->limit = 50;
        break;
    }
  }

  /**
   * Pull presave event callback.
   *
   * @param \Drupal\salesforce_mapping\Event\SalesforcePullEvent $event
   *   The event.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function pullPresave(SalesforcePullEvent $event) {
    $mapping = $event->getMapping();
    switch ($mapping->id()) {
      case 'bh_community_meeting_event':

        try {
          $project = $event->getEntity();
          $sf_data = $event->getMappedObject()->getSalesforceRecord();

          if ($supportedLanguages = $sf_data->field('Languages_supported__c')) {
            $supportedLanguages = explode(';', $supportedLanguages);
            $supportedLanguages = implode(', ', $supportedLanguages);
            $project->set('field_bh_languages_supported', $supportedLanguages);
          }
          // Validate all URL-based fields in the object
          foreach ([
            'Virtual_meeting_web_address__c' => 'field_bh_virt_meeting_web_addr',
            'Post_meeting_recording__c' => 'field_bh_post_meeting_recording'] as $sf_field => $drupal_field) {
            if ($url = $sf_data->field($sf_field)) {
              $url = $this->_validateUrl($url, $sf_field, $sf_data);
              $project->set($drupal_field, $url);
            }
          }
        }
        catch (Exception $exception) {
          // nothing to do
        }

        break;
      case 'building_housing_projects':

        $project = $event->getEntity();
        $sf_data = $event->getMappedObject()->getSalesforceRecord();
        $client = \Drupal::service('salesforce.client');

        // $project->set()
        try {
          $projectManagerId = $sf_data->field('Project_Manager__c')
            ?? $client->objectRead('Project__c', $sf_data->id())->field('Project_Manager__c')
            ?? NULL;

          if ($projectManagerId) {
            $projectManager = $client->objectRead('User', $projectManagerId);
          }
          else {
            $projectManager = NULL;
          }
        }
        catch (Exception $exception) {
          $projectManager = NULL;
        }

        if ($projectManager) {
          $project->set('field_bh_project_manager_name', $projectManager->field('Name'));
          $project->set('field_project_manager_email', $projectManager->field('Email'));
          $project->set('field_bh_project_manger_phone', $projectManager->field('Phone'));
          // $project->save();
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

        // Validate all URL-based fields in the object
        foreach (['Boston_gov_Link__c' => 'field_bh_project_web_link'] as $sf_field => $drupal_field) {
          if ($url = $sf_data->field($sf_field)) {
            $url = $this->_validateUrl($url, $sf_field, $sf_data);
            $update->set($drupal_field, $url);
          }
        }

        if ($mapping->id() == 'bh_website_update') {

          // Check for chatter text updates.
          $chatterFeedURL = $authProvider->getProvider()->getApiEndpoint() . "chatter/feeds/record/" . $sf_data->id() . "/feed-elements";
          $chatterData = NULL;
          try {
            $chatterData = $client->httpRequestRaw($chatterFeedURL);
            $chatterData = $chatterData ? json_decode($chatterData) : NULL;

            if ($chatterData) {
              $currentTextUpdates = $update->field_bh_text_updates;
              $currentTextUpdateIds = [];

              foreach ($currentTextUpdates as $key => $currentTextUpdate) {
                $textData = $currentTextUpdate->getValue();
                $textData = json_decode($textData['value']);
                $currentTextUpdateIds[] = $textData->id;
              }

              foreach ($chatterData->elements as $post) {

                if ($post->type == 'TextPost' && !in_array($post->id, $currentTextUpdateIds)) {

                  // CREATE AND SET THE UPDATE TEXT FIELD.

                  $drupalPost = [
                    'text' => $post->body->text ?? '',
                    'author' => $post->actor->displayName ?? '',
                    'date' => $post->createdDate ?? now(),
                    'id' => $post->id ?? '',
                  ];

                  if ($drupalPost) {
                    $update->field_bh_text_updates->appendItem(json_encode($drupalPost));
                  }
                }
              }
            }

          }
          catch (\Exception $e) {
            // Unable to fetch file data from SF.
            \Drupal::logger('db')->error($this->t('Failed to get Text updates for Update @update', ['@update' => $update->id()]));
            \Drupal::logger('db')->error($this->t('Text updates Backtrace @backtrace', ['@backtrace' => $e->getTraceAsString()]));
            \Drupal::logger('db')->error($this->t('Chatter Feed URL @url', ['@url' => $chatterFeedURL]));
            // return;.
          }

          // Fetch the files URL from raw sf data.
          $attachments = [];
          try {
            $attachments = $sf_data->field('ContentDocumentLinks');
          }
          catch (\Exception $e) {
            // noop, fall through.
          }
          if (@$attachments['totalSize'] < 1) {
            // If Attachments field was empty, do nothing.
            return;
          }
        }
        else {

          // Fetch the attachment URL from raw sf data.
          $attachments = [];
          try {
            $attachments = $sf_data->field('Attachments');
          }
          catch (\Exception $e) {
            // noop, fall through.
          }
          if (@$attachments['totalSize'] < 1) {
            // If Attachments field was empty, do nothing.
            return;
          }
        }

        foreach ($attachments['records'] as $key => $attachment) {

          // If Attachments field was set, it will contain a URL from which we can
          // fetch the attached binary. We must append "body" to the retreived URL
          // https://developer.salesforce.com/docs/atlas.en-us.api_rest.meta/api_rest/dome_sobject_blob_retrieve.htm
          if ($mapping->id() == 'bh_website_update') {
            // /services/data/v47.0/sobjects/ContentVersion/[Id]/VersionData

            $attachmentVersionId = $attachment['ContentDocument']['LatestPublishedVersionId'] ?? '';
            $attachment_url = "sobjects/ContentVersion/" . $attachmentVersionId;
            $attachment_url = $authProvider->getProvider()->getApiEndpoint() . $attachment_url . '/VersionData';
          }
          else {
            $attachment_url = $attachment['attributes']['url'];
            $attachment_url = $authProvider->getProvider()->getInstanceUrl() . $attachment_url . '/Body';
          }

          // Fetch the attachment body, via RestClient::httpRequestRaw.
          try {
            $file_data = $client->httpRequestRaw($attachment_url);
          }
          catch (\Exception $e) {
            // Unable to fetch file data from SF.
            \Drupal::logger('db')->error($this->t('Failed to fetch attachment for Update @update', ['@update' => $update->id()]));
            return;
          }

          // Fetch file destination from account settings.

          if ($project = $update->get('field_bh_project_ref')->referencedEntities()[0]) {

            $projectName = basename($project->toUrl()->toString()) ?? 'unknown';
            $fileTypeToDirMappings = [
              'image/jpeg' => 'image',
              'JPEG' => 'image',
              'image/jpg' => 'image',
              'JPG' => 'image',
              'image/png' => 'image',
              'PNG' => 'image',
              'application/pdf' => 'document',
              'PDF' => 'document',
            ];

            if ($mapping->id() == 'bh_website_update') {
              $fileType = $fileTypeToDirMappings[$attachment['ContentDocument']['FileType']] ?? 'other';
            }
            else {
              $fileType = $fileTypeToDirMappings[$attachment['ContentType']] ?? 'other';
            }

            $storageDirPath = "public://buildinghousing/project/" . $projectName . "/attachment/" . $fileType . "/" . date('Y-m', time()) . "/";

            if ($mapping->id() == 'bh_website_update') {
              $fileName = $attachment['ContentDocument']['Title'] . '.' . $attachment['ContentDocument']['FileExtension'];
            }
            else {
              $fileName = $attachment['Name'];
            }

            if (\Drupal::service('file_system')->prepareDirectory($storageDirPath, FileSystemInterface::CREATE_DIRECTORY)) {
              $destination = $storageDirPath . $this->_sanitizeFilename($fileName);
            }
            else {
              continue;
            }

            if ($fileType == 'image') {
              // $fieldName = 'field_bh_image';
              $fieldName = 'field_bh_project_images';
            }
            else {
              $fieldName = 'field_bh_attachment';
            }

            // Attach the new file id to the user entity.
            /* var \Drupal\file\FileInterface */
            if (!file_exists($destination)) {
              try {
                $file = \Drupal::service('file.repository')
                  ->writeData($file_data, $destination, FileSystemInterface::EXISTS_REPLACE);
              }
              catch (DirectoryNotReadyException | FileException $e) {
                \Drupal::logger('db')
                  ->error('failed to save Attachment file for BH Update ' . $update->id());
                continue;
              }
              if ($file) {
              // $update->field_bh_attachment->target_id = $file->id();
              if ($update->get($fieldName)->isEmpty()) {
                $update->set($fieldName, ['target_id' => $file->id()]);
                // $update->save();
              }
              else {
                $update->get($fieldName)
                  ->appendItem(['target_id' => $file->id()]);
              }

              if ($project->get($fieldName)->isEmpty()) {
                $project->set($fieldName, ['target_id' => $file->id()]);
                $project->save();
              }
              else {
                $project->get($fieldName)
                  ->appendItem(['target_id' => $file->id()]);
                $project->save();
              }
              // $update->save();

            }}

            else {
              try {
                $file = \Drupal::entityTypeManager()
                  ->getStorage('file')
                  ->loadByProperties(['uri' => $destination]);
              }
              catch (Exception $e) {
                \Drupal::logger('db')
                  ->error('failed to save Attachment file for BH Update ' . $update->id());
                continue;
              }

              if ($file) {
                $file = reset($file);

                // @TODO: TEMP code to add in the created date for the files - RM and move above.
                if ($mapping->id() == 'bh_website_update') {
                $updatedDateTime = strtotime($attachment['ContentDocument']['ContentModifiedDate']) ?? $file->get('created')->value ?? time();
              }
                else {
                $updatedDateTime = strtotime($sf_data->field('CreatedDate')) ?? $file->get('created')->value ?? time();
              }
                $file->set('created', $updatedDateTime);
                $file->save();
                // END TEMP CODE.

                if ($project->get($fieldName)->isEmpty()) {
                $project->set($fieldName, ['target_id' => $file->id()]);
                $project->save();
              }
                else {
                if ($currentFiles = $project->get($fieldName)->getValue()) {
                  $fileIsAttached = FALSE;
                  foreach ($currentFiles as $currentFileKey => $currentFile) {
                    if ($currentFile['target_id'] == $file->id()) {
                      $fileIsAttached = TRUE;
                    }
                  }

                  if (!$fileIsAttached) {
                    $project->get($fieldName)
                      ->appendItem(['target_id' => $file->id()]);
                    $project->save();
                  }
                }

              }

              }
              else {
                \Drupal::logger('db')
                  ->error('failed to save Attachment file for BH Update ' . $update->id());
                continue;
              }
            }
          }

        }

        break;
    }
  }

  /**
   * PULL_PREPULL event subscriber.
   *
   * @param \Drupal\salesforce_mapping\Event\SalesforcePullEvent $event
   *   SF Pull Event.
   */
  public function pullPrepull(SalesforcePullEvent $event) {
    // For the "contact" mapping, if the SF record is marked "Inactive", do not
    // pull the record and block the user account.
    $mapping = $event->getMapping();
    // Switch ($mapping->id()) {
    // case 'contact':
    // $sf_data = $event->getMappedObject()->getSalesforceRecord();
    // /** @var \Drupal\user\Entity\User $account */
    // $account = $event->getEntity();
    // try {
    // if (!$sf_data->field('Inactive__c')) {
    // // If the SF record is not marked "Inactive", proceed as normal.
    // return;
    // }
    // }
    // catch (\Exception $e) {
    // // Fall through if "Inactive" field was not found.
    // }
    // // If we got here, SF record is marked inactive. Don't pull it.
    // $event->disallowPull();
    // if (!$account->isNew()) {
    // // If this is an update to an existing account, block the account.
    // // If this is a new account, it won't be created.
    // $account->block()->save();
    // }
    // }
  }

  /**
   * Tries to return a valid URL when only the domain is provided.
   *
   * @param string $url The URL to be validated.
   * @param string $field The SF sync field providing the URL (for reporting).
   * @param \Drupal\salesforce\SObject $sf_data The SF Object (for reporting).
   *
   * @return string|void A valid URL, or else an empty string.
   */
  private function _validateUrl(string $url, string $field, \Drupal\salesforce\SObject $sf_data) {
    if (\Drupal::pathValidator()->isValid($url)) {
      return $url;
    }
    else {
      // Try to build the url out.
      if (!\Drupal::pathValidator()->isValid("http://" . $url)) {
        // just missing the protocol
        return "http://" . $url;
      }
      else {
        // Don't know what else to do, return empty string
        try {
          // Send an email alert.
          $mailManager = \Drupal::service('plugin.manager.mail');
          $params = [
            'url' => $url,
            'sf_field' => $field,
            'sf_id' => $sf_data->id(),
            'sf_title' => $sf_data->field("Title__c"),
          ];
          $mailManager->mail("node_buildinghousing", 'sync_alert_badurl', "david.upton@boston.gov", "en", $params, NULL, TRUE);
        }
        catch(Exception $e) {
          // Nothing to do
        }
        finally {
          // Ensure the empty string is returned.
          return "";
        }
      }
    }
  }

  /**
   * Removes characters from string that would be problematic if that string
   * was used as a filename.
   * @see https://stackoverflow.com/questions/2021624/string-sanitizer-for-filename
   *
   * @param string $text The filename to be sanitized
   *
   * @return false|string|null The santized filename
   */
  private function _sanitizeFilename(string $text) {
    // Remove anything which isn't a word, whitespace, number
    // or any of the following caracters -_~,;[]().
    $text = preg_replace("([^\w\s\d\-_~,;\[\]\(\).])", '', $text);
    // Remove any runs of periods
    return preg_replace("([\.]{2,})", '', $text);
  }

}
