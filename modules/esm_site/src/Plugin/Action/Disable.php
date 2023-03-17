<?php

namespace Drupal\esm_site\Plugin\Action;

use Drupal\views_bulk_operations\Action\ViewsBulkOperationsActionBase;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Action description.
 *
 * @Action(
 *   id = "disable_site",
 *   label = @Translation("Disable site"),
 *   confirm = TRUE,
 *   requirements = {
 *     "_permission" = "edit site",
 *   },
 *   type = "site"
 * )
 */
class Disable extends ViewsBulkOperationsActionBase {

  use StringTranslationTrait;

  /**
   * {@inheritdoc}
   */
  public function execute($entity = NULL) {

    $entity->setStatus(0);
    $entity->save();

    // Don't return anything for a default completion message,
    // otherwise return translatable markup.
    return $this->t('Site disabled');
  }

  /**
   * {@inheritdoc}
   */
  public function access($object, AccountInterface $account = NULL, $return_as_object = FALSE) {
    return $object->access('update', $account, $return_as_object);
  }

}
