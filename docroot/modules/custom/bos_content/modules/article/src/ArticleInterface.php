<?php

namespace Drupal\article;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\user\EntityOwnerInterface;
use Drupal\Core\Entity\EntityChangedInterface;

/**
 * Provides an interface defining an article entity type.
 */
interface ArticleInterface extends ContentEntityInterface, EntityOwnerInterface, EntityChangedInterface {

  /**
   * Gets the article title.
   *
   * @return string
   *   Title of the article.
   */
  public function getTitle();

  /**
   * Sets the article title.
   *
   * @param string $title
   *   The article title.
   *
   * @return \Drupal\article\ArticleInterface
   *   The called article entity.
   */
  public function setTitle($title);

  /**
   * Gets the article creation timestamp.
   *
   * @return int
   *   Creation timestamp of the article.
   */
  public function getCreatedTime();

  /**
   * Sets the article creation timestamp.
   *
   * @param int $timestamp
   *   The article creation timestamp.
   *
   * @return \Drupal\article\ArticleInterface
   *   The called article entity.
   */
  public function setCreatedTime($timestamp);

  /**
   * Returns the article status.
   *
   * @return bool
   *   TRUE if the article is enabled, FALSE otherwise.
   */
  public function isEnabled();

  /**
   * Sets the article status.
   *
   * @param bool $status
   *   TRUE to enable this article, FALSE to disable.
   *
   * @return \Drupal\article\ArticleInterface
   *   The called article entity.
   */
  public function setStatus($status);

}
