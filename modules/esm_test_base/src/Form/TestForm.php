<?php

namespace Drupal\esm_test_base\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\external_site_monitor\Form\EntityMimicNode;

/**
 * Form controller for the test entity edit forms.
 */
class TestForm extends EntityMimicNode {

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);
    $entity = $this->getEntity();

    $type_entity = $entity->bundle->entity;
    $allow_manual = $type_entity->allowManual();

    // Require Schedule Field if we don't allow manual results.
    $form['schedule']['widget'][0]['value']['#required'] = !($allow_manual);
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {

    $entity = $this->getEntity();
    $result = $entity->save();
    $link = $entity->toLink($this->t('View'))->toRenderable();

    $message_arguments = ['%label' => $this->entity->label()];
    $logger_arguments = $message_arguments + ['link' => $this->druaplRenderer->render($link)];

    if ($result == SAVED_NEW) {
      $this->messenger()->addStatus($this->t('New test %label has been created.', $message_arguments));
      $this->logger('esm_test_base')->notice('Created new test %label', $logger_arguments);
    }
    else {
      $this->messenger()->addStatus($this->t('The test %label has been updated.', $message_arguments));
      $this->logger('esm_test_base')->notice('Updated new test %label.', $logger_arguments);
    }

    $form_state->setRedirect('entity.test.canonical', ['test' => $entity->id()]);
  }

}
