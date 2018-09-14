<?php

namespace Drupal\person_profile;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Defines the access control handler for the person profile entity type.
 */
class PersonProfileAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {

    switch ($operation) {
      case 'view':
        return AccessResult::allowedIfHasPermission($account, 'view person profile');

      case 'update':
        return AccessResult::allowedIfHasPermissions($account, ['edit person profile', 'administer person profile'], 'OR');

      case 'delete':
        return AccessResult::allowedIfHasPermissions($account, ['delete person profile', 'administer person profile'], 'OR');

      default:
        // No opinion.
        return AccessResult::neutral();
    }

  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    return AccessResult::allowedIfHasPermissions($account, ['create person profile', 'administer person profile'], 'OR');
  }

}
