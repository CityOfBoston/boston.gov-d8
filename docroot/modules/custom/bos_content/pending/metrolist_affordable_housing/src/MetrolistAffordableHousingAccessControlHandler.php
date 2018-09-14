<?php

namespace Drupal\metrolist_affordable_housing;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Defines the access control handler for the metrolist affordable housing entity type.
 */
class MetrolistAffordableHousingAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {

    switch ($operation) {
      case 'view':
        return AccessResult::allowedIfHasPermission($account, 'view metrolist affordable housing');

      case 'update':
        return AccessResult::allowedIfHasPermissions($account, ['edit metrolist affordable housing', 'administer metrolist affordable housing'], 'OR');

      case 'delete':
        return AccessResult::allowedIfHasPermissions($account, ['delete metrolist affordable housing', 'administer metrolist affordable housing'], 'OR');

      default:
        // No opinion.
        return AccessResult::neutral();
    }

  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    return AccessResult::allowedIfHasPermissions($account, ['create metrolist affordable housing', 'administer metrolist affordable housing'], 'OR');
  }

}
