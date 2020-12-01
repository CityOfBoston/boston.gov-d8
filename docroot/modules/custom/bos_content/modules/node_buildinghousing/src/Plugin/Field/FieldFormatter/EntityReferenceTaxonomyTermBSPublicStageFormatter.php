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

    $publicStages = $this->getPublicStages();

    foreach ($this->getPublicStages() as $delta => $publicStage) {
      //$elements[$delta] = ['#markup' => $publicStage->name];
      $stageIsActive = $parent_entity->get('field_bh_public_stage')->target_id == $publicStage->tid;

      $vars = [
        'classes' => [
          'icon' => 'icon-time',
          'label' => 'detail-item--secondary',
          'body' => 'detail-item__body--tertiary',
          'detail' => 'detail-item--middle m-v300',
        ],
      ];

      $vars['label'] = $publicStage->name;

      if ($stageIsActive) {
       $vars['body'] = 'Current Active Stage - ' . $publicStage->name;
       $vars['classes']['icon'] = 'icon-alert';
        $vars['classes']['label'] = 'detail-item--secondary grid-card__title';
        $vars['classes']['detail'] = 'detail-item--middle m-v300 grid-card__title';
      }
      $elements[$delta] = ['#markup' => \Drupal::theme()->render("detail_item", $vars)];

    }

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
