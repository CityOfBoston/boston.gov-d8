<?php

namespace Drupal\article\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBundleBase;

/**
 * Defines the Article type configuration entity.
 *
 * @ConfigEntityType(
 *   id = "article_type",
 *   label = @Translation("Article type"),
 *   handlers = {
 *     "form" = {
 *       "add" = "Drupal\article\Form\ArticleTypeForm",
 *       "edit" = "Drupal\article\Form\ArticleTypeForm",
 *       "delete" = "Drupal\Core\Entity\EntityDeleteForm",
 *     },
 *     "list_builder" = "Drupal\article\ArticleTypeListBuilder",
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     }
 *   },
 *   admin_permission = "administer article types",
 *   bundle_of = "article",
 *   config_prefix = "article_type",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "uuid" = "uuid"
 *   },
 *   links = {
 *     "add-form" = "/admin/structure/article_types/add",
 *     "edit-form" = "/admin/structure/article_types/manage/{article_type}",
 *     "delete-form" = "/admin/structure/article_types/manage/{article _type}/delete",
 *     "collection" = "/admin/structure/article_types"
 *   },
 *   config_export = {
 *     "id",
 *     "label",
 *     "uuid",
 *   }
 * )
 */
class ArticleType extends ConfigEntityBundleBase {

  /**
   * The machine name of this article type.
   *
   * @var string
   */
  protected $id;

  /**
   * The human-readable name of the article type.
   *
   * @var string
   */
  protected $label;

}
