<?php

namespace Drupal\landing_page;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Defines the access control handler for the landing page entity type.
 */
class LandingPageAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {

    switch ($operation) {
      case 'view':
        return AccessResult::allowedIfHasPermission($account, 'view landing page');

      case 'update':
        return AccessResult::allowedIfHasPermissions($account, ['edit landing page', 'administer landing page'], 'OR');

      case 'delete':
        return AccessResult::allowedIfHasPermissions($account, ['delete landing page', 'administer landing page'], 'OR');

      default:
        // No opinion.
        return AccessResult::neutral();
    }

  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    return AccessResult::allowedIfHasPermissions($account, ['create landing page', 'administer landing page'], 'OR');
  }

}
