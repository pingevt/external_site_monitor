<?php

namespace Drupal\esm_test_base\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\esm_test_base\EsmTestRunnerTrait;
use Drupal\external_site_monitor\EntityTypeBundleTrait;
use Drupal\external_site_monitor\EntityTypeManagerTrait;

/**
 * Provides a 'Custom' Block.
 *
 * @Block(
 *   id = "site_tests_summary_block",
 *   admin_label = @Translation("Site Tests Summary Block"),
 * )
 */
class SiteTestsSummaryBlock extends BlockBase {

  use EntityTypeManagerTrait;
  use EntityTypeBundleTrait;
  use EsmTestRunnerTrait;

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    $config = parent::defaultConfiguration();
    $config['site'] = NULL;

    return $config;
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $form = parent::blockForm($form, $form_state);

    // ksm($this->configuration);

    $site = NULL;
    if ($this->configuration['site']) {
      $site = $this->entityTypeManager()->getStorage('site')->load($this->configuration['site']);
    }

    $form['site'] = [
      '#type' => 'entity_autocomplete',
      '#title' => $this->t('Site'),
      '#target_type' => 'site',
      '#selection_settings' => [
        // 'target_bundles' => ['article'], // Optional: Specify bundles (e.g., content types for 'node')
      ],
      '#default_value' => $site?? NULL,
      '#required' => TRUE,
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockValidate($form, FormStateInterface $form_state) {

  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    if (!$form_state->getErrors()) {
      $this->configuration['site'] = $form_state->getValue('site');
    }
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    // ksm($this, $this->getContextValues(), $this->getConfiguration());

    $build = [
      '#attached' => [
        'library' => [
          'esm_test_base/site_test_summary_block',
        ],
      ],
    ];

    $site = NULL;
    if ($this->configuration['site']) {
      $build['list'] = [
        // '#theme' => 'item_list',
        // '#items' => [],
      ];
      $site = $this->entityTypeManager()->getStorage('site')->load($this->configuration['site']);

      // ksm($site);
      // $tests = $site->getTests();

      // $tests = esm_test_base_get_tests_by_site($site, status: FALSE);

      $test_types = array_keys($this->entityTypeBundleInfo()->getBundleInfo('test'));

      foreach ($test_types as $test_type) {

        $plugin_id = $test_type . "_runner";
        $runner = $this->esmTestRunnerManager()->createInstance($plugin_id);

        $summary = $runner->getStatusBadgeSummary($site);

        if ($summary) {
          $build['list'][] = $summary->renderArray();
        }
      }
    }

    // ksm($build);

    return $build;
  }

}
