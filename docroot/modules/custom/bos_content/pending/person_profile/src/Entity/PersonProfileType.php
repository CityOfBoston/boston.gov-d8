<?php

namespace Drupal\person_profile\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBundleBase;

/**
 * Defines the Person Profile type configuration entity.
 *
 * @ConfigEntityType(
 *   id = "person_profile_type",
 *   label = @Translation("Person Profile type"),
 *   handlers = {
 *     "form" = {
 *       "add" = "Drupal\person_profile\Form\PersonProfileTypeForm",
 *       "edit" = "Drupal\person_profile\Form\PersonProfileTypeForm",
 *       "delete" = "Drupal\Core\Entity\EntityDeleteForm",
 *     },
 *     "list_builder" = "Drupal\person_profile\PersonProfileTypeListBuilder",
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     }
 *   },
 *   admin_permission = "administer person profile types",
 *   bundle_of = "person_profile",
 *   config_prefix = "person_profile_type",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "uuid" = "uuid"
 *   },
 *   links = {
 *     "add-form" = "/admin/structure/person_profile_types/add",
 *     "edit-form" = "/admin/structure/person_profile_types/manage/{person_profile_type}",
 *     "delete-form" = "/admin/structure/person_profile_types/manage/{person_profile _type}/delete",
 *     "collection" = "/admin/structure/person_profile_types"
 *   },
 *   config_export = {
 *     "id",
 *     "label",
 *     "uuid",
 *   }
 * )
 */
class PersonProfileType extends ConfigEntityBundleBase {

  /**
   * The machine name of this person profile type.
   *
   * @var string
   */
  protected $id;

  /**
   * The human-readable name of the person profile type.
   *
   * @var string
   */
  protected $label;

}
