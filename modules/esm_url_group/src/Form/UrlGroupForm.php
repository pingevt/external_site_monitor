<?php

namespace Drupal\esm_url_group\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\external_site_monitor\Form\EntityMimicNode;

/**
 * Form controller for the url group entity edit forms.
 */
class UrlGroupForm extends EntityMimicNode {

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {

    $entity = $this->getEntity();
    $result = $entity->save();
    $link = $entity->toLink($this->t('View'))->toRenderable();

    $message_arguments = ['%label' => $this->entity->label()];
    $logger_arguments = $message_arguments + ['link' => render($link)];

    if ($result == SAVED_NEW) {
      $this->messenger()->addStatus($this->t('New url group %label has been created.', $message_arguments));
      $this->logger('esm_url_group')->notice('Created new url group %label', $logger_arguments);
    }
    else {
      $this->messenger()->addStatus($this->t('The url group %label has been updated.', $message_arguments));
      $this->logger('esm_url_group')->notice('Updated new url group %label.', $logger_arguments);
    }

    $form_state->setRedirect('entity.url_group.canonical', ['url_group' => $entity->id()]);
  }

}
