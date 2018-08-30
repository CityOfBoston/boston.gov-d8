<?php

namespace Drupal\public_notice;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Defines the access control handler for the public notice entity type.
 */
class PublicNoticeAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {

    switch ($operation) {
      case 'view':
        return AccessResult::allowedIfHasPermission($account, 'view public notice');

      case 'update':
        return AccessResult::allowedIfHasPermissions($account, ['edit public notice', 'administer public notice'], 'OR');

      case 'delete':
        return AccessResult::allowedIfHasPermissions($account, ['delete public notice', 'administer public notice'], 'OR');

      default:
        // No opinion.
        return AccessResult::neutral();
    }

  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    return AccessResult::allowedIfHasPermissions($account, ['create public notice', 'administer public notice'], 'OR');
  }

}
