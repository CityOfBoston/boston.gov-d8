<?php

namespace Drupal\script_page;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Defines the access control handler for the script page entity type.
 */
class ScriptPageAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {

    switch ($operation) {
      case 'view':
        return AccessResult::allowedIfHasPermission($account, 'view script page');

      case 'update':
        return AccessResult::allowedIfHasPermissions($account, ['edit script page', 'administer script page'], 'OR');

      case 'delete':
        return AccessResult::allowedIfHasPermissions($account, ['delete script page', 'administer script page'], 'OR');

      default:
        // No opinion.
        return AccessResult::neutral();
    }

  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    return AccessResult::allowedIfHasPermissions($account, ['create script page', 'administer script page'], 'OR');
  }

}
