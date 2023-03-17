<?php

namespace Drupal\esm_site\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\external_site_monitor\Form\EntityMimicNode;

/**
 * Form controller for the site entity edit forms.
 */
class SiteForm extends EntityMimicNode {

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
      $this->messenger()->addStatus($this->t('New site %label has been created.', $message_arguments));
      $this->logger('esm_site')->notice('Created new site %label', $logger_arguments);
    }
    else {
      $this->messenger()->addStatus($this->t('The site %label has been updated.', $message_arguments));
      $this->logger('esm_site')->notice('Updated new site %label.', $logger_arguments);
    }

    $form_state->setRedirect('entity.site.canonical', ['site' => $entity->id()]);
  }

}
