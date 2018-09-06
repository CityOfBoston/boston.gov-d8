<?php

namespace Drupal\test_component_page;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Defines the access control handler for the test component page entity type.
 */
class TestComponentPageAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {

    switch ($operation) {
      case 'view':
        return AccessResult::allowedIfHasPermission($account, 'view test component page');

      case 'update':
        return AccessResult::allowedIfHasPermissions($account, ['edit test component page', 'administer test component page'], 'OR');

      case 'delete':
        return AccessResult::allowedIfHasPermissions($account, ['delete test component page', 'administer test component page'], 'OR');

      default:
        // No opinion.
        return AccessResult::neutral();
    }

  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    return AccessResult::allowedIfHasPermissions($account, ['create test component page', 'administer test component page'], 'OR');
  }

}
