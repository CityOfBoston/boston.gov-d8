<?php

namespace Drupal\how_to;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Defines the access control handler for the how-to entity type.
 */
class HowToAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {

    switch ($operation) {
      case 'view':
        return AccessResult::allowedIfHasPermission($account, 'view how-to');

      case 'update':
        return AccessResult::allowedIfHasPermissions($account, ['edit how-to', 'administer how-to'], 'OR');

      case 'delete':
        return AccessResult::allowedIfHasPermissions($account, ['delete how-to', 'administer how-to'], 'OR');

      default:
        // No opinion.
        return AccessResult::neutral();
    }

  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    return AccessResult::allowedIfHasPermissions($account, ['create how-to', 'administer how-to'], 'OR');
  }

}
