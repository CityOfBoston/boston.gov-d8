<?php

namespace Drupal\emergency_alert\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBundleBase;

/**
 * Defines the Emergency Alert type configuration entity.
 *
 * @ConfigEntityType(
 *   id = "emergency_alert_type",
 *   label = @Translation("Emergency Alert type"),
 *   handlers = {
 *     "form" = {
 *       "add" = "Drupal\emergency_alert\Form\EmergencyAlertTypeForm",
 *       "edit" = "Drupal\emergency_alert\Form\EmergencyAlertTypeForm",
 *       "delete" = "Drupal\Core\Entity\EntityDeleteForm",
 *     },
 *     "list_builder" = "Drupal\emergency_alert\EmergencyAlertTypeListBuilder",
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     }
 *   },
 *   admin_permission = "administer emergency alert types",
 *   bundle_of = "emergency_alert",
 *   config_prefix = "emergency_alert_type",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "uuid" = "uuid"
 *   },
 *   links = {
 *     "add-form" = "/admin/structure/emergency_alert_types/add",
 *     "edit-form" = "/admin/structure/emergency_alert_types/manage/{emergency_alert_type}",
 *     "delete-form" = "/admin/structure/emergency_alert_types/manage/{emergency_alert _type}/delete",
 *     "collection" = "/admin/structure/emergency_alert_types"
 *   },
 *   config_export = {
 *     "id",
 *     "label",
 *     "uuid",
 *   }
 * )
 */
class EmergencyAlertType extends ConfigEntityBundleBase {

  /**
   * The machine name of this emergency alert type.
   *
   * @var string
   */
  protected $id;

  /**
   * The human-readable name of the emergency alert type.
   *
   * @var string
   */
  protected $label;

}
