<?php

namespace Drupal\procurement_advertisement;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\user\EntityOwnerInterface;
use Drupal\Core\Entity\EntityChangedInterface;

/**
 * Provides an interface defining a procurement advertisement entity type.
 */
interface ProcurementAdvertisementInterface extends ContentEntityInterface, EntityOwnerInterface, EntityChangedInterface {

  /**
   * Gets the procurement advertisement title.
   *
   * @return string
   *   Title of the procurement advertisement.
   */
  public function getTitle();

  /**
   * Sets the procurement advertisement title.
   *
   * @param string $title
   *   The procurement advertisement title.
   *
   * @return \Drupal\procurement_advertisement\ProcurementAdvertisementInterface
   *   The called procurement advertisement entity.
   */
  public function setTitle($title);

  /**
   * Gets the procurement advertisement creation timestamp.
   *
   * @return int
   *   Creation timestamp of the procurement advertisement.
   */
  public function getCreatedTime();

  /**
   * Sets the procurement advertisement creation timestamp.
   *
   * @param int $timestamp
   *   The procurement advertisement creation timestamp.
   *
   * @return \Drupal\procurement_advertisement\ProcurementAdvertisementInterface
   *   The called procurement advertisement entity.
   */
  public function setCreatedTime($timestamp);

  /**
   * Returns the procurement advertisement status.
   *
   * @return bool
   *   TRUE if the procurement advertisement is enabled, FALSE otherwise.
   */
  public function isEnabled();

  /**
   * Sets the procurement advertisement status.
   *
   * @param bool $status
   *   TRUE to enable this procurement advertisement, FALSE to disable.
   *
   * @return \Drupal\procurement_advertisement\ProcurementAdvertisementInterface
   *   The called procurement advertisement entity.
   */
  public function setStatus($status);

}
