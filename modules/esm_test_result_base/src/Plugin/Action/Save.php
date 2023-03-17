<?php

namespace Drupal\esm_test_result_base\Plugin\Action;

use Drupal\views_bulk_operations\Action\ViewsBulkOperationsActionBase;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Action description.
 *
 * @Action(
 *   id = "save_test_result",
 *   label = @Translation("Save Test Result"),
 *   confirm = TRUE,
 *   requirements = {
 *     "_permission" = "save result",
 *   },
 *   type = "result"
 * )
 */
class Save extends ViewsBulkOperationsActionBase {

  use StringTranslationTrait;

  /**
   * {@inheritdoc}
   */
  public function execute($entity = NULL) {

    $entity->save();

    // Don't return anything for a default completion message,
    // otherwise return translatable markup.
    return $this->t('Test Result saved');
  }

  /**
   * {@inheritdoc}
   */
  public function access($object, AccountInterface $account = NULL, $return_as_object = FALSE) {
    return $object->access('update', $account, $return_as_object);
  }

}
