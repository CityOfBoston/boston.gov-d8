<?php

namespace Drupal\emergency_alert;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Defines the access control handler for the emergency alert entity type.
 */
class EmergencyAlertAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {

    switch ($operation) {
      case 'view':
        return AccessResult::allowedIfHasPermission($account, 'view emergency alert');

      case 'update':
        return AccessResult::allowedIfHasPermissions($account, ['edit emergency alert', 'administer emergency alert'], 'OR');

      case 'delete':
        return AccessResult::allowedIfHasPermissions($account, ['delete emergency alert', 'administer emergency alert'], 'OR');

      default:
        // No opinion.
        return AccessResult::neutral();
    }

  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    return AccessResult::allowedIfHasPermissions($account, ['create emergency alert', 'administer emergency alert'], 'OR');
  }

}
