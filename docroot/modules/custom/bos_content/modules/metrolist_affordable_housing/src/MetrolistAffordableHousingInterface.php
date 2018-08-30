<?php

namespace Drupal\metrolist_affordable_housing;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\user\EntityOwnerInterface;
use Drupal\Core\Entity\EntityChangedInterface;

/**
 * Provides an interface defining a metrolist affordable housing entity type.
 */
interface MetrolistAffordableHousingInterface extends ContentEntityInterface, EntityOwnerInterface, EntityChangedInterface {

  /**
   * Gets the metrolist affordable housing title.
   *
   * @return string
   *   Title of the metrolist affordable housing.
   */
  public function getTitle();

  /**
   * Sets the metrolist affordable housing title.
   *
   * @param string $title
   *   The metrolist affordable housing title.
   *
   * @return \Drupal\metrolist_affordable_housing\MetrolistAffordableHousingInterface
   *   The called metrolist affordable housing entity.
   */
  public function setTitle($title);

  /**
   * Gets the metrolist affordable housing creation timestamp.
   *
   * @return int
   *   Creation timestamp of the metrolist affordable housing.
   */
  public function getCreatedTime();

  /**
   * Sets the metrolist affordable housing creation timestamp.
   *
   * @param int $timestamp
   *   The metrolist affordable housing creation timestamp.
   *
   * @return \Drupal\metrolist_affordable_housing\MetrolistAffordableHousingInterface
   *   The called metrolist affordable housing entity.
   */
  public function setCreatedTime($timestamp);

  /**
   * Returns the metrolist affordable housing status.
   *
   * @return bool
   *   TRUE if the metrolist affordable housing is enabled, FALSE otherwise.
   */
  public function isEnabled();

  /**
   * Sets the metrolist affordable housing status.
   *
   * @param bool $status
   *   TRUE to enable this metrolist affordable housing, FALSE to disable.
   *
   * @return \Drupal\metrolist_affordable_housing\MetrolistAffordableHousingInterface
   *   The called metrolist affordable housing entity.
   */
  public function setStatus($status);

}
