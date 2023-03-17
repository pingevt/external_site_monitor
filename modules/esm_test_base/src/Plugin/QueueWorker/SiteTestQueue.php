<?php

namespace Drupal\esm_test_base\Plugin\QueueWorker;

use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Queue\QueueWorkerBase;
use Drupal\esm_test_base\Entity\Test;
use Drupal\esm_test_base\Plugin\EsmTestRunnerManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Run tests that are ready to go.
 *
 * @QueueWorker(
 *   id = "esm_test_base.run_tests",
 *   title = @Translation("Run tests that are ready to go"),
 *   cron = {"time" = 30}
 * )
 */
class SiteTestQueue extends QueueWorkerBase implements ContainerFactoryPluginInterface {

  /**
   * Runner Manager.
   *
   * @var Drupal\esm_test_base\Plugin\EsmTestRunnerManager
   */
  public $runnerManager;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EsmTestRunnerManager $runner_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->runnerManager = $runner_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('plugin.manager.esm_test_runner')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function processItem($item) {
    $runnerManager = \Drupal::service('plugin.manager.esm_test_runner');

    if (isset($item->test_id) && !empty($item->test_id)) {
      $test = Test::Load($item->test_id);
      $plugin_id = $test->bundle() . "_runner";
      $runner = $runnerManager->createInstance($plugin_id);
      $runner->runTest($test);
    }
  }

}
