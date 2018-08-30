<?php

namespace Drupal\tabbed_content\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBundleBase;

/**
 * Defines the Tabbed Content type configuration entity.
 *
 * @ConfigEntityType(
 *   id = "tabbed_content_type",
 *   label = @Translation("Tabbed Content type"),
 *   handlers = {
 *     "form" = {
 *       "add" = "Drupal\tabbed_content\Form\TabbedContentTypeForm",
 *       "edit" = "Drupal\tabbed_content\Form\TabbedContentTypeForm",
 *       "delete" = "Drupal\Core\Entity\EntityDeleteForm",
 *     },
 *     "list_builder" = "Drupal\tabbed_content\TabbedContentTypeListBuilder",
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     }
 *   },
 *   admin_permission = "administer tabbed content types",
 *   bundle_of = "tabbed_content",
 *   config_prefix = "tabbed_content_type",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "uuid" = "uuid"
 *   },
 *   links = {
 *     "add-form" = "/admin/structure/tabbed_content_types/add",
 *     "edit-form" = "/admin/structure/tabbed_content_types/manage/{tabbed_content_type}",
 *     "delete-form" = "/admin/structure/tabbed_content_types/manage/{tabbed_content _type}/delete",
 *     "collection" = "/admin/structure/tabbed_content_types"
 *   },
 *   config_export = {
 *     "id",
 *     "label",
 *     "uuid",
 *   }
 * )
 */
class TabbedContentType extends ConfigEntityBundleBase {

  /**
   * The machine name of this tabbed content type.
   *
   * @var string
   */
  protected $id;

  /**
   * The human-readable name of the tabbed content type.
   *
   * @var string
   */
  protected $label;

}
