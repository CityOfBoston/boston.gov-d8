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
 * @ViewsField("building_housing_project_type_views_field_with_text")
 */
class BuildingHousingProjectTypeViewsFieldWithText extends FieldPluginBase {

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
   *
   */
  public function getMainProjectTypeName($projectEntity) {
    $mainType = NULL;

    $termStorage = \Drupal::entityTypeManager()->getStorage('taxonomy_term');

    if ($dispositionTypeId = $projectEntity->get('field_bh_disposition_type')->target_id) {
      $dispositionTypeParents = $termStorage->loadAllParents($dispositionTypeId);
      $mainType = !empty($dispositionTypeParents) ? array_pop($dispositionTypeParents) : NULL;
    }

    if ($projectTypeId = $projectEntity->get('field_bh_project_type')->target_id) {
      if (empty($mainType) || $mainType->getName() == 'Housing') {
        $mainType = 'Housing';
      }
    }

    if ($mainType) {
      return is_string($mainType) ? $mainType : $mainType->getName();
    }

    return $mainType;
  }

  /**
   * {@inheritdoc}
   */
  public function render(ResultRow $values) {
    $mainType = $this->getMainProjectTypeName($values->_entity);

    if ($mainType) {

      switch ($mainType) {
        case "Housing":
          $iconType = 'maplist-housing';
          $pillColor = 'charles-blue';
          $pillText = t('Housing');
          break;

        case "Open Space":
          $iconType = 'maplist-open-space';
          $pillColor = 'green';
          $pillText = t('Open Space');
          break;

        case "Business":
          $iconType = 'maplist-business';
          $pillColor = 'gray-blue';
          $pillText = t('Business');
          break;

        case "Abutter Sale":
          $iconType = 'maplist-sale';
          // $pillColor = 'medium-gray';
          $pillColor = 'dark-gray';
          $pillText = t('DND Owned Land');
          break;

        default:
          $iconType = 'maplist-other';
          $pillColor = 'dark-gray';
          $pillText = t('To Be Decided');
          break;
      }

      $elements = [];

      $elements['projectType'] = [
        '#type' => 'container',
        '#attributes' => [
          'class' => ['bh-project-type-pill', $pillColor]
        ],
      ];

      $elements['projectType']['icon']['#markup'] = \Drupal::theme()->render("bh_icons", ['type' => $iconType]);

      $elements['projectType']['text'] = [
        '#type' => 'html_tag',
        '#tag' => 'p',
        '#value' => $pillText,
      ];

      return $elements;
    }
  }

}
