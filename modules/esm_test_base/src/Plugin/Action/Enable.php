<?php

namespace Drupal\esm_test_base\Plugin\Action;

use Drupal\views_bulk_operations\Action\ViewsBulkOperationsActionBase;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Action description.
 *
 * @Action(
 *   id = "enable_test",
 *   label = @Translation("Enable Test"),
 *   confirm = TRUE,
 *   requirements = {
 *     "_permission" = "edit test",
 *   },
 *   type = "test"
 * )
 */
class Enable extends ViewsBulkOperationsActionBase {

  use StringTranslationTrait;

  /**
   * {@inheritdoc}
   */
  public function execute($entity = NULL) {

    $entity->setStatus(1);
    $entity->save();

    // Don't return anything for a default completion message,
    // otherwise return translatable markup.
    return $this->t('Test enabled');
  }

  /**
   * {@inheritdoc}
   */
  public function access($object, AccountInterface $account = NULL, $return_as_object = FALSE) {
    return $object->access('update', $account, $return_as_object);
  }

}
