<?php

namespace Drupal\node_buildinghousing\EventSubscriber;

use Drupal\Core\File\Exception\DirectoryNotReadyException;
use Drupal\Core\File\Exception\FileException;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\node_buildinghousing\BuildingHousingUtils;
use Drupal\salesforce\Event\SalesforceEvents;
use Drupal\salesforce\Exception;
use Drupal\salesforce_mapping\Event\SalesforcePullEvent;
use Drupal\salesforce_mapping\Event\SalesforceQueryEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use \DateTime;
use \DateTimeZone;

/**
 * Class SalesforceBuildingHousingUpdateSubscriber.
 *
 * @package Drupal\node_buildinghousing
 */
class SalesforceBuildingHousingUpdateSubscriber implements EventSubscriberInterface {

  use StringTranslationTrait;
  private $now;

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
      // SalesforceEvents::PULL_PREPULL => 'pullPrepull',
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
        $query->fields['Project_Manager__c'] = 'Project_Manager__c';

        break;

      case 'bh_website_update':
        $query = $event->getQuery();
        $query->fields[] = "(SELECT Id, ContentType, Name, Description FROM Attachments LIMIT 20)";
        $query->fields[] = "(SELECT ContentDocumentId, ContentDocument.CreatedDate, ContentDocument.ContentModifiedDate, ContentDocument.FileExtension, ContentDocument.Title, ContentDocument.FileType, ContentDocument.LatestPublishedVersionId FROM ContentDocumentLinks LIMIT 20)";

        break;

      case 'building_housing_project_update':
        // Add attachments to the Contact pull mapping so that we can save
        // profile pics. See also ::pullPresave.
        $query = $event->getQuery();
        // Add a subquery:
        $query->fields[] = "(SELECT Id, ContentType, Name, Description FROM Attachments LIMIT 20)";

        break;
    }
    BuildingHousingUtils::removeDateFilter($query);
  }

  /**
   * Pull presave event callback.
   *
   * @param \Drupal\salesforce_mapping\Event\SalesforcePullEvent $trigger_event
   *   The event.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function pullPresave(SalesforcePullEvent $trigger_event) {

    $mapping = $trigger_event->getMapping();

    switch ($mapping->id()) {

      case 'bh_community_meeting_event':
        try {
          $bh_meeting = $trigger_event->getEntity();
          $sf_data = $trigger_event->getMappedObject()->getSalesforceRecord();

          if ($supportedLanguages = $sf_data->field('Languages_supported__c')) {
            $supportedLanguages = explode(';', $supportedLanguages);
            $supportedLanguages = implode(', ', $supportedLanguages);
            $bh_meeting->set('field_bh_languages_supported', $supportedLanguages);
          }

          // Validate all URL-based fields in the object
          foreach ([
                     'Virtual_meeting_web_address__c' => 'field_bh_virt_meeting_web_addr',
                     'Post_meeting_recording__c' => 'field_bh_post_meeting_recording'
                   ] as $sf_field => $drupal_field) {
            if ($url = $sf_data->field($sf_field)) {
              $url = $this->_validateUrl($url, $sf_field, $sf_data);
              $bh_meeting->set($drupal_field, $url);
            }
          }

          // Try to break apart the address into parts for Drupal.
          if ($address = $sf_data->field('Address__c')) {
            $bh_meeting->set("field_address", BuildingHousingUtils::setDrupalAddress($address));
          }
        }
        catch (Exception $exception) {
          // nothing to do
        }

        // Todo: check if this is redundant.
        //   if it is, then remove it here and in each case statement below.
        // $bh_meeting->save();

        break;

      case 'building_housing_projects':

        $bh_project = $trigger_event->getEntity();
        $sf_data = $trigger_event->getMappedObject()->getSalesforceRecord();
        $client = \Drupal::service('salesforce.client');

        // $bh_project->set()
        try {
          $projectManagerId = $sf_data->field('Project_Manager__c')
            ?? $client->objectRead('Project__c', $sf_data->id())
            ->field('Project_Manager__c')
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
          $bh_project->set('field_bh_project_manager_name', $projectManager->field('Name'));
          $bh_project->set('field_project_manager_email', $projectManager->field('Email'));
          $bh_project->set('field_bh_project_manger_phone', $projectManager->field('Phone'));
        }

        $bh_project->save();
        break;

      /*
       * building_housing_project_update is a mapping between the bh_update
       * object in Drupal and the Update__c object in SF.
       * The Update__c object is embedded in the Project__c object (on Related
       * tab) and is the *original* way that users added messages and files to
       * a bh_update object (for inclusion in the bh_project timeline).
       * In SF, files are attached to the Update__c record - these are
       * imported and attached to the Drupal bh_update and bh_project records.
       * Messages of Type "Text Update" made against the Website_Update__c
       * record are imported and attached to the Drupal bh_update record for
       * inclusion as messages on the timeline.
       *
       * bh_website_update is a mapping between the bh_update object in Drupal
       * and the Website_Update__c object in SF.
       * The Website_Update__c object is embedded in the Project__c object (on
       * the Related tab).
       * There should only be one Website_Update_c record per Project__c record.
       * In SF, files are attached to the Website_Update__c record - these are
       * imported and attached to the Drupal bh_update and bh_project records.
       * Chatter messages made against the Website_Update__c record are imported
       * and attached to the Drupal bh_update record for inclusion as messages
       * on the timeline.
       */
      case 'building_housing_project_update':
      case 'bh_website_update':
        $bh_update = $trigger_event->getEntity();
        $sf_data = $trigger_event->getMappedObject()->getSalesforceRecord();
        $this->now = (new DateTime(NULL, new DateTimeZone("Z")))
          ->format("Y-m-d\TH:i:s.vT");

        if ($trigger_event->getOp() == "pull_delete") {
          return;
        }

        // This is a new or updated bh_record.
        // Validate all URL-based fields in the object
        foreach (['Boston_gov_Link__c' => 'field_bh_project_web_link'] as $sf_field => $drupal_field) {
          if ($url = $sf_data->field($sf_field)) {
            $url = $this->_validateUrl($url, $sf_field, $sf_data);
            $bh_update->set($drupal_field, $url);
          }
        }

        // Read and process messages to go onto timeline.
        if ($mapping->id() == 'bh_website_update') {
          // We only check Chatter messages for website updates.
          try {
            $chatterData = $this->getChatterMessages($sf_data, $bh_update);
          }
          catch (\Exception $e) {
            $text = "Failed to request and parse Chatter feed. \n {$e->getMessage()}";
            \Drupal::logger('BuildingHousing')->error($text);
            $chatterData = NULL;
          }

          if ($chatterData) {
            try {
              $this->processTextUpdates($chatterData, $bh_update, "c");
            }
            catch (\Exception $e) {
              // Unable to fetch file data from SF.
              $text = "Could not process Chatter messages.\nError reported: {$e->getMessage()}";
              \Drupal::logger('BuildingHousing')->error($text);
              // not fatal.
            }
          }

        }
        else {
          // This update object may be a text message or an attachment.
          if ($legacy_data = $this->getLegacyMessage($sf_data)) {
            try {
              $this->processTextUpdates($legacy_data, $bh_update, "l");
            }
            catch (\Exception $e) {
              // Unable to save message.
              $text = "Could not process Legacy Update \"Text Update\".\nError reported: {$e->getMessage()}";
              \Drupal::logger('BuildingHousing')->error($text);
              // not fatal.
            }
          }
        }

        // Read and process the Attachments
        if ($attachments = $this->getAttachments($mapping->id(), $sf_data)) {
          $this->processAttachments($mapping->id(), $sf_data, $bh_update, $attachments);
        }

      break;
    }
  }

  private function getAttachments($type, $sf_data) {

    if ($type == 'bh_website_update') {

      try {
        $attachments = $sf_data->field('ContentDocumentLinks');
      }
      catch (\Exception $e) {
        // noop, fall through.
      }
      if ($attachments && @$attachments['totalSize'] < 1) {
        return [];
      }
    }
    else {

      try {
        $attachments = $sf_data->field('Attachments');
      }
      catch (\Exception $e) {
        // noop, fall through.
      }
      if (@$attachments['totalSize'] < 1) {
        return [];
      }
    }

    return $attachments;

  }

  private function processAttachments($type, $sf_data, $bh_update, $attachments) {

    if (empty($attachments) || empty($bh_update)) {
      return;
    }
    if (!$bh_project = $bh_update->get('field_bh_project_ref')->referencedEntities()[0]) {
      return;
    }

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
    $authProvider = \Drupal::service('plugin.manager.salesforce.auth_providers');

    $save = FALSE;

    foreach ($attachments['records'] as $key => $attachment) {

        $projectName = basename($bh_project->toUrl()->toString()) ?? 'unknown';

        if ($type == 'bh_website_update') {
          $fileType = $fileTypeToDirMappings[$attachment['ContentDocument']['FileType']] ?? 'other';
          $createdDateTime = strtotime($attachment['ContentDocument']['CreatedDate']) ?? time();
          $updatedDateTime = strtotime($attachment['ContentDocument']['ContentModifiedDate']) ?? time();
          $attachmentVersionId = $attachment['ContentDocument']['LatestPublishedVersionId'] ?? '';
          $sf_download_url = "sobjects/ContentVersion/" . $attachmentVersionId;
          $sf_download_url = $authProvider->getProvider()->getApiEndpoint() . $sf_download_url . '/VersionData';
        }
        else {
          $fileType = $fileTypeToDirMappings[$attachment['ContentType']] ?? 'other';
          $createdDateTime = strtotime($sf_data->field('CreatedDate')) ?? time();
          $updatedDateTime = strtotime($sf_data->field('LastModifiedDate')) ?? time();
          // The Attachments field will contain a URL from which we can
          // download the attached file. We assume the file is a binary.
          // @see https://developer.salesforce.com/docs/atlas.en-us.api_rest.meta/api_rest/dome_sobject_blob_retrieve.htm
          $sf_download_url = $attachment['attributes']['url'];
          $sf_download_url = $authProvider->getProvider()->getInstanceUrl() . $sf_download_url . '/Body';
        }

        $storageDirPath = "public://buildinghousing/project/" . $projectName . "/attachment/" . $fileType . "/" . date('Y-m', $createdDateTime) . "/";

        if ($type == 'bh_website_update') {
          $fileName = $attachment['ContentDocument']['Title'] . '.' . $attachment['ContentDocument']['FileExtension'];
        }
        else {
          $fileName = $attachment['Name'];
        }

        if (!\Drupal::service('file_system')->prepareDirectory($storageDirPath, FileSystemInterface::CREATE_DIRECTORY)) {
          // Issue with finding or creating the folder, try to continue to
          // next record.
          continue;
        }
        $destination = $storageDirPath . $this->_sanitizeFilename($fileName);

        if (!file_exists($destination)) {
          // New file, so save it.
          if (!$file_data = $this->downloadAttachment($sf_download_url)) {
            continue;
          }
          if (!$file = $this->saveAttachment($file_data, $destination, $createdDateTime)) {
            continue;
          }
        }
        else {
          // File already exists, so load it.
          if (!$file = $this->loadAttachment($destination, $createdDateTime)) {
            continue;
          }
        }
        // Now make a link between the file objecy amd the bh_ objects.
        if ($this->linkAttachment($fileType, $file, [$bh_project, $bh_update]) && !$save) {
          $save = TRUE;
        }

    }

    if ($save) {
      // $bh_update gets automatically saved (later on) by the calling process.
      $bh_project->save();
    }

  }

  private function downloadAttachment($sf_download_url) {
    $client = \Drupal::service('salesforce.client');
    try {
      $file_data = $client->httpRequestRaw($sf_download_url);
    }
    catch (\Exception $e) {
      // Unable to fetch file data from SF.
      \Drupal::logger('BuildingHousing')
        ->error("Failed to fetch attachment {$sf_download_url} from Salesforce");
      return FALSE;
    }
    return $file_data;
  }

  private function saveAttachment($file_data, $destination, $createdDateTime) {
    try {
      $file = \Drupal::service('file.repository')
        ->writeData($file_data, $destination, FileSystemInterface::EXISTS_REPLACE);
      if ($file) {
        // Set the created date specifically because we want it
        // to sync with the files' create datetime in Salesforce.
        $file->set('created', $createdDateTime);
        $file->save();
        return $file;
      }
    }
    catch (DirectoryNotReadyException|FileException $e) {
      \Drupal::logger('BuildingHousing')
        ->error("Failed to save attachment to {$destination} from Salesforce");
    }
    return FALSE;
  }

  private function loadAttachment($filepath, $createdDateTime) {
    try {
      $file = \Drupal::service('file.repository')->loadByUri($filepath);
      if (!$file) {
        $file = \Drupal::entityTypeManager()
          ->getStorage('file')
          ->loadByProperties(['uri' => $filepath]);
        if ($file) {
          $file = reset($file);
        }
      }
      // If the created date for the file is not the same as the
      // created date in Salesforce - then update the created date.
      // This is likely because a user changed the created date in
      // Salesforce in order to locate the file correctly on the
      // timeline.
      if ($file) {
        if ($file->get('created')->value != $createdDateTime) {
          $file->set('created', $createdDateTime);
          $file->save();
        }
        return $file;
      }
    }
    catch (Exception $e) {
      \Drupal::logger('BuildingHousing')
        ->error("Failed to load an existing attachment file {$filepath} from local file system");
    }
    return FALSE;
  }

  /**
   * @param $fileType string "image" or "doscument"
   * @param $file int The Id for a file object
   * @param $update_entities array of node entites to linke the file to
   *
   * @return bool True if a file was linked to a bh_project entity. False if no
   *              file was linked or if a file was linked to a bh_update.
   *              (bh_update will already be saved by the event calling process,
   *              so no need to save early in this class)
   */
  private function linkAttachment($fileType, $file, $update_entities ) {

    $fieldName = ($fileType == 'image' ? 'field_bh_project_images' : 'field_bh_attachment');

    // Link the file to the two entities
    $save = FALSE;
    foreach($update_entities as $bh_entity) {
      if ($bh_entity->get($fieldName)->isEmpty()) {
        // Entity has no files linked, so simply link this file now.
        $bh_entity->set($fieldName, ['target_id' => $file->id()]);
        $save = $save || ($bh_entity->bundle() !== "bh_update");
      }

      else {
        // Entity has files linked, so check if this file is linked
        $islinked = FALSE;
        if ($linked_files = $bh_entity->get($fieldName)->getValue()) {
          foreach ($linked_files as $linked_file) {
            if ($linked_file["target_id"] == $file->id()) {
              // OK, so the Entity already has this file linked - do nothing.
              $islinked = TRUE;
              continue;
            }
          }
        }
        if (!$islinked) {
          // Entity does not have the file already linked so link the file.
          $bh_entity->get($fieldName)
            ->appendItem(['target_id' => $file->id()]);
          $save = $save || ($bh_entity->bundle() !== "bh_update");
        }
      }
    }
    return $save;

  }

  private function getChatterMessages($sf_data, $bh_update) {

    try {
      $client = \Drupal::service('salesforce.client');
      $authProvider = \Drupal::service('plugin.manager.salesforce.auth_providers');
      $chatterFeedURL = $authProvider->getProvider()->getApiEndpoint() . "chatter/feeds/record/" . $sf_data->id() . "/feed-elements";
      $chatterData = $client->httpRequestRaw($chatterFeedURL);
      return $chatterData ? json_decode($chatterData) : NULL;
    }

    catch (\Exception $e) {
      $this->mailException([
        '@url' => "URL: {$chatterFeedURL}",
        '@err' => $e->getMessage(),
        '@update_id' => $bh_update->id()]);
      throw new \Exception($e->getMessage(), $e->getCode());
    }
  }

  private function getLegacyMessage($sf_data) {
    if ($sf_data->field("Publish_to_Web__c") && $sf_data->field("Type__c") == "Text Update") {
      // Process the "messages" in the update_u object.
      $legacy_data = [
        "elements" => [
          [
            "type" => "TextPost",
            "body" => ["text" => $sf_data->field("Update_Body__c")],
            "actor" => ["displayName" => 'City of Boston'],
            "createdDate" => strtotime($sf_data->field("CreatedDate")),
            "modifiedDate" => strtotime($sf_data->field("LastModifiedDate")),
            'id' => $sf_data->field("Id")
          ]
        ]
      ];
      return json_decode(json_encode($legacy_data), FALSE);
    }
    return FALSE;
  }

  private function processTextUpdates($textUpdateData, $bh_update, $source) {
    // Check for chatter text updates.
    try {

      $currentTextUpdateIds = [];
      $remove = TRUE;

      // Cache existing text updates in bh_update record for this property.
      foreach ($bh_update->field_bh_text_updates as $key => $currentTextUpdate) {
        $textData = $currentTextUpdate->getValue();
        $textData = json_decode($textData['value']);
        // If we don't know the source, then we cannot safely delete.
        $remove = ($remove && !empty($textData->s));
        if (empty($textData->s) || $textData->s == $source) {
          $currentTextUpdateIds[$textData->id] = $key;
        }
      }

      // Process the Chatter messages provided.
      foreach ($textUpdateData->elements as $post) {

        $allowed_chatters = ["TextPost", "ContentPost"];
        if (in_array($post->type, $allowed_chatters)) {
          if (!empty($post->body->text)) {
            $message = ($post->body->isRichText ? $post->body->messageSegments : $post->body->text);
            $drupalPost = [
              'text' => $this->_cleanupTextMessage($message, $post->body->isRichText),
              'author' => $post->actor->displayName ?? 'City of Boston',
              'date' => $post->createdDate ?? $this->now,
              'updated' => $post->capabilities->edit->lastEditedDate ?? ($post->modifiedDate ?? $this->now),
              's' => $source,
              'id' => $post->id ?? '',
            ];
            if (!array_key_exists($post->id, $currentTextUpdateIds)) {
              // New posts.
              $bh_update->field_bh_text_updates->appendItem(json_encode($drupalPost));
            }
            else {
              $key = $currentTextUpdateIds[$post->id];
              $textData = json_decode($bh_update->field_bh_text_updates[$key]->value);
              if (!empty($textData->updated) && strtotime($drupalPost["updated"]) != strtotime($textData->updated)) {
                // Updated posts.
                $bh_update->field_bh_text_updates->set($key, json_encode($drupalPost));
              }
              if (empty($textData->updated) && strtotime($drupalPost["updated"]) != strtotime($textData->date)) {
                $bh_update->field_bh_text_updates->set($key, json_encode($drupalPost));
              }
            }
            if ($remove && array_key_exists($post->id, $currentTextUpdateIds)) {
              unset($currentTextUpdateIds[$post->id]);
            }
          }
        }
      }
      if ($remove) {
        // Now remove any chatter items that have been deleted in SF.
        $currentTextUpdateIds = array_flip($currentTextUpdateIds);
        $currentTextUpdateIds = array_reverse($currentTextUpdateIds);
        foreach ($currentTextUpdateIds as $key => $post_id) {
          // Delete un-matched posts.
          $bh_update->field_bh_text_updates->removeItem($key);
        }
      }
    }

    catch (\Exception $e) {
      $this->mailException([
        '@url' => "Post ID: {$post->id}",
        '@err' => $e->getMessage(),
        '@update_id' => $bh_update->id()]);
      throw new \Exception($e->getMessage(), $e->getCode());
    }
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
      return $this->_unwrapUrl($url);
    }
    else {
      // Try to build the url out.
      if (!\Drupal::pathValidator()->isValid("http://" . $url)) {
        // just missing the protocol
        $url = "http://{$url}";
        return $this->_unwrapUrl($url);
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
   * Tries to remove proofpoint if the url has been cut/pasted from an email.
   *
   * @param string $url
   *
   * @return string
   */
  private function _unwrapUrl(string $url) {
    if (stripos($url, 'https://urldefense') !== FALSE) {
      $results = [];
      if (preg_match("/__.*__?/", $url, $results)) {
        if ($results && count($results) == 1) {
          if (\Drupal::pathValidator()->isValid($results[0])) {
            $url = trim($results[0], "_");
          }
        }
      }
    }
    return $url;
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

  private function _cleanupTextMessage($message, $richText = FALSE) {
    if ($richText) {
      // todo: Reformat $message using rich text - if exists.
      //  sanitize rich text (remove markup except bold and italics)
      $build_msg = "";
      $allowed_tags = ["b", "i", "a", "p"];
      foreach ($message as $msgPart) {
        switch ($msgPart->type) {
          case "Text":
            $msgPart->text = html_entity_decode($msgPart->text);
            $msgPart->text = html_entity_decode(preg_replace("/&nbsp;/i", " ", htmlentities($msgPart->text)));
            $build_msg .= $msgPart->text ?? "";
            break;
          case "MarkupBegin":
            if (!empty($msgPart->htmlTag) && in_array(strtolower($msgPart->htmlTag), $allowed_tags)) {
              switch (strtolower($msgPart->htmlTag)) {
                case "p":
                  // Strip out paragraphs. A timeline entry is to be a single
                  // paragraph, so just concatinate paragraphs with a space.
                  $build_msg .= " ";
                  break;
                case "b":
                  $build_msg .= "<span class='font-weight: bold;'>";
                  break;
                case "i":
                  $build_msg .= "<span class='font-style: italic;'>";
                  break;
                case "a":
                  $build_msg .= "<a href=\"{$msgPart->url}\">";
                  break;
              }
            }
            break;
          case "MarkupEnd":
            if (!empty($msgPart->htmlTag) && in_array(strtolower($msgPart->htmlTag), $allowed_tags)) {
              switch (strtolower($msgPart->htmlTag)) {
                case "b":
                case "i":
                  $build_msg .= "</span> ";
                  break;

                case "p":
                  $build_msg .= $msgPart->text ?? "";
                  break;

                case "a":
                  $build_msg .= "</a>";
                  break;
              }
            }
            break;
        }
      }
    }
    $build_msg = preg_replace('/\s*\n\s*\n\s*/', " ", $build_msg);
    // Cleanup the $message - remove images, emoji's etc.
    // @see http://unicode.org/emoji/charts/full-emoji-list.html
    $replacements = [
      "/\[Image:.*\]/u",        // Embedded Images
      '/[\x{0080}-\x{02AF}'
      .'\x{0300}-\x{03FF}'
      .'\x{0600}-\x{06FF}'
      .'\x{0C00}-\x{0C7F}'
      .'\x{1DC0}-\x{1DFF}'
      .'\x{1E00}-\x{1EFF}'
      .'\x{2000}-\x{209F}'
      .'\x{20D0}-\x{214F}'
      .'\x{2190}-\x{23FF}'
      .'\x{2460}-\x{25FF}'
      .'\x{2600}-\x{27EF}'
      .'\x{2900}-\x{29FF}'
      .'\x{2B00}-\x{2BFF}'
      .'\x{2C60}-\x{2C7F}'
      .'\x{2E00}-\x{2E7F}'
      .'\x{3000}-\x{303F}'
      .'\x{A490}-\x{A4CF}'
      .'\x{E000}-\x{F8FF}'
      .'\x{FE00}-\x{FE0F}'
      .'\x{FE30}-\x{FE4F}'
      .'\x{1F000}-\x{1F02F}'
      .'\x{1F0A0}-\x{1F0FF}'
      .'\x{1F100}-\x{1F64F}'
      .'\x{1F680}-\x{1F6FF}'
      .'\x{1F910}-\x{1F96B}'
      .'\x{1F980}-\x{1F9E0}]/u',  // Comprehensive emoji list
    ];
    return preg_replace($replacements, "", $build_msg);
  }
//https://boston.lndo.site/admin/content/salesforce/1142076/edit
  private function mailException($params) {
    $mailManager = \Drupal::service('plugin.manager.mail');
    $mailManager->mail(
      "node_buildinghousing",
      "sync_webupdate_failed",
      "david.upton@boston.gov",
      "en",
      $params,
      NULL,
      TRUE);
  }
}
