<?php

namespace Drupal\esm_test_timing_monitor\Form;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Configure External Site Monitor: Web Page Test settings for this site.
 */
class PchrTestSettingsForm extends ConfigFormBase {

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
    return 'esm_test_timing_monitor_wpt_test_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['esm_test_timing_monitor.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    // API Key.
    $key = NULL;
    if ($this->config('esm_test_timing_monitor.settings')->get('api_key')) {
      $key_storage = $this->entityTypeManager->getStorage("key");
      $key = $key_storage->load($this->config('esm_test_timing_monitor.settings')->get('api_key'));
    }

    // ksm($key, $key->getKeyValue());
    $form['api_key'] = [
      '#type' => 'entity_autocomplete',
      '#target_type' => 'key',
      '#title' => $this->t('API Key'),
      '#description' => $this->t("Please create a key with the API Key."),
      '#tags' => FALSE,
      '#selection_settings' => [],
      '#default_value' => $key,
    ];

    $form['dir'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Directory'),
      '#default_value' => $this->config('esm_test_timing_monitor.settings')->get('dir'),
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
    $this->config('esm_test_timing_monitor.settings')
      // ->set('branch', $form_state->getValue('branch'))
      // ->set('api_url', $form_state->getValue('api_url'))
      ->set('api_key', $form_state->getValue('github_token'))
      ->set('dir', $form_state->getValue('dir'))
      ->save();
    parent::submitForm($form, $form_state);
  }

}
