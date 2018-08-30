<?php

namespace Drupal\script_page;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\user\EntityOwnerInterface;
use Drupal\Core\Entity\EntityChangedInterface;

/**
 * Provides an interface defining a script page entity type.
 */
interface ScriptPageInterface extends ContentEntityInterface, EntityOwnerInterface, EntityChangedInterface {

  /**
   * Gets the script page title.
   *
   * @return string
   *   Title of the script page.
   */
  public function getTitle();

  /**
   * Sets the script page title.
   *
   * @param string $title
   *   The script page title.
   *
   * @return \Drupal\script_page\ScriptPageInterface
   *   The called script page entity.
   */
  public function setTitle($title);

  /**
   * Gets the script page creation timestamp.
   *
   * @return int
   *   Creation timestamp of the script page.
   */
  public function getCreatedTime();

  /**
   * Sets the script page creation timestamp.
   *
   * @param int $timestamp
   *   The script page creation timestamp.
   *
   * @return \Drupal\script_page\ScriptPageInterface
   *   The called script page entity.
   */
  public function setCreatedTime($timestamp);

  /**
   * Returns the script page status.
   *
   * @return bool
   *   TRUE if the script page is enabled, FALSE otherwise.
   */
  public function isEnabled();

  /**
   * Sets the script page status.
   *
   * @param bool $status
   *   TRUE to enable this script page, FALSE to disable.
   *
   * @return \Drupal\script_page\ScriptPageInterface
   *   The called script page entity.
   */
  public function setStatus($status);

}
