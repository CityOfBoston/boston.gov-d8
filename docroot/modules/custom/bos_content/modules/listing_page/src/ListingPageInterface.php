<?php

namespace Drupal\listing_page;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\user\EntityOwnerInterface;
use Drupal\Core\Entity\EntityChangedInterface;

/**
 * Provides an interface defining a listing page entity type.
 */
interface ListingPageInterface extends ContentEntityInterface, EntityOwnerInterface, EntityChangedInterface {

  /**
   * Gets the listing page title.
   *
   * @return string
   *   Title of the listing page.
   */
  public function getTitle();

  /**
   * Sets the listing page title.
   *
   * @param string $title
   *   The listing page title.
   *
   * @return \Drupal\listing_page\ListingPageInterface
   *   The called listing page entity.
   */
  public function setTitle($title);

  /**
   * Gets the listing page creation timestamp.
   *
   * @return int
   *   Creation timestamp of the listing page.
   */
  public function getCreatedTime();

  /**
   * Sets the listing page creation timestamp.
   *
   * @param int $timestamp
   *   The listing page creation timestamp.
   *
   * @return \Drupal\listing_page\ListingPageInterface
   *   The called listing page entity.
   */
  public function setCreatedTime($timestamp);

  /**
   * Returns the listing page status.
   *
   * @return bool
   *   TRUE if the listing page is enabled, FALSE otherwise.
   */
  public function isEnabled();

  /**
   * Sets the listing page status.
   *
   * @param bool $status
   *   TRUE to enable this listing page, FALSE to disable.
   *
   * @return \Drupal\listing_page\ListingPageInterface
   *   The called listing page entity.
   */
  public function setStatus($status);

}
