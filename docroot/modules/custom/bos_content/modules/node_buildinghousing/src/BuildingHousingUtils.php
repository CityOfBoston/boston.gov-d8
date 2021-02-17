<?php

namespace Drupal\node_buildinghousing;

use Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException;
use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Drupal\Core\Entity\EntityInterface as EntityInterface;
use Drupal\Core\Entity\EntityMalformedException;
use Drupal\Core\GeneratedUrl;
use Drupal\taxonomy\Entity\Term;
use http\Client\Request;

/**
 * BuildingHousingUtils - Utilities and helper functions for Building Housing.
 */
class BuildingHousingUtils {

  /**
   * Project Public Stage.
   *
   * @var string|null
   */
  public $publicStage = NULL;


  /**
   * Project Entity.
   *
   * @var \Drupal\Core\Entity\EntityInterface|null
   */
  public $project = NULL;


  /**
   * Project Web Update Entity.
   *
   * @var \Drupal\Core\Entity\EntityInterface|null
   */
  public $webUpdate = NULL;

  /**
   * Get any meetings from a WebUpdateId.
   *
   * @param string $webUpdateId
   *   Project Web Update ID.
   *
   * @return bool|EntityInterface[]|null
   *   False, Null, array or Entities
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public static function getMeetingsFromWebUpdateId(string $webUpdateId) {
    $meetings = \Drupal::entityTypeManager()->getStorage('node')->loadByProperties([
        'field_bh_update_ref' => $webUpdateId,
        'type' => 'bh_meeting'
      ])
      ?? NULL;

    if ($meetings && count($meetings) >= 1) {
      return $meetings;
    }

    return FALSE;
  }

  /**
   * Get a meetings from an EventId.
   *
   * @param string $eventId
   *   Event ID.
   *
   * @return bool|EntityInterface[]|null
   *   False, Null, array or Entities
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public static function getMeetingFromEventId(string $eventId) {
    $meetings = \Drupal::entityTypeManager()->getStorage('node')->loadByProperties([
        'field_bh_event_ref' => $eventId,
        'type' => 'bh_meeting'
      ])
      ?? NULL;

    if ($meetings && count($meetings) >= 1) {
      return array_shift($meetings);
    }

    return FALSE;
  }

  /**
   * Get a Project's WebUpdate.
   *
   * @param \Drupal\Core\Entity\EntityInterface $projectEntity
   *   Building Housing Project Entity.
   *
   * @return bool|mixed
   *   False, Web Update Entity.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public static function getWebUpdate(EntityInterface $projectEntity) {
    $webUpdate = \Drupal::entityTypeManager()->getStorage('node')->loadByProperties([
      'field_bh_project_ref' => $projectEntity->id(),
        'type' => 'bh_update',
        'field_sf_web_update' => TRUE
      ])
      ?? NULL;

    if ($webUpdate && count($webUpdate) >= 1) {
      return reset($webUpdate);
    }

    return FALSE;
  }

  /**
   * Set the Project Weblink onto a Web Update to write back to SF.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   Building Housing Project Entity.
   *
   * @return \Drupal\Core\GeneratedUrl|string|null
   *   URL Object, URL String, or null
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Core\Entity\EntityMalformedException
   */
  public function setProjectWebLink(EntityInterface &$entity) {

    $project = $entity->get('field_bh_project_ref')->target_id ? \Drupal::entityTypeManager()->getStorage('node')->load($entity->get('field_bh_project_ref')->target_id) : NULL;

    if ($project) {
      $projectWebLink = $project->toLink()->getUrl()->setAbsolute(TRUE)->toString() ?? NULL;
      $entity->set('field_bh_project_web_link', $projectWebLink);

      return $projectWebLink;
    }
  }

  /**
   * Set the Public Stage of a Project.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   Building Housing Project Entity.
   *
   * @return string|null
   *   False or Public Stage that was set.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function setPublicStage(EntityInterface &$entity) {

    // @TODO: RM after no issues
    // $projectRecordType = \Drupal\taxonomy\Entity\Term::load($entity->get('field_bh_record_type')->target_id)->name->value ?? null;
    // $projectRecordType = $projectRecordType == '0120y0000007rw7AAA' ? 'Disposition' : $projectRecordType;
    // $projectRecordType = $projectRecordType == '012C0000000Hqw0IAC' ? 'NHD Development' : $projectRecordType;

    $projectRecordType = self::getProjectRecordType($entity);

    $projectStatus = $entity->get('field_bh_project_status')->target_id ? Term::load($entity->get('field_bh_project_status')->target_id)->name->value : NULL;

    $projectStage = $entity->get('field_bh_project_stage')->target_id ? Term::load($entity->get('field_bh_project_stage')->target_id)->name->value : NULL;

    $publicStages = \Drupal::entityTypeManager()->getStorage('taxonomy_term')->loadTree('bh_public_stage') ?? NULL;

    foreach ($publicStages as $key => $publicStage) {
      $publicStages[$publicStage->name] = $publicStage->tid;
      unset($publicStages[$key]);
    }

    $projectCompeteDate = $entity->get('field_bh_project_complete_date')->value ?? NULL;

    $publicStage = NULL;

    // Rule B.
    if (in_array($projectRecordType, ['Disposition'])
      && in_array($projectStatus, ['Not Started', 'Hold', 'Suspended'])
    ) {
      $publicStage = 'Not Active';
    }

    // Rule C.
    if (in_array($projectRecordType, ['Disposition'])
      && in_array($projectStatus, ['Active'])
      && in_array($projectStage, [
        'Under Consideration',
        'Research for Disposition Underway',
        'Future Consideration',
        'Remnant - No Interest',
        'Urban Wild / Conservation',
        'Municipal Use',
        'Tax Taking / Subject to Redemption'
      ])
    ) {
      $publicStage = 'Not Active';
    }

    // Rule D.
    if (in_array($projectRecordType, ['Disposition'])
      && in_array($projectStatus, ['Active'])
      && in_array($projectStage, [
        'Community Meeting In Process',
        'RFP In Process'
      ])
    ) {
      $publicStage = 'Project Launch';
    }

    // Rule E.
    if (in_array($projectRecordType, ['Disposition'])
      && in_array($projectStatus, ['Active'])
      && in_array($projectStage, [
        'RFP Issued',
        'Proposal Review'
      ])
    ) {
      $publicStage = 'Selecting Developer';
    }

    // Rule F.
    if (in_array($projectRecordType, ['Disposition'])
      && in_array($projectStatus, ['Active'])
      && in_array($projectStage, [
        'Under Agreement',
        'Closing Underway'
      ])
    ) {
      $publicStage = 'City Planning Process';
    }

    // Rule G.
    if (in_array($projectRecordType, ['Disposition'])
      && in_array($projectStatus, ['Active'])
      && in_array($projectStage, ['In construction'])
    ) {
      $publicStage = 'In Construction';
    }

    // Rule H.
    if (in_array($projectRecordType, ['Disposition'])
      && in_array($projectStatus, ['Active'])
      && in_array($projectStage, ['Construction complete - Project open'])
    ) {
      $publicStage = 'Project Completed';
    }

    // Rule I.
    if (in_array($projectRecordType, ['Disposition'])
      && in_array($projectStatus, ['Completed'])
    // @TODO: ? What if the ProjectCompleteDate is null?
      && strtotime($projectCompeteDate) >= strtotime('-1 year')
    ) {
      $publicStage = 'Project Completed';
    }

    // Rule J.
    if (in_array($projectRecordType, ['NHD Development'])
      && in_array($projectStatus, ['Active'])
      && in_array($projectStage, [
        'Commitment',
        'Awarded',
        'Pre-Commitment Process',
        'Closing Underway',
        'Pre-development'
      ])
    ) {
      $publicStage = 'City Planning Process';
    }

    // Rule K.
    if (in_array($projectRecordType, ['NHD Development'])
      && in_array($projectStatus, ['Active'])
      && in_array($projectStage, ['In construction'])
    ) {
      $publicStage = 'In Construction';
    }

    // Rule L.
    if (in_array($projectRecordType, ['NHD Development'])
      && in_array($projectStatus, ['Active'])
      && in_array($projectStage, ['Construction complete - Project open'])
    ) {
      $publicStage = 'Project Completed';
    }

    // Rule M.
    if (in_array($projectRecordType, ['NHD Development'])
      && in_array($projectStatus, ['Completed'])
      && strtotime($projectCompeteDate) >= strtotime('-1 year')
    ) {
      $publicStage = 'Project Completed';
    }

    // Set the Public Stage on the Project or unset it if no rules apply.
    if ($publicStage) {
      $entity->set('field_bh_public_stage', [$publicStages[$publicStage]]);
    }
    else {
      $entity->set('field_bh_public_stage', []);
    }

    return $this->publicStage = $publicStage;
  }

  /**
   * Get a Project's Record Type (Disposition or Development)
   *
   * @param \Drupal\Core\Entity\EntityInterface $projectEntity
   *   Building Housing Project Entity.
   *
   * @return string|null
   *   Record Type of Project or null.
   */
  public static function getProjectRecordType(EntityInterface $projectEntity) {
    $projectRecordType = Term::load($projectEntity->get('field_bh_record_type')->target_id)->name->value ?? NULL;
    $projectRecordType = $projectRecordType == '0120y0000007rw7AAA' ? 'Disposition' : $projectRecordType;
    $projectRecordType = $projectRecordType == '012C0000000Hqw0IAC' ? 'NHD Development' : $projectRecordType;

    return $projectRecordType;
  }

  /**
   * Set a Street View Photo given coordinates.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   Building Housing Project or Parcel Entity.
   * @param string $fieldName
   *   Name of the field to save the Street View Photo.
   *
   * @return bool
   *   True or False is a street view was found and saved to the project.
   */
  public function setStreetViewPhoto(EntityInterface &$entity, string $fieldName = 'field_bh_street_view_photo') {
    $streetViewPhotoSet = FALSE;

    if ($this->publicStage && $coordinates = $entity->get('field_bh_coordinates')->value) {

      $endpoint = 'https://maps.googleapis.com/maps/api/streetview/metadata';
      $googleMapsApiKey = 'AIzaSyD8aXv_AZ9dpY8asHiqIsxdNMOBmCGYguY';
      $size = '600x300';

      $client = \Drupal::httpClient();
      $streetViewMetaData = $client->get("$endpoint?size=$size&location=$coordinates&key=$googleMapsApiKey");
      $streetViewMetaData = $streetViewMetaData->getBody() ? json_decode($streetViewMetaData->getBody()) : NULL;

    }

    return $streetViewPhotoSet;
  }

  /**
   * Update project goals field display.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   Building Housing Project Entity.
   */
  public function updateProjectGoalsFieldDisplay(EntityInterface &$entity) {

  }

  /**
   * Set (Create/Update) an Event Entity from a BH Meeting.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   Building Housing Meeting Entity.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function setMeetingEvent(EntityInterface &$entity) {

    // GET WEB UPDATE.
    $webUpdate = !$entity->field_bh_update_ref->isEmpty() ? $entity->field_bh_update_ref->referencedEntities()[0] : NULL;
    // GET PROJECT.
    $project = !$webUpdate->field_bh_project_ref->isEmpty() ? $webUpdate->field_bh_project_ref->referencedEntities()[0] : NULL;

    // @TODO: Change  out for value on meeting import
    $contactEmail = $project->field_project_manager_email->value ?? 'DND.email@boston.dev';
    $contactName = $project->field_bh_project_manager_name->value ?? 'DND';

    if (!$entity->field_bh_event_ref->isEmpty()) {
      $event = $entity->field_bh_event_ref->referencedEntities()[0];

      if ($entity->field_bh_virt_meeting_web_addr->value) {
        $event->set('field_details_link', [
          'uri' => $entity->field_bh_virt_meeting_web_addr->value,
          'title' => t('Join Meeting'),
          'options' => [
            'attributes' => [
              'target' => '_blank',
            ],
          ],
        ]);
      }

      $event->set('title', $entity->getTitle() ?? '');
      $event->set('body', $entity->get('body')->value ?? '');
      $event->set('field_event_contact', $contactName);
      $event->set('field_email', $contactEmail);
      $event->set('field_event_date_recur', [
        'value' => $entity->field_bh_meeting_start_time->value ?? '',
        'end_value' => $entity->field_bh_meeting_end_time->value ?? '',
        // 'timezone' => 'Etc/GMT+10',
        'timezone' => 'Pacific/Honolulu',
      ]);

      $event->save();

    }
    else {
      $event = \Drupal::entityTypeManager()
        ->getStorage('node')
        ->create([
          'type' => 'event',
          'title' => $entity->getTitle() ?? '',
          'body' => $entity->get('body')->value ?? '',
          'field_intro_text' => t('The Department of Neighborhood Development is looking for feedback from the community. Join us to see the latest plans and share your thoughts.'),
          'field_address' => [
            'country_code' => 'US',
            'address_line1' => t('THIS MEETING WILL BE HELD VIRTUALLY.'),
            'locality' => 'Boston',
            'administrative_area' => 'MA',
            'postal_code' => '02201',
          ],
          'field_event_contact' => $contactName,
          'field_email' => $contactEmail,
            // Event Type: "Civic Engagement".
          'field_event_type' => [['target_id' => 1831]],
          'field_event_date_recur' => [
            'value' => $entity->field_bh_meeting_start_time->value ?? '',
            'end_value' => $entity->field_bh_meeting_end_time->value ?? '',
            // 'timezone' => 'Etc/GMT+10',
            'timezone' => 'Pacific/Honolulu',
          ],
        ]);

      if ($entity->field_bh_virt_meeting_web_addr->value) {
        $event->set('field_details_link', [
          'uri' => $entity->field_bh_virt_meeting_web_addr->value,
          'title' => t('Register for Event'),
          'options' => [
            'attributes' => [
              'target' => '_blank',
            ],
          ],
        ]);
      }

      $event->setPublished(TRUE);
      $event->set('moderation_state', 'published');
      $event->save();

      $entity->set('field_bh_event_ref', ['target_id' => $event->id()]);
    }
  }



  public function setParcelGeoPolyData(EntityInterface &$entity) {
    $geoPolySet = FALSE;

    if ($entity) {

      $parcelId = '1100085010';

      $endpoint = "https://services.arcgis.com/sFnw0xNflSi8J0uh/arcgis/rest/services/Parcels_2020/FeatureServer/8/query?outFields='geometry'&f=pgeojson&where=PID_LONG%20%3D%20'$parcelId'";

      $client = \Drupal::httpClient();
      $geoPolyMetaData = $client->get("$endpoint");
      $geoPolyMetaData = $geoPolyMetaData->getBody() ? json_decode($geoPolyMetaData->getBody()) : NULL;

    }

    return $geoPolySet;


  }



}
