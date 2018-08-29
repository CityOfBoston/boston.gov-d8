<?php

namespace Drupal\advpoll;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\user\EntityOwnerInterface;
use Drupal\Core\Entity\EntityChangedInterface;

/**
 * Provides an interface defining an advanced poll entity type.
 */
interface AdvancedPollInterface extends ContentEntityInterface, EntityOwnerInterface, EntityChangedInterface {

  /**
   * Gets the advanced poll title.
   *
   * @return string
   *   Title of the advanced poll.
   */
  public function getTitle();

  /**
   * Sets the advanced poll title.
   *
   * @param string $title
   *   The advanced poll title.
   *
   * @return \Drupal\advpoll\AdvancedPollInterface
   *   The called advanced poll entity.
   */
  public function setTitle($title);

  /**
   * Gets the advanced poll creation timestamp.
   *
   * @return int
   *   Creation timestamp of the advanced poll.
   */
  public function getCreatedTime();

  /**
   * Sets the advanced poll creation timestamp.
   *
   * @param int $timestamp
   *   The advanced poll creation timestamp.
   *
   * @return \Drupal\advpoll\AdvancedPollInterface
   *   The called advanced poll entity.
   */
  public function setCreatedTime($timestamp);

  /**
   * Returns the advanced poll status.
   *
   * @return bool
   *   TRUE if the advanced poll is enabled, FALSE otherwise.
   */
  public function isEnabled();

  /**
   * Sets the advanced poll status.
   *
   * @param bool $status
   *   TRUE to enable this advanced poll, FALSE to disable.
   *
   * @return \Drupal\advpoll\AdvancedPollInterface
   *   The called advanced poll entity.
   */
  public function setStatus($status);

}
