<?php

namespace Drupal\department_profile;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Defines the access control handler for the department profile entity type.
 */
class DepartmentProfileAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {

    switch ($operation) {
      case 'view':
        return AccessResult::allowedIfHasPermission($account, 'view department profile');

      case 'update':
        return AccessResult::allowedIfHasPermissions($account, ['edit department profile', 'administer department profile'], 'OR');

      case 'delete':
        return AccessResult::allowedIfHasPermissions($account, ['delete department profile', 'administer department profile'], 'OR');

      default:
        // No opinion.
        return AccessResult::neutral();
    }

  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    return AccessResult::allowedIfHasPermissions($account, ['create department profile', 'administer department profile'], 'OR');
  }

}
