<?php

namespace Drupal\program_initiative_profile;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\user\EntityOwnerInterface;
use Drupal\Core\Entity\EntityChangedInterface;

/**
 * Provides an interface defining a program initiative profile entity type.
 */
interface ProgramInitiativeProfileInterface extends ContentEntityInterface, EntityOwnerInterface, EntityChangedInterface {

  /**
   * Gets the program initiative profile title.
   *
   * @return string
   *   Title of the program initiative profile.
   */
  public function getTitle();

  /**
   * Sets the program initiative profile title.
   *
   * @param string $title
   *   The program initiative profile title.
   *
   * @return \Drupal\program_initiative_profile\ProgramInitiativeProfileInterface
   *   The called program initiative profile entity.
   */
  public function setTitle($title);

  /**
   * Gets the program initiative profile creation timestamp.
   *
   * @return int
   *   Creation timestamp of the program initiative profile.
   */
  public function getCreatedTime();

  /**
   * Sets the program initiative profile creation timestamp.
   *
   * @param int $timestamp
   *   The program initiative profile creation timestamp.
   *
   * @return \Drupal\program_initiative_profile\ProgramInitiativeProfileInterface
   *   The called program initiative profile entity.
   */
  public function setCreatedTime($timestamp);

  /**
   * Returns the program initiative profile status.
   *
   * @return bool
   *   TRUE if the program initiative profile is enabled, FALSE otherwise.
   */
  public function isEnabled();

  /**
   * Sets the program initiative profile status.
   *
   * @param bool $status
   *   TRUE to enable this program initiative profile, FALSE to disable.
   *
   * @return \Drupal\program_initiative_profile\ProgramInitiativeProfileInterface
   *   The called program initiative profile entity.
   */
  public function setStatus($status);

}
