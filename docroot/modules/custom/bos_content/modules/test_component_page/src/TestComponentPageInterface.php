<?php

namespace Drupal\test_component_page;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\user\EntityOwnerInterface;
use Drupal\Core\Entity\EntityChangedInterface;

/**
 * Provides an interface defining a test component page entity type.
 */
interface TestComponentPageInterface extends ContentEntityInterface, EntityOwnerInterface, EntityChangedInterface {

  /**
   * Gets the test component page title.
   *
   * @return string
   *   Title of the test component page.
   */
  public function getTitle();

  /**
   * Sets the test component page title.
   *
   * @param string $title
   *   The test component page title.
   *
   * @return \Drupal\test_component_page\TestComponentPageInterface
   *   The called test component page entity.
   */
  public function setTitle($title);

  /**
   * Gets the test component page creation timestamp.
   *
   * @return int
   *   Creation timestamp of the test component page.
   */
  public function getCreatedTime();

  /**
   * Sets the test component page creation timestamp.
   *
   * @param int $timestamp
   *   The test component page creation timestamp.
   *
   * @return \Drupal\test_component_page\TestComponentPageInterface
   *   The called test component page entity.
   */
  public function setCreatedTime($timestamp);

  /**
   * Returns the test component page status.
   *
   * @return bool
   *   TRUE if the test component page is enabled, FALSE otherwise.
   */
  public function isEnabled();

  /**
   * Sets the test component page status.
   *
   * @param bool $status
   *   TRUE to enable this test component page, FALSE to disable.
   *
   * @return \Drupal\test_component_page\TestComponentPageInterface
   *   The called test component page entity.
   */
  public function setStatus($status);

}
