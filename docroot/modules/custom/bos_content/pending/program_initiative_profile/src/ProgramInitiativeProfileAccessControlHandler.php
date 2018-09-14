<?php

namespace Drupal\program_initiative_profile;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Defines the access control handler for the program initiative profile entity type.
 */
class ProgramInitiativeProfileAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {

    switch ($operation) {
      case 'view':
        return AccessResult::allowedIfHasPermission($account, 'view program initiative profile');

      case 'update':
        return AccessResult::allowedIfHasPermissions($account, ['edit program initiative profile', 'administer program initiative profile'], 'OR');

      case 'delete':
        return AccessResult::allowedIfHasPermissions($account, ['delete program initiative profile', 'administer program initiative profile'], 'OR');

      default:
        // No opinion.
        return AccessResult::neutral();
    }

  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    return AccessResult::allowedIfHasPermissions($account, ['create program initiative profile', 'administer program initiative profile'], 'OR');
  }

}
