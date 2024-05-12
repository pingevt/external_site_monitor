<?php

namespace Drupal\esm_test_pa11y\Form;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Configure External Site Monitor: Pa11y Testing settings for this site.
 */
class Pa11yTestSettingsForm extends ConfigFormBase {

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
    return 'esm_test_pa11y_pa11y_test_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['esm_test_pa11y.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    // ksm($this);
    $form['branch'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Branch'),
      '#default_value' => $this->config('esm_test_pa11y.settings')->get('branch'),
    ];

    $form['api_url'] = [
      '#type' => 'textfield',
      '#title' => $this->t('API Url'),
      '#description' => $this->t("i.e. https://api.github.com/repos/{owner}/{repo}/actions/workflows/{workflow-id}/dispatches"),
      '#default_value' => $this->config('esm_test_pa11y.settings')->get('api_url'),
    ];

    // Github Token.
    $key = NULL;
    if ($this->config('esm_test_pa11y.settings')->get('github_token')) {
      $key_storage = $this->entityTypeManager->getStorage("key");
      $key = $key_storage->load($this->config('esm_test_pa11y.settings')->get('github_token'));
    }

    $form['github_token'] = [
      '#type' => 'entity_autocomplete',
      '#target_type' => 'key',
      '#title' => $this->t('GitHub Token'),
      '#description' => $this->t("Please create a key with the Github Token."),
      '#tags' => FALSE,
      '#selection_settings' => [],
      '#default_value' => $key,
    ];

    $form['dir'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Directory'),
      '#default_value' => $this->config('esm_test_pa11y.settings')->get('dir'),
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
    $this->config('esm_test_pa11y.settings')
      // ->set('example', $form_state->getValue('example'))
      ->set('branch', $form_state->getValue('branch'))
      ->set('api_url', $form_state->getValue('api_url'))
      ->set('github_token', $form_state->getValue('github_token'))
      ->set('dir', $form_state->getValue('dir'))
      ->save();
    parent::submitForm($form, $form_state);
  }

}
