<?php

namespace Drupal\department_profile\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBundleBase;

/**
 * Defines the Department Profile type configuration entity.
 *
 * @ConfigEntityType(
 *   id = "department_profile_type",
 *   label = @Translation("Department Profile type"),
 *   handlers = {
 *     "form" = {
 *       "add" = "Drupal\department_profile\Form\DepartmentProfileTypeForm",
 *       "edit" = "Drupal\department_profile\Form\DepartmentProfileTypeForm",
 *       "delete" = "Drupal\Core\Entity\EntityDeleteForm",
 *     },
 *     "list_builder" = "Drupal\department_profile\DepartmentProfileTypeListBuilder",
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     }
 *   },
 *   admin_permission = "administer department profile types",
 *   bundle_of = "department_profile",
 *   config_prefix = "department_profile_type",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "uuid" = "uuid"
 *   },
 *   links = {
 *     "add-form" = "/admin/structure/department_profile_types/add",
 *     "edit-form" = "/admin/structure/department_profile_types/manage/{department_profile_type}",
 *     "delete-form" = "/admin/structure/department_profile_types/manage/{department_profile _type}/delete",
 *     "collection" = "/admin/structure/department_profile_types"
 *   },
 *   config_export = {
 *     "id",
 *     "label",
 *     "uuid",
 *   }
 * )
 */
class DepartmentProfileType extends ConfigEntityBundleBase {

  /**
   * The machine name of this department profile type.
   *
   * @var string
   */
  protected $id;

  /**
   * The human-readable name of the department profile type.
   *
   * @var string
   */
  protected $label;

}
