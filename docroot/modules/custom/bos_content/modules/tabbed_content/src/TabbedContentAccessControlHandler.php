<?php

namespace Drupal\tabbed_content;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Defines the access control handler for the tabbed content entity type.
 */
class TabbedContentAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {

    switch ($operation) {
      case 'view':
        return AccessResult::allowedIfHasPermission($account, 'view tabbed content');

      case 'update':
        return AccessResult::allowedIfHasPermissions($account, ['edit tabbed content', 'administer tabbed content'], 'OR');

      case 'delete':
        return AccessResult::allowedIfHasPermissions($account, ['delete tabbed content', 'administer tabbed content'], 'OR');

      default:
        // No opinion.
        return AccessResult::neutral();
    }

  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    return AccessResult::allowedIfHasPermissions($account, ['create tabbed content', 'administer tabbed content'], 'OR');
  }

}
