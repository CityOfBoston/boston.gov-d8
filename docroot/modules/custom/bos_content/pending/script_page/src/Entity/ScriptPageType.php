<?php

namespace Drupal\script_page\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBundleBase;

/**
 * Defines the Script Page type configuration entity.
 *
 * @ConfigEntityType(
 *   id = "script_page_type",
 *   label = @Translation("Script Page type"),
 *   handlers = {
 *     "form" = {
 *       "add" = "Drupal\script_page\Form\ScriptPageTypeForm",
 *       "edit" = "Drupal\script_page\Form\ScriptPageTypeForm",
 *       "delete" = "Drupal\Core\Entity\EntityDeleteForm",
 *     },
 *     "list_builder" = "Drupal\script_page\ScriptPageTypeListBuilder",
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     }
 *   },
 *   admin_permission = "administer script page types",
 *   bundle_of = "script_page",
 *   config_prefix = "script_page_type",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "uuid" = "uuid"
 *   },
 *   links = {
 *     "add-form" = "/admin/structure/script_page_types/add",
 *     "edit-form" = "/admin/structure/script_page_types/manage/{script_page_type}",
 *     "delete-form" = "/admin/structure/script_page_types/manage/{script_page _type}/delete",
 *     "collection" = "/admin/structure/script_page_types"
 *   },
 *   config_export = {
 *     "id",
 *     "label",
 *     "uuid",
 *   }
 * )
 */
class ScriptPageType extends ConfigEntityBundleBase {

  /**
   * The machine name of this script page type.
   *
   * @var string
   */
  protected $id;

  /**
   * The human-readable name of the script page type.
   *
   * @var string
   */
  protected $label;

}
