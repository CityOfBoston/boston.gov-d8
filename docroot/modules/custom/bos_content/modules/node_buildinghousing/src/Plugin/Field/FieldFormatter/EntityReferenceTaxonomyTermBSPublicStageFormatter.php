<?php

namespace Drupal\node_buildinghousing\Plugin\Field\FieldFormatter;

use Drupal\Core\Url;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\Plugin\Field\FieldFormatter\EntityReferenceFormatterBase;

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
class EntityReferenceTaxonomyTermBSPublicStageFormatter extends EntityReferenceFormatterBase {

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $parent_entity = $items->getEntity();
    $elements = [];

    $termStorage = \Drupal::entityTypeManager()->getStorage('taxonomy_term');


    $stageCurrentState = 'past'; //past, present, future.
    foreach ($this->getPublicStages() as $delta => $publicStage) {
      //$elements[$delta] = ['#markup' => $publicStage->name];
      $stageIsActive = $parent_entity->get('field_bh_public_stage')->target_id == $publicStage->tid;

      if ($stageCurrentState == 'past' && $stageIsActive) {
        $stageCurrentState = 'present';
      }elseif ($stageCurrentState == 'present' && !$stageIsActive) {
        $stageCurrentState = 'future';
      }

      $publicStageTerm = $termStorage->load($publicStage->tid);
      $vars = [];

      $stageTitle = $publicStageTerm->get('field_display_title') ?? null;
      $stageIcon = $publicStageTerm->get('field_icon') ?? null;
      $stageDescription = $publicStageTerm->get('description') ?? null;
      $stageDate = 'Winter 2020';

      if ($stageTitle->isEmpty()) {
        continue;
      }

      $vars['icon'] = $stageIcon->view('icon');
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
          $vars['icon'] = \Drupal::theme()->render("bh_icons", ['type' => null]);
          $vars['body'] = t('Predicted Date: ') . $stageDate;
          $vars['date'] = '';
          break;
      }

      $elements[] = ['#markup' => \Drupal::theme()->render("bh_project_timeline_moment", $vars)];

      //@TODO: THis is just a temp place to put the meeting for styling dev
      if ($stageCurrentState == 'present') {
        $elements[] = $this->getMeetings($parent_entity);
//        $elements[] = $this->getDocuments($parent_entity);
        $elements[] = $this->getTexts($parent_entity);
      }

    }


    return ['#markup' => \Drupal::theme()->render("bh_project_timeline", ['items' => $elements])];

//    return $elements;
  }


  public function getMeetings($project) {
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

  public function getTexts($project) {
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

  public function getDocuments($project) {
    $elements = [];

    $data = [
      'label' => t('developer presentation'),
      'title' => t('WillemLevielle RFP Response.pdf'),
      'icon' => \Drupal::theme()->render("bh_icons", ['type' => 'calendar']),
      'date' => 'DEC 15, 2020',
      'link' => '/events',
      'currentState' => 'present',
    ];


    $elements[] = ['#markup' => \Drupal::theme()->render("bh_project_timeline_document", $data)];
    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public static function isApplicable(FieldDefinitionInterface $field_definition) {
    // This formatter is only available for taxonomy terms.
    $isTaxonomyTerm = $field_definition->getFieldStorageDefinition()->getSetting('target_type') == 'taxonomy_term';
    $isNode = $field_definition->getTargetEntityTypeId();
    $isBHProject = $field_definition->getTargetBundle();

    if ($isTaxonomyTerm && $isNode && $isBHProject) {
      return true;
    }else{
      return false;
    }
  }

  private function getPublicStages () {
    $publicStages = \Drupal::entityTypeManager()->getStorage('taxonomy_term')->loadTree('bh_public_stage') ?? null;
    return $publicStages;
  }

}
