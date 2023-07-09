<?php

namespace Drupal\external_site_monitor\Form;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Entity\EntityRepositoryInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\RendererInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Form controller for the race entity edit forms.
 */
class EntityMimicNode extends ContentEntityForm {

  /**
   * The Drupal renderer service.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $druaplRenderer;

  public function __construct(EntityRepositoryInterface $entity_repository, EntityTypeBundleInfoInterface $entity_type_bundle_info, TimeInterface $time, RendererInterface $render) {
    $this->entityRepository = $entity_repository;
    $this->entityTypeBundleInfo = $entity_type_bundle_info;
    $this->time = $time;
    $this->druaplRenderer = $render;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity.repository'),
      $container->get('entity_type.bundle.info'),
      $container->get('datetime.time'),
      $container->get('renderer')
    );
  }

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
