<?php

namespace Drupal\esm_test_timing_monitor\Plugin\EsmTestRunner;

use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Url;
use Drupal\esm_site\Entity\Site;
use Drupal\esm_test_base\Plugin\EsmTestRunnerInterface;
use Drupal\esm_test_base\Plugin\EsmTestRunnerBase;
use Drupal\esm_test_base\StatusBadge;
use Drupal\esm_test_base\StatusBadgeStatus;
use Drupal\esm_test_result_base\Entity\Result;
use Drupal\external_site_monitor\DatabaseTrait;
use Drupal\external_site_monitor\EntityTypeBundleTrait;
use Drupal\external_site_monitor\HttpClientTrait;

/**
 * Class TmTestRunner runs the tag checker test.
 *
 * @EsmTestRunner(
 *   id = "tm_test_runner",
 *   test_type = "tm_test",
 *   test_result_type = "tm_test_result"
 * )
 */
class TmTestRunner extends EsmTestRunnerBase implements EsmTestRunnerInterface, ContainerFactoryPluginInterface {

  use DatabaseTrait;
  use EntityTypeBundleTrait;
  use HttpClientTrait;

  protected $dataCache = [];

  /**
   * {@inheritdoc}
   */
  public function runTest($test) {

    // Grab config.
    $config = $this->configFactory->get('esm_test_timing_monitor.settings');

    // Set Time for this report.
    $created = new \DateTime("now", $this->utcTz());
    $created->setTimezone($this->utcTz());
    $timestamp = $created->format("Ymd-His");

    // Prepare Directory.
    $target_dir = $config->get('dir') . "/" . $timestamp;
    $short_dir = explode("://", $target_dir)[1];
    $this->fileSystem->prepareDirectory($target_dir, FileSystemInterface::CREATE_DIRECTORY);

    $urls = $test->getTestingUrls();
    $url_field_data = current($urls);

    $days = 14;

    // We always use the same result.
    // If no result yet, create the result.
    if (!($result = $this->getMostRecentResult($test, ['test' => $test->id()]))) {
      // Create Result.
      $result = Result::create([
        'bundle' => $this->pluginDefinition['test_result_type'],
        'created' => $created->getTimestamp(),
        'title' => "Test Results for " . $test->label(),
        'test' => $test->id(),
      ]);
      $result->save();

      $days = 60;
    }

    if ($config->get('api_key')) {
      $key_storage = $this->entityTypeManager->getStorage("key");
      $key = $key_storage->load($config->get('api_key'));

      $headers = [
        'api-key' => $key->getKeyValue(),
      ];

      $options = [
        'headers' => $headers,
        'http_errors' => FALSE,
      ];

      $api_url = $url_field_data['uri'];
      $api_url .= "/api/timing-monitor/";
      $api_url .= $test->field_tm_type->value;
      $api_url .= "/daily-average?days=" . $days;

      $this->loggerFactory->get('esm:tm')->debug("Attempting to call Target Site: <pre>" . print_r([
        $api_url,
      ], TRUE) . "</pre>", []);

      $httpClient = $this->httpClient();

      $response = $this->httpClient()->get($api_url, $options);
      // ksm($response, $response->getBody()->getContents());
      $contents = $response->getBody()->getContents();


      $this->loggerFactory->get('esm:tm')->debug("Response Code: @code", ["@code" => $response->getStatusCode()]);
      $this->loggerFactory->get('esm:tm')->debug("Contents: @contents", ["@contents" => $contents]);

      if ($response->getStatusCode() == 200) {
        $return_data = json_decode($contents);

        // Handle return data.
        // Grab current data so we can update/merge properly.
        $query = $this->database()->select("esm_tm_daily_data", 'tm');
        $query->fields('tm', []);
        $query->condition('result', $result->id());
        $query->range(0, 35);
        $query->orderBy('period', 'DESC');
        $exisitng_data = $query->execute()->fetchAll();

        $exisitng_data_assoc = [];
        foreach ($exisitng_data as $ed) {
          $exisitng_data_assoc[$ed->period] = $ed;
        }

        $data_to_update = [];
        $data_to_insert = [];
        foreach ($return_data->data->dates as $date => $day_data) {

          if (isset($exisitng_data_assoc[$date])) {
            $data_to_update[] = array_merge((array) $exisitng_data_assoc[$date], [
              'period' => $date,
              'average' => is_null($day_data) ? NULL : (float) $day_data,
            ]);
          }
          else {
            $data_to_insert[] = [
              'result' => (int) $result->id(),
              'period' => $date,
              'average' => is_null($day_data) ? NULL : (float) $day_data,
            ];
          }
        }

        // Update Queries.
        if (!empty($data_to_update)) {
          foreach ($data_to_update as $u) {
            $updateq = $this->database->update('esm_tm_daily_data');
            $updateq->fields($u);
            $updateq->condition('id', $u['id']);
            $updateq->execute();
          }
        }

        // Insert Queries.
        if (!empty($data_to_insert)) {
          $insertq = $this->database->insert('esm_tm_daily_data');
          $insertq->fields(array_keys($data_to_insert[0]));
          foreach($data_to_insert as $di) {
            $insertq->values($di);
          }
          $insertq->execute();
        }

        // Save Result and hook will update fields.
        $result->save();
      }
      else {
        $this->loggerFactory->get('esm:tm')->error("ERROR calling Target Site: @site", ["@site" => $api_url]);
      }

      // Set Test Data.
      $test->setNewRevision();
      $test->revision_log = "Tests Run";
      $test->last_run = $created->format("Y-m-d\TH:i:s");
      $test->save();
    }
  }

  /**
   * {@inheritdoc}
   */
  public function buildResultsSummary($test, &$build) {
    ksm('buildResultsSummary');
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
    $views[] = [
      '#attributes' => [
        'class' => ["o-url-result"],
      ],
      "group" => [
        [
          '#attributes' => [
            'class' => ["o-url-result--chart"],
          ],
          'view' => $this->getDataChart($test),
        ],
        [
          '#attributes' => [
            'class' => ["o-url-result--table"],
          ],
          'view' => views_embed_view("tm_test_results", "base", $test->id()),
        ],
      ],
    ];

    $build['#content'] = [
      "data" => $views,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getStatusBadge(Result $result):StatusBadge {
    $badge = new StatusBadge();
    $test = $result->test->entity;

    $s = "";
    $s .= $test->label();
    // $s .= ".";
    // $s .= $test->label();

    $badge->addLabel($s);

    // Latest.
    $latest_val = $result->field_latest_avg->value;
    $status = "success";
    if ($latest_val >= 0.05) {
      $status = "warning";
    }
    if ($latest_val >= 0.1) {
      $status = "error";
    }
    elseif ($latest_val >= 0.01) {
      $status = "info";
    }
    $badge->addItem($status, number_format($latest_val, 4) . "s", "Latest Average");

    // 7 day.
    $seven_val = $result->field_7_day->value;
    $status = "success";
    if ($seven_val >= 0.05) {
      $status = "warning";
    }
    if ($seven_val >= 0.1) {
      $status = "error";
    }
    elseif ($seven_val >= 0.01) {
      $status = "info";
    }
    $badge->addItem($status, number_format($seven_val, 4) . "s", "7 day");

    // 30 day.
    $thirty_val = $result->field_30_day->value;
    $status = "success";
    if ($thirty_val >= 0.05) {
      $status = "warning";
    }
    if ($thirty_val >= 0.1) {
      $status = "error";
    }
    elseif ($thirty_val >= 0.01) {
      $status = "info";
    }
    $badge->addItem($status, number_format($thirty_val, 4) . "s", "30 day");

    return $badge;
  }

  /**
   * {@inheritdoc}
   */
  public function getStatusBadgeSummary(Site $site): ?StatusBadge  {
    // Grab Tests.
    $tests = $site->getTests([
      ['bundle', $this->pluginDefinition['test_type']],
      ['status', '1'],
    ]);

    if (!empty($tests)) {

      $data = array_fill(0, 3, 0);
      $results_count = 0;
      foreach ($tests as $test) {
        foreach ($test->getTestingUrls() as $url_field_data) {
          if ($result = $this->getMostRecentResult($test)) {
            $data[0] += $result->field_latest_avg->value;
            $data[1] += $result->field_7_day->value;
            $data[2] += $result->field_30_day->value;

            $results_count++;
          }
        }
      }

      $data[0] = $data[0] / $results_count;
      $data[1] = $data[1] / $results_count;
      $data[2] = $data[2] / $results_count;

      $badge = new StatusBadge();

      $bundles = $this->entityTypeBundleInfo()->getBundleInfo('test');
      $badge->addLabel($bundles[$this->pluginDefinition['test_type']]['label'] . " Summary");

      // Latest.
      $status = StatusBadgeStatus::Success;
      if ($data[0] >= 0.05) {
        $status = "warning";
      }
      if ($data[0] >= 0.1) {
        $status = "error";
      }
      elseif ($data[0] >= 0.01) {
        $status = "info";
      }
      $badge->addItem($status, number_format($data[0], 4) . "s", "Latest Ratio");

      // 7 day.
      $status = StatusBadgeStatus::Success;
      if ($data[1] >= 0.05) {
        $status = "warning";
      }
      if ($data[1] >= 0.1) {
        $status = "error";
      }
      elseif ($data[1] >= 0.01) {
        $status = "info";
      }
      $badge->addItem($status, number_format($data[1], 4) . "s", "7 day");

      // 30 day.
      $status = StatusBadgeStatus::Success;
      if ($data[2] >= 0.05) {
        $status = "warning";
      }
      if ($data[2] >= 0.1) {
        $status = "error";
      }
      elseif ($data[2] >= 0.01) {
        $status = "info";
      }
      $badge->addItem($status, number_format($data[2], 4) . "s", "30 day");

      return $badge;
    }

    return new StatusBadge();
  }

  public function getDataChart($test, $span = 90) {

    list(
      $labels,
      $averages,
      $running7_ratios,
      $running30_ratios,
    ) = $this->getData($test);

    // Set spans.
    $labels = array_slice($labels, ($span * -1));
    $averages = array_slice($averages, ($span * -1));
    $running7_ratios = array_slice($running7_ratios, ($span * -1));
    $running30_ratios = array_slice($running30_ratios, ($span * -1));

    // Build chart.
    $chart = [
      'view' => [
        '#type' => 'chart',
        '#title' => 'Average Execution Time Report',
        '#chart_type' => 'line',
        '#legend_position' => "bottom",
        'y_axis' => [
          '#type' => 'chart_yaxis',
          '#title' => $this->t('%'),
          '#min' => 0,
          '#opposite' => TRUE,
        ],
        'series' => [
          '#type' => 'chart_data',
          '#title' => t('Averages'),
          '#data' => $averages,
          '#color' => "#d42d2d",
        ],
        'series7' => [
          '#type' => 'chart_data',
          '#title' => t('Averages - 7 Day running'),
          '#data' => $running7_ratios,
          '#color' => "#8d1a69",
        ],
        'series30' => [
          '#type' => 'chart_data',
          '#title' => t('Averages - 30 Day running'),
          '#data' => $running30_ratios,
          '#color' => "#000000",
        ],

        'xaxis' => [
          '#type' => 'chart_xaxis',
          '#title' => t('Days'),
          '#labels' => $labels,
        ],
      ],
    ];

    return $chart;
  }

  public function getData($test) {

    if (isset($this->dataCache[$test->id()])) {
      return $this->dataCache[$test->id()];
    }

    // Combine data.
    $result = $this->getMostRecentResult($test, ['test' => $test->id()]);

    $query = \Drupal::database()->select("esm_tm_daily_data", 'ep');
    $query->fields('ep', []);
    $query->condition('result', $result->id());
    $query->range(0, 90);
    $query->orderBy('period', 'ASC');
    $exisitng_data = $query->execute()->fetchAll();

    $labels = [];
    $averages = [];
    $running7_items = [];
    $running7_ratios = [];
    $running30_items = [];
    $running30_ratios = [];

    foreach($exisitng_data as $i => $day_data) {
      $labels[] = $day_data->period;
      $averages[] = (float) $day_data->average;

      // 7 day.
      if ($i < 7) {
        $running7_items[] = (float) $day_data->average;
      }
      else {
        array_shift($running7_items);
        $running7_items[] = (float) $day_data->average;
      }
      $running7_ratios[] = ( array_reduce($running7_items, function ($carry, $item) {
        $carry += $item;
        return $carry;
      }) / count($running7_items));

      // 30 day.
      if ($i < 30) {
        $running30_items[] = (float) $day_data->average;
      }
      else {
        array_shift($running30_items);
        $running30_items[] = (float) $day_data->average;
      }
      $running30_ratios[] = ( array_reduce($running30_items, function ($carry, $item) {
        $carry += $item;
        return $carry;
      }) / count($running30_items));
    }

    $this->dataCache[$test->id()] = [
      $labels,
      $averages,
      $running7_ratios,
      $running30_ratios,
    ];

    return [
      $labels,
      $averages,
      $running7_ratios,
      $running30_ratios,
    ];
  }

}
