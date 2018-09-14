<?php

namespace Drupal\status_item\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBundleBase;

/**
 * Defines the Status Item type configuration entity.
 *
 * @ConfigEntityType(
 *   id = "status_item_type",
 *   label = @Translation("Status Item type"),
 *   handlers = {
 *     "form" = {
 *       "add" = "Drupal\status_item\Form\StatusItemTypeForm",
 *       "edit" = "Drupal\status_item\Form\StatusItemTypeForm",
 *       "delete" = "Drupal\Core\Entity\EntityDeleteForm",
 *     },
 *     "list_builder" = "Drupal\status_item\StatusItemTypeListBuilder",
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     }
 *   },
 *   admin_permission = "administer status item types",
 *   bundle_of = "status_item",
 *   config_prefix = "status_item_type",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "uuid" = "uuid"
 *   },
 *   links = {
 *     "add-form" = "/admin/structure/status_item_types/add",
 *     "edit-form" = "/admin/structure/status_item_types/manage/{status_item_type}",
 *     "delete-form" = "/admin/structure/status_item_types/manage/{status_item _type}/delete",
 *     "collection" = "/admin/structure/status_item_types"
 *   },
 *   config_export = {
 *     "id",
 *     "label",
 *     "uuid",
 *   }
 * )
 */
class StatusItemType extends ConfigEntityBundleBase {

  /**
   * The machine name of this status item type.
   *
   * @var string
   */
  protected $id;

  /**
   * The human-readable name of the status item type.
   *
   * @var string
   */
  protected $label;

}
