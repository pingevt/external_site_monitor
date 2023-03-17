<?php

namespace Drupal\esm_test_base\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\esm_site\Entity\Site;
use Drupal\esm_test_base\Entity\Test;
use Drupal\esm_test_base\Plugin\EsmTestRunnerManager;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Controller for handling a Site's Tests.
 */
class TestResults extends ControllerBase implements ContainerInjectionInterface {

  /**
   * Runner Plugin Manager.
   *
   * @var Drupal\esm_test_base\Plugin\EsmTestRunnerManager
   */
  private $runnerManager;

  /**
   * {@inheritdoc}
   */
  public function __construct(EsmTestRunnerManager $runner_manager) {
    $this->runnerManager = $runner_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('plugin.manager.esm_test_runner')
    );
  }

  /**
   * Build render array for test results.
   */
  public function render(Site $site, Test $test, Request $request) {
    return $this->renderFromTest($test, $request);
  }

  /**
   * Build render array for test results.
   */
  public function renderFromTest(Test $test, Request $request) {
    $build = [
      'header' => [],
      'table' => [],
    ];

    $plugin_id = $test->bundle() . "_runner";
    $runner = $this->runnerManager->createInstance($plugin_id);
    $runner->buildResultsTable($test, $build);

    return $build;
  }

}
