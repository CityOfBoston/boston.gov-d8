<?php

namespace Drupal\site_alert;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Defines the access control handler for the site alert entity type.
 */
class SiteAlertAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {

    switch ($operation) {
      case 'view':
        return AccessResult::allowedIfHasPermission($account, 'view site alert');

      case 'update':
        return AccessResult::allowedIfHasPermissions($account, ['edit site alert', 'administer site alert'], 'OR');

      case 'delete':
        return AccessResult::allowedIfHasPermissions($account, ['delete site alert', 'administer site alert'], 'OR');

      default:
        // No opinion.
        return AccessResult::neutral();
    }

  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    return AccessResult::allowedIfHasPermissions($account, ['create site alert', 'administer site alert'], 'OR');
  }

}
