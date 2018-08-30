<?php

namespace Drupal\place_profile\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBundleBase;

/**
 * Defines the Place Profile type configuration entity.
 *
 * @ConfigEntityType(
 *   id = "place_profile_type",
 *   label = @Translation("Place Profile type"),
 *   handlers = {
 *     "form" = {
 *       "add" = "Drupal\place_profile\Form\PlaceProfileTypeForm",
 *       "edit" = "Drupal\place_profile\Form\PlaceProfileTypeForm",
 *       "delete" = "Drupal\Core\Entity\EntityDeleteForm",
 *     },
 *     "list_builder" = "Drupal\place_profile\PlaceProfileTypeListBuilder",
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     }
 *   },
 *   admin_permission = "administer place profile types",
 *   bundle_of = "place_profile",
 *   config_prefix = "place_profile_type",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "uuid" = "uuid"
 *   },
 *   links = {
 *     "add-form" = "/admin/structure/place_profile_types/add",
 *     "edit-form" = "/admin/structure/place_profile_types/manage/{place_profile_type}",
 *     "delete-form" = "/admin/structure/place_profile_types/manage/{place_profile _type}/delete",
 *     "collection" = "/admin/structure/place_profile_types"
 *   },
 *   config_export = {
 *     "id",
 *     "label",
 *     "uuid",
 *   }
 * )
 */
class PlaceProfileType extends ConfigEntityBundleBase {

  /**
   * The machine name of this place profile type.
   *
   * @var string
   */
  protected $id;

  /**
   * The human-readable name of the place profile type.
   *
   * @var string
   */
  protected $label;

}
