<?php

namespace Drupal\external_site_monitor\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Form controller for the race entity edit forms.
 */
class EntityMimicNode extends ContentEntityForm {

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    // Use the edit form template.
    $form['#theme'][] = 'content_edit_form';

    // Use the form layout library.
    $form['#attached']['library'] = ['external_site_monitor/entity_mimic_node'];

    // Create the advanced element if it doesn't exist.
    if (!isset($form['advanced'])) {
      $form['advanced'] = [
        '#type' => 'container',
        '#weight' => 99,
        '#accordion' => TRUE,
      ];
    }
    else {
      // Make 'advanced' a container instead of vertical_tabs.
      $form['advanced']['#type'] = 'container';
      $form['advanced']['#accordion'] = TRUE;
    }

    $form['status']['#group'] = 'footer';

    // Add the style class to tell the admin theme how to style it.
    $form['advanced']['#attributes']['class'][] = 'entity-meta';

    $form['author'] = [
      '#type' => 'details',
      '#title' => $this->t('Authoring information'),
      '#group' => 'advanced',
      '#attributes' => [
        'class' => ['form-author'],
      ],
      '#attached' => [
        'library' => ['external_site_monitor/entity_mimic_node'],
      ],
      '#weight' => 90,
      '#optional' => TRUE,
    ];

    if (isset($form['uid'])) {
      $form['uid']['#group'] = 'author';
    }

    if (isset($form['created'])) {
      $form['created']['#group'] = 'author';
    }

    return $form;
  }

}
