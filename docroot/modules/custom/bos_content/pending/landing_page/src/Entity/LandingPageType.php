<?php

namespace Drupal\landing_page\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBundleBase;

/**
 * Defines the Landing Page type configuration entity.
 *
 * @ConfigEntityType(
 *   id = "landing_page_type",
 *   label = @Translation("Landing Page type"),
 *   handlers = {
 *     "form" = {
 *       "add" = "Drupal\landing_page\Form\LandingPageTypeForm",
 *       "edit" = "Drupal\landing_page\Form\LandingPageTypeForm",
 *       "delete" = "Drupal\Core\Entity\EntityDeleteForm",
 *     },
 *     "list_builder" = "Drupal\landing_page\LandingPageTypeListBuilder",
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     }
 *   },
 *   admin_permission = "administer landing page types",
 *   bundle_of = "landing_page",
 *   config_prefix = "landing_page_type",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "uuid" = "uuid"
 *   },
 *   links = {
 *     "add-form" = "/admin/structure/landing_page_types/add",
 *     "edit-form" = "/admin/structure/landing_page_types/manage/{landing_page_type}",
 *     "delete-form" = "/admin/structure/landing_page_types/manage/{landing_page _type}/delete",
 *     "collection" = "/admin/structure/landing_page_types"
 *   },
 *   config_export = {
 *     "id",
 *     "label",
 *     "uuid",
 *   }
 * )
 */
class LandingPageType extends ConfigEntityBundleBase {

  /**
   * The machine name of this landing page type.
   *
   * @var string
   */
  protected $id;

  /**
   * The human-readable name of the landing page type.
   *
   * @var string
   */
  protected $label;

}
