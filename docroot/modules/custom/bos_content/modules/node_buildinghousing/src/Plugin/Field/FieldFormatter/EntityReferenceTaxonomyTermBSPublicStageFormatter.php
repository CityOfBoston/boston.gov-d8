<?php

namespace Drupal\node_buildinghousing\Plugin\Field\FieldFormatter;

use Drupal\Core\Url;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\Plugin\Field\FieldFormatter\EntityReferenceFormatterBase;
use Drupal\node_buildinghousing\BuildingHousingUtils as BHUtils;
use Drupal\webform\Plugin\WebformElement\DateTime;

/**
 * Plugin implementation of the 'entity reference taxonomy term Building Housing Public Stage' formatter.
 *
 * @FieldFormatter(
 *   id = "entity_reference_bh_public_stage",
 *   label = @Translation("Building Housing Public Stage"),
 *   description = @Translation("Display reference to taxonomy term for Building Housing Public Stage."),
 *   field_types = {
 *     "entity_reference"
 *   }
 * )
 */
class EntityReferenceTaxonomyTermBSPublicStageFormatter extends EntityReferenceFormatterBase
{

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode)
  {
    $parent_entity = $items->getEntity();
    $elements = [];

    $termStorage = \Drupal::entityTypeManager()->getStorage('taxonomy_term');


    $stageCurrentState = 'past'; //past, present, future.
    foreach ($this->getPublicStages() as $delta => $publicStage) {
      //$elements[$delta] = ['#markup' => $publicStage->name];
      $stageIsActive = $parent_entity->get('field_bh_public_stage')->target_id == $publicStage->tid;

      if ($stageCurrentState == 'past' && $stageIsActive) {
        $stageCurrentState = 'present';
      } elseif ($stageCurrentState == 'present' && !$stageIsActive) {
        $stageCurrentState = 'future';
      }

      $publicStageTerm = $termStorage->load($publicStage->tid);
      $vars = [];

      $stageTitle = $publicStageTerm->get('field_display_title') ?? null;
//      $stageIcon = $publicStageTerm->get('field_icon') ?? null;
      $stageIcon = $this->getStageIcon($publicStageTerm->getName(), $stageCurrentState) ?? null;
      $stageDescription = $publicStageTerm->get('description') ?? null;
      $stageDate = $this->getStageDate($parent_entity, $publicStageTerm);

      if ($stageTitle->isEmpty()) {
        continue;
      }

//      $vars['icon'] = $stageIcon->view('icon');
      $vars['icon'] = $stageIcon;
      $vars['label'] = $stageTitle->view(['label' => 'hidden']);
      $vars['body'] = $stageDescription->view(['label' => 'hidden']);
      $vars['date'] = $stageDate;
      $vars['currentState'] = $stageCurrentState;


      switch ($stageCurrentState) {
        case 'past':
//          $vars['currentState'] = \Drupal::theme()->render("bh_icons", ['type' => 'shopping']);
          break;
        case 'present':
//          $vars['icon'] = \Drupal::theme()->render("bh_icons", ['type' => 'parking']);
          break;
        case 'future':


          $stageDate = $stageDate ? $stageDate : 'To Be Determined';

          $vars['icon'] = \Drupal::theme()->render("bh_icons", ['type' => null]);
          $vars['body'] = t('Predicted Date: ') . $stageDate;
          $vars['date'] = '';
          break;
      }

      $elements[] = ['#markup' => \Drupal::theme()->render("bh_project_timeline_moment", $vars)];

      //@TODO: THis is just a temp place to put the meeting for styling dev
      if ($stageCurrentState == 'present') {
//        $elements[] = $this->getMeetings($parent_entity);
//        $elements[] = $this->getTexts($parent_entity);
        $elements[] = $this->getRFP($parent_entity);
        $elements[] = $this->getDocuments($parent_entity);
      }

    }


    return ['#markup' => \Drupal::theme()->render("bh_project_timeline", ['items' => $elements])];

//    return $elements;
  }

  private function getStageIcon ($stage, $stageCurrentState) {

    $stageIconMapping = [
      'Project Launch' => 'community-feedback',
      'Selecting Developer' => 'selecting-a-developer',
      'City Planning Process' => 'in-city-planning',
      'In Construction' => 'in-construction',
      'Project Completed' => 'completed',
      'Not Active' => '',
    ];

    $color = $stageCurrentState == 'present' ? 'ob' : 'cb';

    return \Drupal::theme()->render('bh_icons', ['type' => $stageIconMapping[$stage], 'fill' => $color]) ?? [];
  }

  private function getStageDate($project, $stage)
  {

    switch ($stage->getName()) {
      case 'Project Launch':
        $date = $project->get('field_bh_project_start_date')->value ?? null;
        break;
      case 'Selecting Developer':
        $date = $project->get('field_bh_rfp_issued_date')->value ?? null;
        break;
      case 'City Planning Process':
        switch (BHUtils::getProjectRecordType($project)) {
          case 'Disposition':
            $date = $project->get('field_bh_initial_td_vote_date')->value ?? null;
            break;
          case 'NHD Development':
            $date = $project->get('field_bh_dnd_funding_award_date')->value ?? null;
            break;
          default:
            $date = null;
        }
        break;
      case 'In Construction':
        $date = $project->get('field_bh_construction_start_date')->value ?? null;
        break;
      case 'Project Completed':
        $date = $project->get('field_bh_construct_complete_date')->value ?? null;
        break;
      default:
        $date = null;
    }

    $stageDate = $date ? $this->dateToSeason($date) : '';


    return $stageDate ?? $date ?? '';
  }


  public function getMeetings($project)
  {
    $elements = [];

    $data = [
      'label' => t('UPCOMING COMMUNITY MEETING'),
      'title' => t('Warren St 14-17 Public Meeting'),
      'body' => t('Description of what to expect at this public meeting, maybe a brief overview of the goals of the meeting.'),
      'icon' => \Drupal::theme()->render("bh_icons", ['type' => 'calendar']),
      'date' => 'DEC 15, 2020',
      'time' => '6-8PM',
      'link' => '/events',
      'currentState' => 'present',
      'addToCal' => NULL,
    ];


    $elements[] = ['#markup' => \Drupal::theme()->render("bh_project_timeline_meeting", $data)];


    return $elements;
  }

  public function getTexts($project)
  {
    $elements = [];

    $data = [
      'label' => t('Project Manager'),
      'title' => t('Anne Conway'),
      'body' => t('The Department of Neighborhood Development (DND) invites you to a virtual community meeting next month to introduce the preferred developer for the Request for Proposals (RFP) regarding 14-14A, 15-15A, and 17 Holborn St, Roxbury.
'),
      'icon' => \Drupal::theme()->render("bh_icons", ['type' => 'chat']),
      'date' => 'NOV 25, 2020',
      'currentState' => 'present',
    ];


    $elements[] = ['#markup' => \Drupal::theme()->render("bh_project_timeline_text", $data)];

    return $elements;
  }

  public function getRFP($project)
  {
    $elements = [];

    $today = new \DateTime('now');
    $rfpDate = $project->get('field_bh_rfp_issued_date')->value ?? null;

    if ($rfpDate) {

      $rfpDate = new \DateTime($rfpDate);

      $data = [
        'label' => t('Go to RFP list'),
        'url' => '/departments/neighborhood-development/requests-proposals', //@TODO: change out with config?
        'title' => t('Request For Proposals (RFP) Open for Bidding'),
        'body' => t('Visit the link below to learn more.'),
        'icon' => \Drupal::theme()->render("bh_icons", ['type' => 'timeline-building', 'fill' => '#288BE4']),
        'icon' => \Drupal::theme()->render("bh_icons", ['type' => 'timeline-building', 'fill' => '#091F2F']),
        'rfpListIcon' => \Drupal::theme()->render("bh_icons", ['type' => 'rfp-building-permit']),
        'date' => $rfpDate->format('M j, Y'),
        'currentState' => 'present',
      ];

//      if ($today->getTimestamp() <= $rfpDate->getTimestamp()) { // TESTING ONLY
      if ($today->getTimestamp() >= $rfpDate->getTimestamp()) { //CORRECT
        $elements[] = ['#markup' => \Drupal::theme()->render("bh_project_timeline_rfp", $data)];
      }
    }

    return $elements;
  }

  public function getDocuments($project)
  {
    $elements = [];

    $data = [
      'icon' => \Drupal::theme()->render("bh_icons", ['type' => 'dot-filled']),
      "fileIcon" => \Drupal::theme()->render("bh_icons", ['type' => 'file-pdf']),
      'date' => 'DEC 15, 2020',
      'currentState' => 'present',
    ];

    $data['documents'][] = [
      'label' => t('developer presentation'),
      'link' => 'WillemLevielle RFP Response - A.pdf',
      'url' => '/events?a',
    ];

    $data['documents'][] = [
      'label' => t('developer presentation'),
      'link' => 'WillemLevielle RFP Response - B.pdf',
      'url' => '/events?b',
    ];

    $data['documents'][] = [
      'label' => t('developer presentation'),
      'link' => 'WillemLevielle RFP Response - C.pdf',
      'url' => '/events?c',
    ];

    $data['documents'][] = [
      'label' => t('developer presentation'),
      'link' => 'WillemLevielle RFP Response - D.pdf',
      'url' => '/events?d',
    ];

    $data['documents'][] = [
      'label' => t('developer presentation'),
      'link' => 'WillemLevielle RFP Response - D.pdf',
      'url' => '/events?d',
    ];

    $data['documents'][] = [
      'label' => t('developer presentation'),
      'link' => 'WillemLevielle RFP Response - D.pdf',
      'url' => '/events?d',
    ];

    $data['documents'][] = [
      'label' => t('developer presentation'),
      'link' => 'WillemLevielle RFP Response - D.pdf',
      'url' => '/events?d',
    ];

    $elements[] = ['#markup' => \Drupal::theme()->render("bh_project_timeline_document", $data)];
    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public static function isApplicable(FieldDefinitionInterface $field_definition)
  {
    // This formatter is only available for taxonomy terms.
    $isTaxonomyTerm = $field_definition->getFieldStorageDefinition()->getSetting('target_type') == 'taxonomy_term';
    $isNode = $field_definition->getTargetEntityTypeId();
    $isBHProject = $field_definition->getTargetBundle();

    if ($isTaxonomyTerm && $isNode && $isBHProject) {
      return true;
    } else {
      return false;
    }
  }

  private function getPublicStages()
  {
    $publicStages = \Drupal::entityTypeManager()->getStorage('taxonomy_term')->loadTree('bh_public_stage') ?? null;
    return $publicStages;
  }

  private function dateToSeason($date)
  {
    $season = '';
    $seasonDate = new \DateTime($date);
    $monthDayDate = $seasonDate->format('md');

    switch (true) {
      //spring runs from March 1 (0301) to May 31 (0531)
      case $monthDayDate >= '0301' && $monthDayDate <= '0531':
        $season = 'Spring';
        break;
      //summer runs from June 1 (0601) to August 31 (0831)
      case $monthDayDate >= '0601' && $monthDayDate <= '0831':
        $season = 'Summer';
        break;
      //fall (autumn) runs from September 1 (0901) to November 30 (1130)
      case $monthDayDate >= '0901' && $monthDayDate <= '1130':
        $season = 'Fall';
        break;
      //winter runs from December 1 (1201) to February 28+1 (0229)
      case $monthDayDate >= '1201' || $monthDayDate <= '0229':
        $season = 'Winter';
        break;
    }

    return $season . ' ' . $seasonDate->format('Y');
  }

}
