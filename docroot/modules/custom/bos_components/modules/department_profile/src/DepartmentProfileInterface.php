<?php

namespace Drupal\department_profile;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\user\EntityOwnerInterface;
use Drupal\Core\Entity\EntityChangedInterface;

/**
 * Provides an interface defining a department profile entity type.
 */
interface DepartmentProfileInterface extends ContentEntityInterface, EntityOwnerInterface, EntityChangedInterface {

  /**
   * Gets the department profile title.
   *
   * @return string
   *   Title of the department profile.
   */
  public function getTitle();

  /**
   * Sets the department profile title.
   *
   * @param string $title
   *   The department profile title.
   *
   * @return \Drupal\department_profile\DepartmentProfileInterface
   *   The called department profile entity.
   */
  public function setTitle($title);

  /**
   * Gets the department profile creation timestamp.
   *
   * @return int
   *   Creation timestamp of the department profile.
   */
  public function getCreatedTime();

  /**
   * Sets the department profile creation timestamp.
   *
   * @param int $timestamp
   *   The department profile creation timestamp.
   *
   * @return \Drupal\department_profile\DepartmentProfileInterface
   *   The called department profile entity.
   */
  public function setCreatedTime($timestamp);

  /**
   * Returns the department profile status.
   *
   * @return bool
   *   TRUE if the department profile is enabled, FALSE otherwise.
   */
  public function isEnabled();

  /**
   * Sets the department profile status.
   *
   * @param bool $status
   *   TRUE to enable this department profile, FALSE to disable.
   *
   * @return \Drupal\department_profile\DepartmentProfileInterface
   *   The called department profile entity.
   */
  public function setStatus($status);

}
