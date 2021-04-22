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
 * @ViewsField("building_housing_project_type_marker_views_field")
 */
class BuildingHousingProjectTypeMarkerViewsField extends BuildingHousingProjectTypeViewsField {

  /**
   * {@inheritdoc}
   */
  public function render(ResultRow $values) {

    $mainType = $this->getMainProjectTypeName($values->_entity);

    if ($mainType) {

      switch ($mainType) {
        case "Housing":
          $projectName = 'housing-marker.svg';
          break;

        case "Open Space":
          $projectName = 'open-space-marker.svg';
          break;

        case "Business":
          $projectName = 'business-marker.svg';
          break;

        case "Abutter Sale":
        case "For Sale":
          $projectName = 'sale-marker.svg';
          break;

        default:
          $projectName = 'other-marker.svg';
          break;

      }

      return $projectName;
    }
  }

}
