<?php

namespace Drupal\emergency_alert;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\user\EntityOwnerInterface;
use Drupal\Core\Entity\EntityChangedInterface;

/**
 * Provides an interface defining an emergency alert entity type.
 */
interface EmergencyAlertInterface extends ContentEntityInterface, EntityOwnerInterface, EntityChangedInterface {

  /**
   * Gets the emergency alert title.
   *
   * @return string
   *   Title of the emergency alert.
   */
  public function getTitle();

  /**
   * Sets the emergency alert title.
   *
   * @param string $title
   *   The emergency alert title.
   *
   * @return \Drupal\emergency_alert\EmergencyAlertInterface
   *   The called emergency alert entity.
   */
  public function setTitle($title);

  /**
   * Gets the emergency alert creation timestamp.
   *
   * @return int
   *   Creation timestamp of the emergency alert.
   */
  public function getCreatedTime();

  /**
   * Sets the emergency alert creation timestamp.
   *
   * @param int $timestamp
   *   The emergency alert creation timestamp.
   *
   * @return \Drupal\emergency_alert\EmergencyAlertInterface
   *   The called emergency alert entity.
   */
  public function setCreatedTime($timestamp);

  /**
   * Returns the emergency alert status.
   *
   * @return bool
   *   TRUE if the emergency alert is enabled, FALSE otherwise.
   */
  public function isEnabled();

  /**
   * Sets the emergency alert status.
   *
   * @param bool $status
   *   TRUE to enable this emergency alert, FALSE to disable.
   *
   * @return \Drupal\emergency_alert\EmergencyAlertInterface
   *   The called emergency alert entity.
   */
  public function setStatus($status);

}
