<?php

namespace Drupal\topic_page\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBundleBase;

/**
 * Defines the Guide type configuration entity.
 *
 * @ConfigEntityType(
 *   id = "guide_type",
 *   label = @Translation("Guide type"),
 *   handlers = {
 *     "form" = {
 *       "add" = "Drupal\topic_page\Form\GuideTypeForm",
 *       "edit" = "Drupal\topic_page\Form\GuideTypeForm",
 *       "delete" = "Drupal\Core\Entity\EntityDeleteForm",
 *     },
 *     "list_builder" = "Drupal\topic_page\GuideTypeListBuilder",
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     }
 *   },
 *   admin_permission = "administer guide types",
 *   bundle_of = "guide",
 *   config_prefix = "guide_type",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "uuid" = "uuid"
 *   },
 *   links = {
 *     "add-form" = "/admin/structure/guide_types/add",
 *     "edit-form" = "/admin/structure/guide_types/manage/{guide_type}",
 *     "delete-form" = "/admin/structure/guide_types/manage/{guide _type}/delete",
 *     "collection" = "/admin/structure/guide_types"
 *   },
 *   config_export = {
 *     "id",
 *     "label",
 *     "uuid",
 *   }
 * )
 */
class GuideType extends ConfigEntityBundleBase {

  /**
   * The machine name of this guide type.
   *
   * @var string
   */
  protected $id;

  /**
   * The human-readable name of the guide type.
   *
   * @var string
   */
  protected $label;

}
