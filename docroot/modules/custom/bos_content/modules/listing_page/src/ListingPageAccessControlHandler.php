<?php

namespace Drupal\listing_page;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Defines the access control handler for the listing page entity type.
 */
class ListingPageAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {

    switch ($operation) {
      case 'view':
        return AccessResult::allowedIfHasPermission($account, 'view listing page');

      case 'update':
        return AccessResult::allowedIfHasPermissions($account, ['edit listing page', 'administer listing page'], 'OR');

      case 'delete':
        return AccessResult::allowedIfHasPermissions($account, ['delete listing page', 'administer listing page'], 'OR');

      default:
        // No opinion.
        return AccessResult::neutral();
    }

  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    return AccessResult::allowedIfHasPermissions($account, ['create listing page', 'administer listing page'], 'OR');
  }

}
