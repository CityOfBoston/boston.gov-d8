<?php

namespace Drupal\change;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\user\EntityOwnerInterface;
use Drupal\Core\Entity\EntityChangedInterface;

/**
 * Provides an interface defining a change entity type.
 */
interface ChangeInterface extends ContentEntityInterface, EntityOwnerInterface, EntityChangedInterface {

  /**
   * Gets the change title.
   *
   * @return string
   *   Title of the change.
   */
  public function getTitle();

  /**
   * Sets the change title.
   *
   * @param string $title
   *   The change title.
   *
   * @return \Drupal\change\ChangeInterface
   *   The called change entity.
   */
  public function setTitle($title);

  /**
   * Gets the change creation timestamp.
   *
   * @return int
   *   Creation timestamp of the change.
   */
  public function getCreatedTime();

  /**
   * Sets the change creation timestamp.
   *
   * @param int $timestamp
   *   The change creation timestamp.
   *
   * @return \Drupal\change\ChangeInterface
   *   The called change entity.
   */
  public function setCreatedTime($timestamp);

  /**
   * Returns the change status.
   *
   * @return bool
   *   TRUE if the change is enabled, FALSE otherwise.
   */
  public function isEnabled();

  /**
   * Sets the change status.
   *
   * @param bool $status
   *   TRUE to enable this change, FALSE to disable.
   *
   * @return \Drupal\change\ChangeInterface
   *   The called change entity.
   */
  public function setStatus($status);

}
