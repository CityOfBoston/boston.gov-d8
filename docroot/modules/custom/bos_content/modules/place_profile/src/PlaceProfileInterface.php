<?php

namespace Drupal\place_profile;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\user\EntityOwnerInterface;
use Drupal\Core\Entity\EntityChangedInterface;

/**
 * Provides an interface defining a place profile entity type.
 */
interface PlaceProfileInterface extends ContentEntityInterface, EntityOwnerInterface, EntityChangedInterface {

  /**
   * Gets the place profile title.
   *
   * @return string
   *   Title of the place profile.
   */
  public function getTitle();

  /**
   * Sets the place profile title.
   *
   * @param string $title
   *   The place profile title.
   *
   * @return \Drupal\place_profile\PlaceProfileInterface
   *   The called place profile entity.
   */
  public function setTitle($title);

  /**
   * Gets the place profile creation timestamp.
   *
   * @return int
   *   Creation timestamp of the place profile.
   */
  public function getCreatedTime();

  /**
   * Sets the place profile creation timestamp.
   *
   * @param int $timestamp
   *   The place profile creation timestamp.
   *
   * @return \Drupal\place_profile\PlaceProfileInterface
   *   The called place profile entity.
   */
  public function setCreatedTime($timestamp);

  /**
   * Returns the place profile status.
   *
   * @return bool
   *   TRUE if the place profile is enabled, FALSE otherwise.
   */
  public function isEnabled();

  /**
   * Sets the place profile status.
   *
   * @param bool $status
   *   TRUE to enable this place profile, FALSE to disable.
   *
   * @return \Drupal\place_profile\PlaceProfileInterface
   *   The called place profile entity.
   */
  public function setStatus($status);

}
