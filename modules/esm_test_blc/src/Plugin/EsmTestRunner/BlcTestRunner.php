<?php

namespace Drupal\esm_test_blc\Plugin\EsmTestRunner;

use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\esm_test_base\Plugin\EsmTestRunnerInterface;
use Drupal\esm_test_base\Plugin\EsmTestRunnerBase;
use Drupal\esm_test_blc\SiteCrawlerController;
use Drupal\esm_test_result_base\StatusBadge;
use Drupal\esm_test_result_base\Entity\Result;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class BlcTestRunner runs the broken link checker.
 *
 * @EsmTestRunner(
 *   id = "blc_test_runner",
 *   test_type = "blc_test",
 *   test_result_type = "blc_test_result"
 * )
 */
class BlcTestRunner extends EsmTestRunnerBase implements EsmTestRunnerInterface, ContainerFactoryPluginInterface {

  /**
   * {@inheritdoc}
   */
  public function runTest($test) {

    // Grab config.
    $config = $this->configFactory->get('esm_test_blc.settings');

    // Set Time for this report.
    $created = new \DateTime("now", $this->utcTz());
    $created->setTimezone($this->utcTz());
    $timestamp = $created->format("Ymd-His");

    // Grab test URL. We are only working on the site rpimary url as root.
    $site = $test->site->entity;
    $root_url = $site->field_primary_url->uri;

    // Initialize domain, and sitemap data.
    // Prepare results entities.
    $results_entities = [];

    $result = Result::create([
      'bundle' => $this->pluginDefinition['test_result_type'],
      'created' => $created->getTimestamp(),
      'title' => "Test Results for " . $test->label(),
      'test' => $test->id(),
      'field_url' => $root_url,
      'field_blc_cron_runs' => 1,
    ]);
    $result->save();

    // Create cron job to start kicking off job.
    $controller = SiteCrawlerController::create($result);

    $controller->run(); // Sets the base.
    $controller->run(); // Sets the initial sitemap.

    $controller->save();

    // Create Queue item.
    $queue = \Drupal::service('queue')->get('esm_test_blc.continue_tests');

    $queue_item = new \stdClass();
    $queue_item->test_id = $test->id();
    $queue_item->result_id = $result->id();
    $queue->createItem($queue_item);

    // Update Test with lastupdate time.
    $created = new \DateTime("now", $this->utcTz());
    $created->setTimestamp($result->getCreatedTime());

    $test->setNewRevision();
    $test->revision_log = "Test Results";
    $test->last_run = $created->format("Y-m-d\TH:i:s");
    $test->save();
  }

  /**
   * Second or later pass at running the test.
   */
  public function continueTest($test, $result) {
    // Create cron job to start kicking `o`ff job.
    $controller = SiteCrawlerController::create($result);
    // $this->startTime = time();
    $controller->run();
    $controller->save();

    if ($controller->getStatus() !== SiteCrawlerController::FINISHED) {
      $queue = \Drupal::service('queue')->get('esm_test_blc.continue_tests');

      $queue_item = new \stdClass();
      $queue_item->test_id = $test->id();
      $queue_item->result_id = $result->id();
      $queue->createItem($queue_item);
    }

    $result->field_blc_cron_runs = $result->field_blc_cron_runs->value + 1;

    $result->save();
  }

  /**
   * {@inheritdoc}
   */
  public function buildResultsSummary($test, &$build) {

    if ($result = $this->getMostRecentResult($test)) {
      $badge = $this->getStatusBadge($result);
      $build['status_' . $result->id()] = $badge->renderArray();
    }
  }

  /**
   * {@inheritdoc}
   */
  public function buildResultsTable($test, &$build) {

    $build = [
      '#theme' => 'site_test_results',
      '#test' => $test,
      '#title' => $test->label() . " Results",
    ];

    $views = [];

    $site = $test->site->entity;

    foreach ($site->field_primary_url->getValue() as $url_field_data) {
      $views[] = [
        '#attributes' => [
          'class' => ["o-url-result"],
        ],
        "group" => [
          [
            '#attributes' => [
              'class' => ["o-url-result--table"],
            ],
            'view' => views_embed_view("blc_test_results", "base", $test->id(), $url_field_data['uri']),
          ],
        ],
      ];
    }

    $build['#content'] = [
      "data" => $views,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getStatusBadge(Result $result):StatusBadge {
    $badge = new StatusBadge();

    $badge->addLabel(str_replace(['https://', "http://"], "", $result->field_url->uri));

    $problem_count = $result->field_blc_problem_link_count->value;
    if ($problem_count !== NULL) {
      if ($problem_count == 0) {
        $badge->addItem("success", $problem_count, "Problem Link Count");
      }
      else {
        $badge->addItem("error", $problem_count, "Problem Link Count");
      }
    }
    else {
      $badge->addItem("info", "-", "Problem Link Count");
    }

    return $badge;
  }

}
