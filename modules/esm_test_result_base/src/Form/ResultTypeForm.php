<?php

namespace Drupal\esm_test_result_base\Form;

use Drupal\Core\Entity\BundleEntityFormBase;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Form\FormStateInterface;

/**
 * Form handler for result type forms.
 */
class ResultTypeForm extends BundleEntityFormBase {

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    $entity_type = $this->entity;
    if ($this->operation == 'add') {
      $form['#title'] = $this->t('Add result type');
    }
    else {
      $form['#title'] = $this->t(
        'Edit %label result type',
        ['%label' => $entity_type->label()]
      );
    }

    $form['label'] = [
      '#title' => $this->t('Label'),
      '#type' => 'textfield',
      '#default_value' => $entity_type->label(),
      '#description' => $this->t('The human-readable name of this result type.'),
      '#required' => TRUE,
      '#size' => 30,
    ];

    $form['id'] = [
      '#type' => 'machine_name',
      '#default_value' => $entity_type->id(),
      '#maxlength' => EntityTypeInterface::BUNDLE_MAX_LENGTH,
      '#machine_name' => [
        'exists' => ['Drupal\esm_test_result_base\Entity\ResultType', 'load'],
        'source' => ['label'],
      ],
      '#description' => $this->t('A unique machine-readable name for this result type. It must only contain lowercase letters, numbers, and underscores.'),
    ];

    return $this->protectBundleIdElement($form);
  }

  /**
   * {@inheritdoc}
   */
  protected function actions(array $form, FormStateInterface $form_state) {
    $actions = parent::actions($form, $form_state);
    $actions['submit']['#value'] = $this->t('Save result type');
    $actions['delete']['#value'] = $this->t('Delete result type');
    return $actions;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $entity_type = $this->entity;

    $entity_type->set('id', trim($entity_type->id()));
    $entity_type->set('label', trim($entity_type->label()));

    $status = $entity_type->save();

    $t_args = ['%name' => $entity_type->label()];
    if ($status == SAVED_UPDATED) {
      $message = $this->t('The result type %name has been updated.', $t_args);
    }
    elseif ($status == SAVED_NEW) {
      $message = $this->t('The result type %name has been added.', $t_args);
    }
    $this->messenger()->addStatus($message);

    $form_state->setRedirectUrl($entity_type->toUrl('collection'));
  }

}
