<?php

namespace Drupal\node_buildinghousing\Plugin\views\field;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Component\Utility\Random;
use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\views\ResultRow;

/**
 * A handler to provide a field that is completely custom by the administrator.
 *
 * @ingroup views_field_handlers
 *
 * @ViewsField("building_housing_project_type_views_field")
 */
class BuildingHousingProjectTypeViewsField extends FieldPluginBase {

  /**
   * {@inheritdoc}
   */
  public function usesGroupBy() {
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function query() {
    // Do nothing -- to override the parent query.
  }

  /**
   * {@inheritdoc}
   */
  protected function defineOptions() {
    $options = parent::defineOptions();

    $options['hide_alter_empty'] = ['default' => FALSE];
    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    parent::buildOptionsForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function render(ResultRow $values) {
    // Return a random text, here you can include your custom logic.
    // Include any namespace required to call the method required to generate
    // the desired output.
//    $random = new Random();
//    return $random->name();

    $mainType = null;

    $termStorage = \Drupal::entityTypeManager()->getStorage('taxonomy_term');
    $entity = $values->_entity;

    if ($dispositionTypeId = $entity->get('field_bh_disposition_type')->target_id) {
      $dispositionTypeParents = $termStorage->loadAllParents($dispositionTypeId);
      $mainType = !empty($dispositionTypeParents) ? array_pop($dispositionTypeParents) : null;
    }

    if ($projectTypeId = $entity->get('field_bh_project_type')->target_id) {
      if ( empty($mainType) || $mainType->getName() == 'Housing') {
        $mainType = 'Housing';
      }
    }

    if ($mainType) {
      $mainType = is_string($mainType) ? $mainType : $mainType->getName();

//    $projectType = 'Housing';

    switch ($mainType) {
      case "Housing":
        $iconType = 'maplist-housing';
        break;
      case "Open Space":
        $iconType = 'maplist-open-space';
        break;
      case "Business":
        $iconType = 'maplist-business';
        break;
      default:
        $iconType = 'maplist-other';
        break;

    }

    return \Drupal::theme()->render("bh_icons", ['type' => $iconType]);

    }
  }

}
