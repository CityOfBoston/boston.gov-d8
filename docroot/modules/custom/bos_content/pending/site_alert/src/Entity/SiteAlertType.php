<?php

namespace Drupal\site_alert\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBundleBase;

/**
 * Defines the Site Alert type configuration entity.
 *
 * @ConfigEntityType(
 *   id = "site_alert_type",
 *   label = @Translation("Site Alert type"),
 *   handlers = {
 *     "form" = {
 *       "add" = "Drupal\site_alert\Form\SiteAlertTypeForm",
 *       "edit" = "Drupal\site_alert\Form\SiteAlertTypeForm",
 *       "delete" = "Drupal\Core\Entity\EntityDeleteForm",
 *     },
 *     "list_builder" = "Drupal\site_alert\SiteAlertTypeListBuilder",
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     }
 *   },
 *   admin_permission = "administer site alert types",
 *   bundle_of = "site_alert",
 *   config_prefix = "site_alert_type",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "uuid" = "uuid"
 *   },
 *   links = {
 *     "add-form" = "/admin/structure/site_alert_types/add",
 *     "edit-form" = "/admin/structure/site_alert_types/manage/{site_alert_type}",
 *     "delete-form" = "/admin/structure/site_alert_types/manage/{site_alert _type}/delete",
 *     "collection" = "/admin/structure/site_alert_types"
 *   },
 *   config_export = {
 *     "id",
 *     "label",
 *     "uuid",
 *   }
 * )
 */
class SiteAlertType extends ConfigEntityBundleBase {

  /**
   * The machine name of this site alert type.
   *
   * @var string
   */
  protected $id;

  /**
   * The human-readable name of the site alert type.
   *
   * @var string
   */
  protected $label;

}
