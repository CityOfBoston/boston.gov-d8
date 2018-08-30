<?php

namespace Drupal\post;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\user\EntityOwnerInterface;
use Drupal\Core\Entity\EntityChangedInterface;

/**
 * Provides an interface defining a post entity type.
 */
interface PostInterface extends ContentEntityInterface, EntityOwnerInterface, EntityChangedInterface {

  /**
   * Gets the post title.
   *
   * @return string
   *   Title of the post.
   */
  public function getTitle();

  /**
   * Sets the post title.
   *
   * @param string $title
   *   The post title.
   *
   * @return \Drupal\post\PostInterface
   *   The called post entity.
   */
  public function setTitle($title);

  /**
   * Gets the post creation timestamp.
   *
   * @return int
   *   Creation timestamp of the post.
   */
  public function getCreatedTime();

  /**
   * Sets the post creation timestamp.
   *
   * @param int $timestamp
   *   The post creation timestamp.
   *
   * @return \Drupal\post\PostInterface
   *   The called post entity.
   */
  public function setCreatedTime($timestamp);

  /**
   * Returns the post status.
   *
   * @return bool
   *   TRUE if the post is enabled, FALSE otherwise.
   */
  public function isEnabled();

  /**
   * Sets the post status.
   *
   * @param bool $status
   *   TRUE to enable this post, FALSE to disable.
   *
   * @return \Drupal\post\PostInterface
   *   The called post entity.
   */
  public function setStatus($status);

}
