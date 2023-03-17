<?php

namespace Drupal\esm_test_base;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Defines the access control handler for the test entity type.
 */
class TestAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {

    switch ($operation) {
      case 'view':
        return AccessResult::allowedIfHasPermission($account, 'view test');

      case 'update':
        return AccessResult::allowedIfHasPermissions($account, [
          'edit test',
          'administer test',
        ], 'OR');

      case 'delete':
        return AccessResult::allowedIfHasPermissions($account, [
          'delete test',
          'administer test',
        ], 'OR');

      default:
        // No opinion.
        return AccessResult::neutral();
    }

  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    return AccessResult::allowedIfHasPermissions($account, [
      'create test',
      'administer test',
    ], 'OR');
  }

}
