<?php

namespace Drupal\procurement_advertisement\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBundleBase;

/**
 * Defines the Procurement Advertisement type configuration entity.
 *
 * @ConfigEntityType(
 *   id = "procurement_advertisement_type",
 *   label = @Translation("Procurement Advertisement type"),
 *   handlers = {
 *     "form" = {
 *       "add" = "Drupal\procurement_advertisement\Form\ProcurementAdvertisementTypeForm",
 *       "edit" = "Drupal\procurement_advertisement\Form\ProcurementAdvertisementTypeForm",
 *       "delete" = "Drupal\Core\Entity\EntityDeleteForm",
 *     },
 *     "list_builder" = "Drupal\procurement_advertisement\ProcurementAdvertisementTypeListBuilder",
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     }
 *   },
 *   admin_permission = "administer procurement advertisement types",
 *   bundle_of = "procurement_advertisement",
 *   config_prefix = "procurement_advertisement_type",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "uuid" = "uuid"
 *   },
 *   links = {
 *     "add-form" = "/admin/structure/procurement_advertisement_types/add",
 *     "edit-form" = "/admin/structure/procurement_advertisement_types/manage/{procurement_advertisement_type}",
 *     "delete-form" = "/admin/structure/procurement_advertisement_types/manage/{procurement_advertisement _type}/delete",
 *     "collection" = "/admin/structure/procurement_advertisement_types"
 *   },
 *   config_export = {
 *     "id",
 *     "label",
 *     "uuid",
 *   }
 * )
 */
class ProcurementAdvertisementType extends ConfigEntityBundleBase {

  /**
   * The machine name of this procurement advertisement type.
   *
   * @var string
   */
  protected $id;

  /**
   * The human-readable name of the procurement advertisement type.
   *
   * @var string
   */
  protected $label;

}
