<?php

namespace Drupal\landing_page;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\user\EntityOwnerInterface;
use Drupal\Core\Entity\EntityChangedInterface;

/**
 * Provides an interface defining a landing page entity type.
 */
interface LandingPageInterface extends ContentEntityInterface, EntityOwnerInterface, EntityChangedInterface {

  /**
   * Gets the landing page title.
   *
   * @return string
   *   Title of the landing page.
   */
  public function getTitle();

  /**
   * Sets the landing page title.
   *
   * @param string $title
   *   The landing page title.
   *
   * @return \Drupal\landing_page\LandingPageInterface
   *   The called landing page entity.
   */
  public function setTitle($title);

  /**
   * Gets the landing page creation timestamp.
   *
   * @return int
   *   Creation timestamp of the landing page.
   */
  public function getCreatedTime();

  /**
   * Sets the landing page creation timestamp.
   *
   * @param int $timestamp
   *   The landing page creation timestamp.
   *
   * @return \Drupal\landing_page\LandingPageInterface
   *   The called landing page entity.
   */
  public function setCreatedTime($timestamp);

  /**
   * Returns the landing page status.
   *
   * @return bool
   *   TRUE if the landing page is enabled, FALSE otherwise.
   */
  public function isEnabled();

  /**
   * Sets the landing page status.
   *
   * @param bool $status
   *   TRUE to enable this landing page, FALSE to disable.
   *
   * @return \Drupal\landing_page\LandingPageInterface
   *   The called landing page entity.
   */
  public function setStatus($status);

}
