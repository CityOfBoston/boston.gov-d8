<?php

namespace Drupal\procurement_advertisement;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Defines the access control handler for the procurement advertisement entity type.
 */
class ProcurementAdvertisementAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {

    switch ($operation) {
      case 'view':
        return AccessResult::allowedIfHasPermission($account, 'view procurement advertisement');

      case 'update':
        return AccessResult::allowedIfHasPermissions($account, ['edit procurement advertisement', 'administer procurement advertisement'], 'OR');

      case 'delete':
        return AccessResult::allowedIfHasPermissions($account, ['delete procurement advertisement', 'administer procurement advertisement'], 'OR');

      default:
        // No opinion.
        return AccessResult::neutral();
    }

  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    return AccessResult::allowedIfHasPermissions($account, ['create procurement advertisement', 'administer procurement advertisement'], 'OR');
  }

}
