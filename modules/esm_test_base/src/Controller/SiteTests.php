<?php

namespace Drupal\esm_test_base\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Link;
use Drupal\Core\Render\Markup;
use Drupal\Core\Url;
use Drupal\esm_site\Entity\Site;
use Drupal\esm_test_base\Entity\Test;
use Drupal\esm_test_base\Plugin\EsmTestRunnerManager;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Controller for handling a Site's Tests.
 */
class SiteTests extends ControllerBase implements ContainerInjectionInterface {

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
   * Build render array of site tests.
   */
  public function render(Site $site, Request $request) {
    $build = [
      'list' => [
        '#theme' => "item_list",
        '#attributes' => ['class' => ["site-test-summary-list"]],
        '#title' => Markup::create("Tests for <em>" . $site->label() . "</em>"),
        '#items' => [],
      ],
      '#attached' => [
        'library' => [
          "esm_test_base/tests",
        ],
      ],
    ];

    $test_storage = $this->entityTypeManager()->getStorage('test');
    $tests = $test_storage->loadByProperties([
      'site' => $site->id(),
      // todo: make this configureable or something.
      'status' => 1,
    ]);

    foreach ($tests as $test) {
      $result_url = Url::fromRoute("test.test_results", [
        // 'site' => $site->id(),
        'test' => $test->id(),
      ]);

      $result_link = Link::fromTextAndUrl('Results', $result_url);

      $test_url = Url::fromRoute("entity.test.canonical", [
        'test' => $test->id(),
      ]);

      $test_link = Link::fromTextAndUrl($test->label(), $test_url);

      $plugin_id = $test->bundle() . "_runner";
      $runner = $this->runnerManager->createInstance($plugin_id);
      $summary_view = [];
      $runner->buildResultsSummary($test, $summary_view);

      $build['list']['#items'][] = [
        [
          '#prefix' => "<div>",
          '#markup' => "<div class='heading'><span class='heading-f'>" . $test_link->toString() . "</span> (" . $test->bundle() . ") <span class='link'>" . $result_link->toString() . " →</span></div>",
          '#suffix' => "</div>",
          $summary_view,
        ],
      ];
    }

    return $build;
  }

  /**
   * Function to immediately run a test. Use with Caution.
   */
  public function runNow(Test $test, Request $request) {
    $plugin_id = $test->bundle() . "_runner";
    $runner = $this->runnerManager->createInstance($plugin_id);
    $runner->runTest($test);

    $this->messenger()->addStatus('Your test has been initiated.');

    return $this->redirect('entity.test.canonical', ['test' => $test->id()]);
  }

}
