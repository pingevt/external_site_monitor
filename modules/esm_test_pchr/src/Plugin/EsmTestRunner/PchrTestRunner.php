<?php

namespace Drupal\esm_test_pchr\Plugin\EsmTestRunner;

use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Url;
use Drupal\esm_test_base\Plugin\EsmTestRunnerInterface;
use Drupal\esm_test_base\Plugin\EsmTestRunnerBase;
use Drupal\esm_test_result_base\StatusBadge;
use Drupal\esm_test_result_base\Entity\Result;

/**
 * Class PchrTestRunner runs the tag checker test.
 *
 * @EsmTestRunner(
 *   id = "pchr_test_runner",
 *   test_type = "pchr_test",
 *   test_result_type = "pchr_test_result"
 * )
 */
class PchrTestRunner extends EsmTestRunnerBase implements EsmTestRunnerInterface, ContainerFactoryPluginInterface {

  protected $dataCache = [];

  /**
   * {@inheritdoc}
   */
  public function runTest($test) {

    // Grab config.
    $config = $this->configFactory->get('esm_test_pchr.settings');

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
    }

    if ($config->get('github_token')) {
      $key_storage = $this->entityTypeManager->getStorage("key");
      $key = $key_storage->load($config->get('github_token'));

      $callback_url = Url::fromRoute('esm_test_pchr.pchr_results', ['test' => $test->id()], ['absolute' => TRUE])->toString();

      $post_data = [
        'ref' => $config->get('branch'),
        'inputs' => [
          'panth-site' => $test->field_pantheon_site_name->value,
          'panth-env' => $test->field_pantheon_site_env->value,
          'callback' => $callback_url,
        ],
      ];

      // ksm($post_data);

      $auth_string = 'Authorization: Bearer ' . $key->getKeyValue();

      // ksm($auth_string);

      $this->loggerFactory->get('esm:pchr')->debug("Attempting to Call Github Action: <pre>" . print_r([
        $config->get('api_url'),
        $post_data,
      ], TRUE) . "</pre>", []);

      $ch = curl_init($config->get('api_url'));
      curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
      curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($post_data));
      curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'User-Agent: Curl/2000',
        'Accept: application/vnd.github+json',
        'Content-Type: application/json',
        $auth_string,
      ]);

      $response = curl_exec($ch);

      $response_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);

      $this->loggerFactory->get('esm:pchr')->debug("Response Code: @code", ["@code" => $response_code]);
      $this->loggerFactory->get('esm:pchr')->debug("Response: @response", ["@response" => $response]);
      $this->loggerFactory->get('esm:pchr')->debug("Response: @response", ["@response" => json_decode($response)]);

      if ($errno = curl_errno($ch)) {
        $error_message = curl_strerror($errno);
        $this->loggerFactory->get('esm:pchr')->error("cURL erro ({@errno}): @err", [
          "@errno" => $errno,
          "@err" => $error_message,
        ]);
      }

      // Close the connection, release resources used.
      curl_close($ch);

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
          'view' => views_embed_view("pchr_test_results", "base", $test->id()),
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
    $s .= $test->field_pantheon_site_name->value;
    $s .= ".";
    $s .= $test->field_pantheon_site_env->value;

    $badge->addLabel($s);

    // Latest.
    $latest_val = $result->field_latest_ratio->value;
    $status = "success";
    if ($latest_val <= 50) {
      $status = "warning";
    }
    if ($latest_val <= 70) {
      $status = "error";
    }
    elseif ($latest_val <= 90) {
      $status = "info";
    }
    $badge->addItem($status, number_format($latest_val, 2) . "%", "Latest Ratio");

    // 7day.
    $seven_val = $result->field_7_day->value;
    $status = "success";
    if ($seven_val <= 50) {
      $status = "warning";
    }
    if ($seven_val <= 70) {
      $status = "error";
    }
    elseif ($seven_val <= 90) {
      $status = "info";
    }
    $badge->addItem($status, number_format($seven_val, 2) . "%", "7 day");

    // 30 day.
    $thirty_val = $result->field_7_day->value;
    $status = "success";
    if ($thirty_val <= 50) {
      $status = "warning";
    }
    if ($thirty_val <= 70) {
      $status = "error";
    }
    elseif ($thirty_val <= 90) {
      $status = "info";
    }
    $badge->addItem($status, number_format($thirty_val, 2) . "%", "30 day");

    return $badge;
  }

  public function getDataChart($test, $span = 90) {

    list(
      $labels,
      $visits,
      $pages_served,
      $cache_hits,
      $cache_misses,
      $cache_hit_ratios,
      $running7_ratios,
      $running30_ratios,
    ) = $this->getData($test);

    // Set spans.
    $labels = array_slice($labels, ($span * -1));
    $visits = array_slice($visits, ($span * -1));
    $pages_served = array_slice($pages_served, ($span * -1));
    $cache_hits = array_slice($cache_hits, ($span * -1));
    $cache_misses = array_slice($cache_misses, ($span * -1));
    $cache_hit_ratios = array_slice($cache_hit_ratios, ($span * -1));
    $running7_ratios = array_slice($running7_ratios, ($span * -1));
    $running30_ratios = array_slice($running30_ratios, ($span * -1));

    // Build chart.
    $chart = [
      'view' => [
        '#type' => 'chart',
        '#title' => 'Page Cache Hit Ratio Report',
        '#chart_type' => 'line',
        '#legend_position' => "bottom",
        'y_axis' => [
          '#type' => 'chart_yaxis',
          '#title' => $this->t('%'),
          '#min' => 0,
          '#max' => 100,
          '#opposite' => TRUE,
        ],
        'y_axis_secondary' => [
          '#type' => 'chart_yaxis',
          // '#title' => $this->t(''),
          '#opposite' => FALSE,
        ],
        // 'y_axis_tertiary' => [
        //   '#type' => 'chart_yaxis',
        //   // '#title' => $this->t(''),
        //   '#opposite' => FALSE,
        //   '#stacking' => TRUE,
        // ],
        'series_visits' => [
          '#type' => 'chart_data',
          '#chart_type' => 'column',
          '#title' => t('Visits'),
          '#data' => $visits,
          '#target_axis' => 'y_axis_secondary',
          '#color' => "#32cafc",
        ],
        'pages_served' => [
          '#type' => 'chart_data',
          '#chart_type' => 'column',
          '#title' => t('Pages Served'),
          '#data' => $pages_served,
          '#target_axis' => 'y_axis_secondary',
          '#color' => "#97f7a2",
        ],


        // 'cache_hits' => [
        //   '#type' => 'chart_data',
        //   '#chart_type' => 'column',
        //   '#title' => t('Cache Hits'),
        //   '#data' => $cache_hits,
        //   '#target_axis' => 'y_axis_tertiary',
        //   '#stacking' => TRUE,
        // ],
        // 'cache_misses' => [
        //   '#type' => 'chart_data',
        //   '#chart_type' => 'column',
        //   '#title' => t('Cache Misses'),
        //   '#data' => $cache_misses,
        //   '#target_axis' => 'y_axis_tertiary',
        //   '#stacking' => TRUE,
        // ],


        'series30' => [
          '#type' => 'chart_data',
          '#title' => t('Cache Hit Ratio - 30 Day running'),
          '#data' => $running30_ratios,
          '#color' => "#000000",
        ],
        'series7' => [
          '#type' => 'chart_data',
          '#title' => t('Cache Hit Ratio - 7 Day running'),
          '#data' => $running7_ratios,
          '#color' => "#8d1a69",
        ],
        'series' => [
          '#type' => 'chart_data',
          '#title' => t('Cache Hit Ratio'),
          '#data' => $cache_hit_ratios,
          '#color' => "#d42d2d",
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

    $query = \Drupal::database()->select("esm_pchr_data", 'ep');
    $query->fields('ep', []);
    $query->condition('result', $result->id());
    $query->range(0, 90);
    $query->orderBy('period', 'ASC');
    $exisitng_data = $query->execute()->fetchAll();

    $labels = [];
    $visits = [];
    $pages_served = [];
    $cache_hits = [];
    $cache_misses = [];
    $cache_hit_ratios = [];
    $running7_items = [];
    $running7_ratios = [];
    $running30_items = [];
    $running30_ratios = [];

    foreach($exisitng_data as $i => $day_data) {
      $labels[] = $day_data->period;
      $visits[] = (int) $day_data->visits;
      $pages_served[] = (int) $day_data->pages_served;
      $cache_hits[] = (int) $day_data->cache_hits;
      $cache_misses[] = (int) $day_data->cache_misses;
      $cache_hit_ratios[] = (float) $day_data->cache_hit_ratio;

      // 7 day.
      if ($i < 7) {
        $running7_items[] = (float) $day_data->cache_hit_ratio;
        // $running7_ratios[] = NULL;
      }
      else {
        array_shift($running7_items);
        $running7_items[] = (float) $day_data->cache_hit_ratio;
      }
      $running7_ratios[] = ( array_reduce($running7_items, function ($carry, $item) {
        $carry += $item;
        return $carry;
      }) / count($running7_items));

      // 30 day.
      if ($i < 30) {
        $running30_items[] = (float) $day_data->cache_hit_ratio;
        // $running30_ratios[] = NULL;
      }
      else {
        array_shift($running30_items);
        $running30_items[] = (float) $day_data->cache_hit_ratio;
      }
      $running30_ratios[] = ( array_reduce($running30_items, function ($carry, $item) {
        $carry += $item;
        return $carry;
      }) / count($running30_items));
    }

    $this->dataCache[$test->id()] = [
      $labels,
      $visits,
      $pages_served,
      $cache_hits,
      $cache_misses,
      $cache_hit_ratios,
      $running7_ratios,
      $running30_ratios,
    ];

    return [
      $labels,
      $visits,
      $pages_served,
      $cache_hits,
      $cache_misses,
      $cache_hit_ratios,
      $running7_ratios,
      $running30_ratios,
    ];
  }

}
