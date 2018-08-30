<?php

namespace Drupal\how_to\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBundleBase;

/**
 * Defines the How-To type configuration entity.
 *
 * @ConfigEntityType(
 *   id = "how_to_type",
 *   label = @Translation("How-To type"),
 *   handlers = {
 *     "form" = {
 *       "add" = "Drupal\how_to\Form\HowToTypeForm",
 *       "edit" = "Drupal\how_to\Form\HowToTypeForm",
 *       "delete" = "Drupal\Core\Entity\EntityDeleteForm",
 *     },
 *     "list_builder" = "Drupal\how_to\HowToTypeListBuilder",
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     }
 *   },
 *   admin_permission = "administer how-to types",
 *   bundle_of = "how_to",
 *   config_prefix = "how_to_type",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "uuid" = "uuid"
 *   },
 *   links = {
 *     "add-form" = "/admin/structure/how_to_types/add",
 *     "edit-form" = "/admin/structure/how_to_types/manage/{how_to_type}",
 *     "delete-form" = "/admin/structure/how_to_types/manage/{how_to _type}/delete",
 *     "collection" = "/admin/structure/how_to_types"
 *   },
 *   config_export = {
 *     "id",
 *     "label",
 *     "uuid",
 *   }
 * )
 */
class HowToType extends ConfigEntityBundleBase {

  /**
   * The machine name of this how-to type.
   *
   * @var string
   */
  protected $id;

  /**
   * The human-readable name of the how-to type.
   *
   * @var string
   */
  protected $label;

}
