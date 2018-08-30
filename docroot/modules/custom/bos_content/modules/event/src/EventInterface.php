<?php

namespace Drupal\event;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\user\EntityOwnerInterface;
use Drupal\Core\Entity\EntityChangedInterface;

/**
 * Provides an interface defining an event entity type.
 */
interface EventInterface extends ContentEntityInterface, EntityOwnerInterface, EntityChangedInterface {

  /**
   * Gets the event title.
   *
   * @return string
   *   Title of the event.
   */
  public function getTitle();

  /**
   * Sets the event title.
   *
   * @param string $title
   *   The event title.
   *
   * @return \Drupal\event\EventInterface
   *   The called event entity.
   */
  public function setTitle($title);

  /**
   * Gets the event creation timestamp.
   *
   * @return int
   *   Creation timestamp of the event.
   */
  public function getCreatedTime();

  /**
   * Sets the event creation timestamp.
   *
   * @param int $timestamp
   *   The event creation timestamp.
   *
   * @return \Drupal\event\EventInterface
   *   The called event entity.
   */
  public function setCreatedTime($timestamp);

  /**
   * Returns the event status.
   *
   * @return bool
   *   TRUE if the event is enabled, FALSE otherwise.
   */
  public function isEnabled();

  /**
   * Sets the event status.
   *
   * @param bool $status
   *   TRUE to enable this event, FALSE to disable.
   *
   * @return \Drupal\event\EventInterface
   *   The called event entity.
   */
  public function setStatus($status);

}
