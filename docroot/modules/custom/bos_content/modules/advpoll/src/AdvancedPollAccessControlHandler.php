<?php

namespace Drupal\advpoll;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Defines the access control handler for the advanced poll entity type.
 */
class AdvancedPollAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {

    switch ($operation) {
      case 'view':
        return AccessResult::allowedIfHasPermission($account, 'view advanced poll');

      case 'update':
        return AccessResult::allowedIfHasPermissions($account, ['edit advanced poll', 'administer advanced poll'], 'OR');

      case 'delete':
        return AccessResult::allowedIfHasPermissions($account, ['delete advanced poll', 'administer advanced poll'], 'OR');

      default:
        // No opinion.
        return AccessResult::neutral();
    }

  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    return AccessResult::allowedIfHasPermissions($account, ['create advanced poll', 'administer advanced poll'], 'OR');
  }

}
