<?php

namespace Drupal\public_notice;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\user\EntityOwnerInterface;
use Drupal\Core\Entity\EntityChangedInterface;

/**
 * Provides an interface defining a public notice entity type.
 */
interface PublicNoticeInterface extends ContentEntityInterface, EntityOwnerInterface, EntityChangedInterface {

  /**
   * Gets the public notice title.
   *
   * @return string
   *   Title of the public notice.
   */
  public function getTitle();

  /**
   * Sets the public notice title.
   *
   * @param string $title
   *   The public notice title.
   *
   * @return \Drupal\public_notice\PublicNoticeInterface
   *   The called public notice entity.
   */
  public function setTitle($title);

  /**
   * Gets the public notice creation timestamp.
   *
   * @return int
   *   Creation timestamp of the public notice.
   */
  public function getCreatedTime();

  /**
   * Sets the public notice creation timestamp.
   *
   * @param int $timestamp
   *   The public notice creation timestamp.
   *
   * @return \Drupal\public_notice\PublicNoticeInterface
   *   The called public notice entity.
   */
  public function setCreatedTime($timestamp);

  /**
   * Returns the public notice status.
   *
   * @return bool
   *   TRUE if the public notice is enabled, FALSE otherwise.
   */
  public function isEnabled();

  /**
   * Sets the public notice status.
   *
   * @param bool $status
   *   TRUE to enable this public notice, FALSE to disable.
   *
   * @return \Drupal\public_notice\PublicNoticeInterface
   *   The called public notice entity.
   */
  public function setStatus($status);

}
