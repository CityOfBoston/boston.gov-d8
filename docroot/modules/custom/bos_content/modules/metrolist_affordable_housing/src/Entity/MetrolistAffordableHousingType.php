<?php

namespace Drupal\metrolist_affordable_housing\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBundleBase;

/**
 * Defines the Metrolist Affordable Housing type configuration entity.
 *
 * @ConfigEntityType(
 *   id = "metrolist_affordable_housing_type",
 *   label = @Translation("Metrolist Affordable Housing type"),
 *   handlers = {
 *     "form" = {
 *       "add" = "Drupal\metrolist_affordable_housing\Form\MetrolistAffordableHousingTypeForm",
 *       "edit" = "Drupal\metrolist_affordable_housing\Form\MetrolistAffordableHousingTypeForm",
 *       "delete" = "Drupal\Core\Entity\EntityDeleteForm",
 *     },
 *     "list_builder" = "Drupal\metrolist_affordable_housing\MetrolistAffordableHousingTypeListBuilder",
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     }
 *   },
 *   admin_permission = "administer metrolist affordable housing types",
 *   bundle_of = "metrolist_affordable_housing",
 *   config_prefix = "metrolist_affordable_housing_type",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "uuid" = "uuid"
 *   },
 *   links = {
 *     "add-form" = "/admin/structure/metrolist_affordable_housing_types/add",
 *     "edit-form" = "/admin/structure/metrolist_affordable_housing_types/manage/{metrolist_affordable_housing_type}",
 *     "delete-form" = "/admin/structure/metrolist_affordable_housing_types/manage/{metrolist_affordable_housing _type}/delete",
 *     "collection" = "/admin/structure/metrolist_affordable_housing_types"
 *   },
 *   config_export = {
 *     "id",
 *     "label",
 *     "uuid",
 *   }
 * )
 */
class MetrolistAffordableHousingType extends ConfigEntityBundleBase {

  /**
   * The machine name of this metrolist affordable housing type.
   *
   * @var string
   */
  protected $id;

  /**
   * The human-readable name of the metrolist affordable housing type.
   *
   * @var string
   */
  protected $label;

}
