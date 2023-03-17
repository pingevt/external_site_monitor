<?php

namespace Drupal\esm_test_base\Plugin\Action;

use Drupal\views_bulk_operations\Action\ViewsBulkOperationsActionBase;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Action description.
 *
 * @Action(
 *   id = "disable_test",
 *   label = @Translation("Disable Test"),
 *   confirm = TRUE,
 *   requirements = {
 *     "_permission" = "edit test",
 *   },
 *   type = "test"
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
    return $this->t('Test disabled');
  }

  /**
   * {@inheritdoc}
   */
  public function access($object, AccountInterface $account = NULL, $return_as_object = FALSE) {
    return $object->access('update', $account, $return_as_object);
  }

}
