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
          $vars['icon'] = \Drupal::theme()->render("bh_icons", ['type' => 'parking']);
          break;
        case 'future':
          $vars['icon'] = \Drupal::theme()->render("bh_icons", ['type' => null]);
          $vars['body'] = t('Predicted Date: ') . $stageDate;
          $vars['date'] = '';
          break;
      }

      $elements[$delta] = ['#markup' => \Drupal::theme()->render("bh_project_timeline_moment", $vars)];

    }


    return ['#markup' => \Drupal::theme()->render("bh_project_timeline", ['items' => $elements])];

//    return $elements;
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
