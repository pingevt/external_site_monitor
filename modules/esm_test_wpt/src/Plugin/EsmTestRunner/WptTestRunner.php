<?php

namespace Drupal\esm_test_wpt\Plugin\EsmTestRunner;

use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\esm_test_base\Plugin\EsmTestRunnerInterface;
use Drupal\esm_test_base\Plugin\EsmTestRunnerBase;
use Drupal\esm_test_result_base\StatusBadge;
use Drupal\esm_test_result_base\Entity\Result;

/**
 * Class WptTestRunner runs the tag checker test.
 *
 * @EsmTestRunner(
 *   id = "wpt_test_runner",
 *   test_type = "wpt_test",
 *   test_result_type = "wpt_test_result"
 * )
 */
class WptTestRunner extends EsmTestRunnerBase implements EsmTestRunnerInterface, ContainerFactoryPluginInterface {

  /**
   * {@inheritdoc}
   */
  public function runTest($test) {

  }

  /**
   * {@inheritdoc}
   */
  public function buildResultsSummary($test, &$build) {
    foreach ($test->getTestingUrls() as $url_field_data) {
      if ($result = $this->getMostRecentResult($test, ['url' => $url_field_data['uri']])) {
        $badge = $this->getStatusBadge($result);
        $build['status_' . $result->id()] = $badge->renderArray();
      }
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
    foreach ($test->getTestingUrls() as $url_field_data) {
      $views[] = [

        '#attributes' => [
          'class' => ["o-url-result"],
        ],
        "group" => [
          [
            '#attributes' => [
              'class' => ["o-url-result--chart"],
            ],
            'view' => views_embed_view("wpt_test_results", "chart_base", $test->id(), $url_field_data['uri']),
          ],
          [
            '#attributes' => [
              'class' => ["o-url-result--table"],
            ],
            'view' => views_embed_view("wpt_test_results", "base", $test->id(), $url_field_data['uri']),
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
    $badge->addItem("info", ($result->field_speed_index_f->value / 1000) . "s", "Speed Index");

    return $badge;
  }

}
