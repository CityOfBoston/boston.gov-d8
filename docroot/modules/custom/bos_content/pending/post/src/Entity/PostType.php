<?php

namespace Drupal\post\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBundleBase;

/**
 * Defines the Post type configuration entity.
 *
 * @ConfigEntityType(
 *   id = "post_type",
 *   label = @Translation("Post type"),
 *   handlers = {
 *     "form" = {
 *       "add" = "Drupal\post\Form\PostTypeForm",
 *       "edit" = "Drupal\post\Form\PostTypeForm",
 *       "delete" = "Drupal\Core\Entity\EntityDeleteForm",
 *     },
 *     "list_builder" = "Drupal\post\PostTypeListBuilder",
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     }
 *   },
 *   admin_permission = "administer post types",
 *   bundle_of = "post",
 *   config_prefix = "post_type",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "uuid" = "uuid"
 *   },
 *   links = {
 *     "add-form" = "/admin/structure/post_types/add",
 *     "edit-form" = "/admin/structure/post_types/manage/{post_type}",
 *     "delete-form" = "/admin/structure/post_types/manage/{post _type}/delete",
 *     "collection" = "/admin/structure/post_types"
 *   },
 *   config_export = {
 *     "id",
 *     "label",
 *     "uuid",
 *   }
 * )
 */
class PostType extends ConfigEntityBundleBase {

  /**
   * The machine name of this post type.
   *
   * @var string
   */
  protected $id;

  /**
   * The human-readable name of the post type.
   *
   * @var string
   */
  protected $label;

}
