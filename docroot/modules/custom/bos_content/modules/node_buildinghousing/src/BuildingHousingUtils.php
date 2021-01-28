<?php

namespace Drupal\node_buildinghousing;



use http\Client\Request;

/**
 * BuildingHousingUtils - Utilities and helper functions for Building Housing
 */
class BuildingHousingUtils {


  public $publicStage = NULL;
  public $project = NULL;
  public $webUpdate = NULL;


  public static function helloWorld () {

    return 'Hello Building Housing World!';
  }


  public static function getMeetingsFromWebUpdateID ($webUpdateId) {
    $meetings = \Drupal::entityTypeManager()->getStorage('node')->loadByProperties([
        'field_bh_update_ref' => $webUpdateId,
        'type' => 'bh_meeting'
      ])
      ?? null;

    if ($meetings && count($meetings) >= 1) {
      return $meetings;
    }

    return false;
  }


  public static function getWebUpdate ($projectEntity) {
    $webUpdate = \Drupal::entityTypeManager()->getStorage('node')->loadByProperties([
      'field_bh_project_ref' => $projectEntity->id(),
        'type' => 'bh_update',
        'field_sf_web_update' => true
      ])
      ?? null;

    if ($webUpdate && count($webUpdate) >= 1) {
      return reset($webUpdate);
    }

    return false;
  }

  public function setProjectWebLink (&$entity) {

    $project = $entity->get('field_bh_project_ref')->target_id ? \Drupal::entityTypeManager()->getStorage('node')->load($entity->get('field_bh_project_ref')->target_id) : null;

    if ($project) {
      $projectWebLink = $project->toLink()->getUrl()->setAbsolute(true)->toString() ?? NULL;
      $entity->set('field_bh_project_web_link', $projectWebLink);

      return $projectWebLink;
    }
  }

  public function setPublicStage (&$entity) {

    //@TODO: RM after no issues
//    $projectRecordType = \Drupal\taxonomy\Entity\Term::load($entity->get('field_bh_record_type')->target_id)->name->value ?? null;
//    $projectRecordType = $projectRecordType == '0120y0000007rw7AAA' ? 'Disposition' : $projectRecordType;
//    $projectRecordType = $projectRecordType == '012C0000000Hqw0IAC' ? 'NHD Development' : $projectRecordType;

    $projectRecordType = self::getProjectRecordType($entity);

    $projectStatus = $entity->get('field_bh_project_status')->target_id ? \Drupal\taxonomy\Entity\Term::load($entity->get('field_bh_project_status')->target_id)->name->value : null;

    $projectStage = $entity->get('field_bh_project_stage')->target_id ? \Drupal\taxonomy\Entity\Term::load($entity->get('field_bh_project_stage')->target_id)->name->value : null;

    $publicStages = \Drupal::entityTypeManager()->getStorage('taxonomy_term')->loadTree('bh_public_stage') ?? null;

    foreach ($publicStages as $key => $publicStage) {
      $publicStages[$publicStage->name] = $publicStage->tid;
      unset($publicStages[$key]);
    }

    $projectCompeteDate = $entity->get('field_bh_project_complete_date')->value ?? null;

    $publicStage = null;


    // Rule B
    if (in_array($projectRecordType, ['Disposition'])
      && in_array($projectStatus, ['Not Started', 'Hold', 'Suspended'])
    ) {
      $publicStage = 'Not Active';
    }

    // Rule C
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

    // Rule D
    if (in_array($projectRecordType, ['Disposition'])
      && in_array($projectStatus, ['Active'])
      && in_array($projectStage, [
        'Community Meeting In Process',
        'RFP In Process'
      ])
    ) {
      $publicStage = 'Project Launch';
    }

    // Rule E
    if (in_array($projectRecordType, ['Disposition'])
      && in_array($projectStatus, ['Active'])
      && in_array($projectStage, [
        'RFP Issued',
        'Proposal Review'
      ])
    ) {
      $publicStage = 'Selecting Developer';
    }

    // Rule F
    if (in_array($projectRecordType, ['Disposition'])
      && in_array($projectStatus, ['Active'])
      && in_array($projectStage, [
        'Under Agreement',
        'Closing Underway'
      ])
    ) {
      $publicStage = 'City Planning Process';
    }

    // Rule G
    if (in_array($projectRecordType, ['Disposition'])
      && in_array($projectStatus, ['Active'])
      && in_array($projectStage, ['In construction'])
    ) {
      $publicStage = 'In Construction';
    }

    // Rule H
    if (in_array($projectRecordType, ['Disposition'])
      && in_array($projectStatus, ['Active'])
      && in_array($projectStage, ['Construction complete - Project open'])
    ) {
      $publicStage = 'Project Completed';
    }

    // Rule I
    if (in_array($projectRecordType, ['Disposition'])
      && in_array($projectStatus, ['Completed'])
      && strtotime($projectCompeteDate) >= strtotime('-1 year') //@TODO: ? What if the ProjectCompleteDate is null?
    ) {
      $publicStage = 'Project Completed';
    }

    // Rule J
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

    // Rule K
    if (in_array($projectRecordType, ['NHD Development'])
      && in_array($projectStatus, ['Active'])
      && in_array($projectStage, ['In construction'])
    ) {
      $publicStage = 'In Construction';
    }

    // Rule L
    if (in_array($projectRecordType, ['NHD Development'])
      && in_array($projectStatus, ['Active'])
      && in_array($projectStage, ['Construction complete - Project open'])
    ) {
      $publicStage = 'Project Completed';
    }

    // Rule M
    if (in_array($projectRecordType, ['NHD Development'])
      && in_array($projectStatus, ['Completed'])
      && strtotime($projectCompeteDate) >= strtotime('-1 year')
    ) {
      $publicStage = 'Project Completed';
    }

    // Set the Public Stage on the Project or unset it if no rules apply
    if ($publicStage) {
      $entity->set('field_bh_public_stage', [$publicStages[$publicStage]]);
    } else {
      $entity->set('field_bh_public_stage', []);
    }

    return $this->publicStage = $publicStage;
  }

  public static function getProjectRecordType ($projectEntity) {
    $projectRecordType = \Drupal\taxonomy\Entity\Term::load($projectEntity->get('field_bh_record_type')->target_id)->name->value ?? null;
    $projectRecordType = $projectRecordType == '0120y0000007rw7AAA' ? 'Disposition' : $projectRecordType;
    $projectRecordType = $projectRecordType == '012C0000000Hqw0IAC' ? 'NHD Development' : $projectRecordType;

    return $projectRecordType;
  }

  public function setStreetViewPhoto (&$entity, $fieldName =  'field_bh_street_view_photo') {
    $streetViewPhotoSet = false;

    if ($this->publicStage && $coordinates = $entity->get('field_bh_coordinates')->value) {

      $endpoint = 'https://maps.googleapis.com/maps/api/streetview/metadata';
      $googleMapsApiKey = 'AIzaSyD8aXv_AZ9dpY8asHiqIsxdNMOBmCGYguY';
      $size = '600x300';


      $client = \Drupal::httpClient();
      $streetViewMetaData = $client->get( "$endpoint?size=$size&location=$coordinates&key=$googleMapsApiKey");
      $streetViewMetaData = $streetViewMetaData->getBody() ? json_decode($streetViewMetaData->getBody()) : NULL;

    }

    return $streetViewPhotoSet;
  }

  public function updateProjectGoalsFieldDisplay (&$entity) {


  }



}
