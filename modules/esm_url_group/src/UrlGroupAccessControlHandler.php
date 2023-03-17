<?php

namespace Drupal\esm_url_group;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Defines the access control handler for the url group entity type.
 */
class UrlGroupAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {

    switch ($operation) {
      case 'view':
        return AccessResult::allowedIfHasPermission($account, 'view url group');

      case 'update':
        return AccessResult::allowedIfHasPermissions($account, ['edit url group', 'administer url group'], 'OR');

      case 'delete':
        return AccessResult::allowedIfHasPermissions($account, ['delete url group', 'administer url group'], 'OR');

      default:
        // No opinion.
        return AccessResult::neutral();
    }

  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    return AccessResult::allowedIfHasPermissions($account, ['create url group', 'administer url group'], 'OR');
  }

}
