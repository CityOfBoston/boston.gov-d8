<?php

namespace Drupal\program_initiative_profile\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBundleBase;

/**
 * Defines the Program Initiative Profile type configuration entity.
 *
 * @ConfigEntityType(
 *   id = "program_initiative_profile_type",
 *   label = @Translation("Program Initiative Profile type"),
 *   handlers = {
 *     "form" = {
 *       "add" = "Drupal\program_initiative_profile\Form\ProgramInitiativeProfileTypeForm",
 *       "edit" = "Drupal\program_initiative_profile\Form\ProgramInitiativeProfileTypeForm",
 *       "delete" = "Drupal\Core\Entity\EntityDeleteForm",
 *     },
 *     "list_builder" = "Drupal\program_initiative_profile\ProgramInitiativeProfileTypeListBuilder",
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     }
 *   },
 *   admin_permission = "administer program initiative profile types",
 *   bundle_of = "program_initiative_profile",
 *   config_prefix = "program_initiative_profile_type",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "uuid" = "uuid"
 *   },
 *   links = {
 *     "add-form" = "/admin/structure/program_initiative_profile_types/add",
 *     "edit-form" = "/admin/structure/program_initiative_profile_types/manage/{program_initiative_profile_type}",
 *     "delete-form" = "/admin/structure/program_initiative_profile_types/manage/{program_initiative_profile _type}/delete",
 *     "collection" = "/admin/structure/program_initiative_profile_types"
 *   },
 *   config_export = {
 *     "id",
 *     "label",
 *     "uuid",
 *   }
 * )
 */
class ProgramInitiativeProfileType extends ConfigEntityBundleBase {

  /**
   * The machine name of this program initiative profile type.
   *
   * @var string
   */
  protected $id;

  /**
   * The human-readable name of the program initiative profile type.
   *
   * @var string
   */
  protected $label;

}
