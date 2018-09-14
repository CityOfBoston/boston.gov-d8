<?php

namespace Drupal\listing_page\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBundleBase;

/**
 * Defines the Listing page type configuration entity.
 *
 * @ConfigEntityType(
 *   id = "listing_page_type",
 *   label = @Translation("Listing page type"),
 *   handlers = {
 *     "form" = {
 *       "add" = "Drupal\listing_page\Form\ListingPageTypeForm",
 *       "edit" = "Drupal\listing_page\Form\ListingPageTypeForm",
 *       "delete" = "Drupal\Core\Entity\EntityDeleteForm",
 *     },
 *     "list_builder" = "Drupal\listing_page\ListingPageTypeListBuilder",
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     }
 *   },
 *   admin_permission = "administer listing page types",
 *   bundle_of = "listing_page",
 *   config_prefix = "listing_page_type",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "uuid" = "uuid"
 *   },
 *   links = {
 *     "add-form" = "/admin/structure/listing_page_types/add",
 *     "edit-form" = "/admin/structure/listing_page_types/manage/{listing_page_type}",
 *     "delete-form" = "/admin/structure/listing_page_types/manage/{listing_page _type}/delete",
 *     "collection" = "/admin/structure/listing_page_types"
 *   },
 *   config_export = {
 *     "id",
 *     "label",
 *     "uuid",
 *   }
 * )
 */
class ListingPageType extends ConfigEntityBundleBase {

  /**
   * The machine name of this listing page type.
   *
   * @var string
   */
  protected $id;

  /**
   * The human-readable name of the listing page type.
   *
   * @var string
   */
  protected $label;

}
