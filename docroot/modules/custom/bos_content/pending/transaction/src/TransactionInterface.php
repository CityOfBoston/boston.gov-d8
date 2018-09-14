<?php

namespace Drupal\transaction;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\user\EntityOwnerInterface;
use Drupal\Core\Entity\EntityChangedInterface;

/**
 * Provides an interface defining a transaction entity type.
 */
interface TransactionInterface extends ContentEntityInterface, EntityOwnerInterface, EntityChangedInterface {

  /**
   * Gets the transaction title.
   *
   * @return string
   *   Title of the transaction.
   */
  public function getTitle();

  /**
   * Sets the transaction title.
   *
   * @param string $title
   *   The transaction title.
   *
   * @return \Drupal\transaction\TransactionInterface
   *   The called transaction entity.
   */
  public function setTitle($title);

  /**
   * Gets the transaction creation timestamp.
   *
   * @return int
   *   Creation timestamp of the transaction.
   */
  public function getCreatedTime();

  /**
   * Sets the transaction creation timestamp.
   *
   * @param int $timestamp
   *   The transaction creation timestamp.
   *
   * @return \Drupal\transaction\TransactionInterface
   *   The called transaction entity.
   */
  public function setCreatedTime($timestamp);

  /**
   * Returns the transaction status.
   *
   * @return bool
   *   TRUE if the transaction is enabled, FALSE otherwise.
   */
  public function isEnabled();

  /**
   * Sets the transaction status.
   *
   * @param bool $status
   *   TRUE to enable this transaction, FALSE to disable.
   *
   * @return \Drupal\transaction\TransactionInterface
   *   The called transaction entity.
   */
  public function setStatus($status);

}
