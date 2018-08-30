<?php

namespace Drupal\site_alert;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\user\EntityOwnerInterface;
use Drupal\Core\Entity\EntityChangedInterface;

/**
 * Provides an interface defining a site alert entity type.
 */
interface SiteAlertInterface extends ContentEntityInterface, EntityOwnerInterface, EntityChangedInterface {

  /**
   * Gets the site alert title.
   *
   * @return string
   *   Title of the site alert.
   */
  public function getTitle();

  /**
   * Sets the site alert title.
   *
   * @param string $title
   *   The site alert title.
   *
   * @return \Drupal\site_alert\SiteAlertInterface
   *   The called site alert entity.
   */
  public function setTitle($title);

  /**
   * Gets the site alert creation timestamp.
   *
   * @return int
   *   Creation timestamp of the site alert.
   */
  public function getCreatedTime();

  /**
   * Sets the site alert creation timestamp.
   *
   * @param int $timestamp
   *   The site alert creation timestamp.
   *
   * @return \Drupal\site_alert\SiteAlertInterface
   *   The called site alert entity.
   */
  public function setCreatedTime($timestamp);

  /**
   * Returns the site alert status.
   *
   * @return bool
   *   TRUE if the site alert is enabled, FALSE otherwise.
   */
  public function isEnabled();

  /**
   * Sets the site alert status.
   *
   * @param bool $status
   *   TRUE to enable this site alert, FALSE to disable.
   *
   * @return \Drupal\site_alert\SiteAlertInterface
   *   The called site alert entity.
   */
  public function setStatus($status);

}
