<?php

namespace Drupal\status_item;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\user\EntityOwnerInterface;
use Drupal\Core\Entity\EntityChangedInterface;

/**
 * Provides an interface defining a status item entity type.
 */
interface StatusItemInterface extends ContentEntityInterface, EntityOwnerInterface, EntityChangedInterface {

  /**
   * Gets the status item title.
   *
   * @return string
   *   Title of the status item.
   */
  public function getTitle();

  /**
   * Sets the status item title.
   *
   * @param string $title
   *   The status item title.
   *
   * @return \Drupal\status_item\StatusItemInterface
   *   The called status item entity.
   */
  public function setTitle($title);

  /**
   * Gets the status item creation timestamp.
   *
   * @return int
   *   Creation timestamp of the status item.
   */
  public function getCreatedTime();

  /**
   * Sets the status item creation timestamp.
   *
   * @param int $timestamp
   *   The status item creation timestamp.
   *
   * @return \Drupal\status_item\StatusItemInterface
   *   The called status item entity.
   */
  public function setCreatedTime($timestamp);

  /**
   * Returns the status item status.
   *
   * @return bool
   *   TRUE if the status item is enabled, FALSE otherwise.
   */
  public function isEnabled();

  /**
   * Sets the status item status.
   *
   * @param bool $status
   *   TRUE to enable this status item, FALSE to disable.
   *
   * @return \Drupal\status_item\StatusItemInterface
   *   The called status item entity.
   */
  public function setStatus($status);

}
