<?php

namespace Drupal\topic_page;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Defines the access control handler for the guide entity type.
 */
class GuideAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {

    switch ($operation) {
      case 'view':
        return AccessResult::allowedIfHasPermission($account, 'view guide');

      case 'update':
        return AccessResult::allowedIfHasPermissions($account, ['edit guide', 'administer guide'], 'OR');

      case 'delete':
        return AccessResult::allowedIfHasPermissions($account, ['delete guide', 'administer guide'], 'OR');

      default:
        // No opinion.
        return AccessResult::neutral();
    }

  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    return AccessResult::allowedIfHasPermissions($account, ['create guide', 'administer guide'], 'OR');
  }

}
