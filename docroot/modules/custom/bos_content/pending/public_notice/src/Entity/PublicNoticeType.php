<?php

namespace Drupal\public_notice\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBundleBase;

/**
 * Defines the Public Notice type configuration entity.
 *
 * @ConfigEntityType(
 *   id = "public_notice_type",
 *   label = @Translation("Public Notice type"),
 *   handlers = {
 *     "form" = {
 *       "add" = "Drupal\public_notice\Form\PublicNoticeTypeForm",
 *       "edit" = "Drupal\public_notice\Form\PublicNoticeTypeForm",
 *       "delete" = "Drupal\Core\Entity\EntityDeleteForm",
 *     },
 *     "list_builder" = "Drupal\public_notice\PublicNoticeTypeListBuilder",
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     }
 *   },
 *   admin_permission = "administer public notice types",
 *   bundle_of = "public_notice",
 *   config_prefix = "public_notice_type",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "uuid" = "uuid"
 *   },
 *   links = {
 *     "add-form" = "/admin/structure/public_notice_types/add",
 *     "edit-form" = "/admin/structure/public_notice_types/manage/{public_notice_type}",
 *     "delete-form" = "/admin/structure/public_notice_types/manage/{public_notice _type}/delete",
 *     "collection" = "/admin/structure/public_notice_types"
 *   },
 *   config_export = {
 *     "id",
 *     "label",
 *     "uuid",
 *   }
 * )
 */
class PublicNoticeType extends ConfigEntityBundleBase {

  /**
   * The machine name of this public notice type.
   *
   * @var string
   */
  protected $id;

  /**
   * The human-readable name of the public notice type.
   *
   * @var string
   */
  protected $label;

}
