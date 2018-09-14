<?php

namespace Drupal\tabbed_content;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\user\EntityOwnerInterface;
use Drupal\Core\Entity\EntityChangedInterface;

/**
 * Provides an interface defining a tabbed content entity type.
 */
interface TabbedContentInterface extends ContentEntityInterface, EntityOwnerInterface, EntityChangedInterface {

  /**
   * Gets the tabbed content title.
   *
   * @return string
   *   Title of the tabbed content.
   */
  public function getTitle();

  /**
   * Sets the tabbed content title.
   *
   * @param string $title
   *   The tabbed content title.
   *
   * @return \Drupal\tabbed_content\TabbedContentInterface
   *   The called tabbed content entity.
   */
  public function setTitle($title);

  /**
   * Gets the tabbed content creation timestamp.
   *
   * @return int
   *   Creation timestamp of the tabbed content.
   */
  public function getCreatedTime();

  /**
   * Sets the tabbed content creation timestamp.
   *
   * @param int $timestamp
   *   The tabbed content creation timestamp.
   *
   * @return \Drupal\tabbed_content\TabbedContentInterface
   *   The called tabbed content entity.
   */
  public function setCreatedTime($timestamp);

  /**
   * Returns the tabbed content status.
   *
   * @return bool
   *   TRUE if the tabbed content is enabled, FALSE otherwise.
   */
  public function isEnabled();

  /**
   * Sets the tabbed content status.
   *
   * @param bool $status
   *   TRUE to enable this tabbed content, FALSE to disable.
   *
   * @return \Drupal\tabbed_content\TabbedContentInterface
   *   The called tabbed content entity.
   */
  public function setStatus($status);

}
