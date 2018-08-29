<?php

namespace Drupal\place_profile;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Defines the access control handler for the place profile entity type.
 */
class PlaceProfileAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {

    switch ($operation) {
      case 'view':
        return AccessResult::allowedIfHasPermission($account, 'view place profile');

      case 'update':
        return AccessResult::allowedIfHasPermissions($account, ['edit place profile', 'administer place profile'], 'OR');

      case 'delete':
        return AccessResult::allowedIfHasPermissions($account, ['delete place profile', 'administer place profile'], 'OR');

      default:
        // No opinion.
        return AccessResult::neutral();
    }

  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    return AccessResult::allowedIfHasPermissions($account, ['create place profile', 'administer place profile'], 'OR');
  }

}
