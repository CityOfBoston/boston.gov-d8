<?php

namespace Drupal\how_to;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\user\EntityOwnerInterface;
use Drupal\Core\Entity\EntityChangedInterface;

/**
 * Provides an interface defining a how-to entity type.
 */
interface HowToInterface extends ContentEntityInterface, EntityOwnerInterface, EntityChangedInterface {

  /**
   * Gets the how-to title.
   *
   * @return string
   *   Title of the how-to.
   */
  public function getTitle();

  /**
   * Sets the how-to title.
   *
   * @param string $title
   *   The how-to title.
   *
   * @return \Drupal\how_to\HowToInterface
   *   The called how-to entity.
   */
  public function setTitle($title);

  /**
   * Gets the how-to creation timestamp.
   *
   * @return int
   *   Creation timestamp of the how-to.
   */
  public function getCreatedTime();

  /**
   * Sets the how-to creation timestamp.
   *
   * @param int $timestamp
   *   The how-to creation timestamp.
   *
   * @return \Drupal\how_to\HowToInterface
   *   The called how-to entity.
   */
  public function setCreatedTime($timestamp);

  /**
   * Returns the how-to status.
   *
   * @return bool
   *   TRUE if the how-to is enabled, FALSE otherwise.
   */
  public function isEnabled();

  /**
   * Sets the how-to status.
   *
   * @param bool $status
   *   TRUE to enable this how-to, FALSE to disable.
   *
   * @return \Drupal\how_to\HowToInterface
   *   The called how-to entity.
   */
  public function setStatus($status);

}
