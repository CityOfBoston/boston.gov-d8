<?php

namespace Drupal\topic_page;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\user\EntityOwnerInterface;
use Drupal\Core\Entity\EntityChangedInterface;

/**
 * Provides an interface defining a guide entity type.
 */
interface GuideInterface extends ContentEntityInterface, EntityOwnerInterface, EntityChangedInterface {

  /**
   * Gets the guide title.
   *
   * @return string
   *   Title of the guide.
   */
  public function getTitle();

  /**
   * Sets the guide title.
   *
   * @param string $title
   *   The guide title.
   *
   * @return \Drupal\topic_page\GuideInterface
   *   The called guide entity.
   */
  public function setTitle($title);

  /**
   * Gets the guide creation timestamp.
   *
   * @return int
   *   Creation timestamp of the guide.
   */
  public function getCreatedTime();

  /**
   * Sets the guide creation timestamp.
   *
   * @param int $timestamp
   *   The guide creation timestamp.
   *
   * @return \Drupal\topic_page\GuideInterface
   *   The called guide entity.
   */
  public function setCreatedTime($timestamp);

  /**
   * Returns the guide status.
   *
   * @return bool
   *   TRUE if the guide is enabled, FALSE otherwise.
   */
  public function isEnabled();

  /**
   * Sets the guide status.
   *
   * @param bool $status
   *   TRUE to enable this guide, FALSE to disable.
   *
   * @return \Drupal\topic_page\GuideInterface
   *   The called guide entity.
   */
  public function setStatus($status);

}
