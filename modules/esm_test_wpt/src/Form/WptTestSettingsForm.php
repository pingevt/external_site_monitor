<?php

namespace Drupal\esm_test_wpt\Form;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Configure External Site Monitor: Web Page Test settings for this site.
 */
class WptTestSettingsForm extends ConfigFormBase {

  /**
   * Entity Type Manager.
   *
   * @var Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * {@inheritdoc}
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager) {
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('config.factory')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'esm_test_wpt_wpt_test_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['esm_test_wpt.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $form['dir'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Directory'),
      '#default_value' => $this->config('esm_test_wpt.settings')->get('dir'),
    ];

    return parent::buildForm($form, $form_state);
  }

  // phpcs:disable Generic.CodeAnalysis.UselessOverridingMethod.Found

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);
  }

  // phpcs:enable

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('esm_test_wpt.settings')
      ->set('dir', $form_state->getValue('dir'))
      ->save();
    parent::submitForm($form, $form_state);
  }

}
