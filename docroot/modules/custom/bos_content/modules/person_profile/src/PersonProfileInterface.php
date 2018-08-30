<?php

namespace Drupal\person_profile;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\user\EntityOwnerInterface;
use Drupal\Core\Entity\EntityChangedInterface;

/**
 * Provides an interface defining a person profile entity type.
 */
interface PersonProfileInterface extends ContentEntityInterface, EntityOwnerInterface, EntityChangedInterface {

  /**
   * Gets the person profile title.
   *
   * @return string
   *   Title of the person profile.
   */
  public function getTitle();

  /**
   * Sets the person profile title.
   *
   * @param string $title
   *   The person profile title.
   *
   * @return \Drupal\person_profile\PersonProfileInterface
   *   The called person profile entity.
   */
  public function setTitle($title);

  /**
   * Gets the person profile creation timestamp.
   *
   * @return int
   *   Creation timestamp of the person profile.
   */
  public function getCreatedTime();

  /**
   * Sets the person profile creation timestamp.
   *
   * @param int $timestamp
   *   The person profile creation timestamp.
   *
   * @return \Drupal\person_profile\PersonProfileInterface
   *   The called person profile entity.
   */
  public function setCreatedTime($timestamp);

  /**
   * Returns the person profile status.
   *
   * @return bool
   *   TRUE if the person profile is enabled, FALSE otherwise.
   */
  public function isEnabled();

  /**
   * Sets the person profile status.
   *
   * @param bool $status
   *   TRUE to enable this person profile, FALSE to disable.
   *
   * @return \Drupal\person_profile\PersonProfileInterface
   *   The called person profile entity.
   */
  public function setStatus($status);

}
