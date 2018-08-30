<?php

namespace Drupal\status_item;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Defines the access control handler for the status item entity type.
 */
class StatusItemAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {

    switch ($operation) {
      case 'view':
        return AccessResult::allowedIfHasPermission($account, 'view status item');

      case 'update':
        return AccessResult::allowedIfHasPermissions($account, ['edit status item', 'administer status item'], 'OR');

      case 'delete':
        return AccessResult::allowedIfHasPermissions($account, ['delete status item', 'administer status item'], 'OR');

      default:
        // No opinion.
        return AccessResult::neutral();
    }

  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    return AccessResult::allowedIfHasPermissions($account, ['create status item', 'administer status item'], 'OR');
  }

}
