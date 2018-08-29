<?php

namespace Drupal\event\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBundleBase;

/**
 * Defines the Event type configuration entity.
 *
 * @ConfigEntityType(
 *   id = "event_type",
 *   label = @Translation("Event type"),
 *   handlers = {
 *     "form" = {
 *       "add" = "Drupal\event\Form\EventTypeForm",
 *       "edit" = "Drupal\event\Form\EventTypeForm",
 *       "delete" = "Drupal\Core\Entity\EntityDeleteForm",
 *     },
 *     "list_builder" = "Drupal\event\EventTypeListBuilder",
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     }
 *   },
 *   admin_permission = "administer event types",
 *   bundle_of = "event",
 *   config_prefix = "event_type",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "uuid" = "uuid"
 *   },
 *   links = {
 *     "add-form" = "/admin/structure/event_types/add",
 *     "edit-form" = "/admin/structure/event_types/manage/{event_type}",
 *     "delete-form" = "/admin/structure/event_types/manage/{event _type}/delete",
 *     "collection" = "/admin/structure/event_types"
 *   },
 *   config_export = {
 *     "id",
 *     "label",
 *     "uuid",
 *   }
 * )
 */
class EventType extends ConfigEntityBundleBase {

  /**
   * The machine name of this event type.
   *
   * @var string
   */
  protected $id;

  /**
   * The human-readable name of the event type.
   *
   * @var string
   */
  protected $label;

}
