<?php

namespace Drupal\esm_test_html_val\Plugin\EsmTestRunner;

use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\esm_site\Entity\Site;
use Drupal\esm_test_base\Plugin\EsmTestRunnerBase;
use Drupal\esm_test_base\Plugin\EsmTestRunnerInterface;
use Drupal\esm_test_result_base\Entity\Result;
use Drupal\esm_test_base\StatusBadge;
use Drupal\esm_test_base\StatusBadgeStatus;
use Drupal\external_site_monitor\DateTimeTrait;
use Drupal\external_site_monitor\EntityTypeBundleTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class HtmlValTestRunner runs HTML validation.
 *
 * @EsmTestRunner(
 *   id = "html_val_test_runner",
 *   test_type = "html_val_test",
 *   test_result_type = "html_val_test_result"
 * )
 */
class HtmlValTestRunner extends EsmTestRunnerBase implements EsmTestRunnerInterface, ContainerFactoryPluginInterface {

  use DateTimeTrait;
  use EntityTypeBundleTrait;

  /**
   * Entity Type Manager.
   *
   * @var Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The Config Factory.
   *
   * @var \Drupal\Core\Config\ConfigFactory
   */
  protected $configFactory;

  /**
   * The file system.
   *
   * @var \Drupal\Core\File\FileSystemInterface
   */
  protected $fileSystem;

  /**
   * The logger factory.
   *
   * @var \Drupal\Core\Logger\LoggerChannelFactoryInterface
   */
  protected $loggerFactory;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityTypeManagerInterface $entity_type_manager, ConfigFactory $config_factory, FileSystemInterface $file_system, LoggerChannelFactoryInterface $factory) {
    $this->configuration = $configuration;
    $this->pluginId = $plugin_id;
    $this->pluginDefinition = $plugin_definition;
    $this->entityTypeManager = $entity_type_manager;
    $this->configFactory = $config_factory;
    $this->fileSystem = $file_system;
    $this->loggerFactory = $factory;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager'),
      $container->get('config.factory'),
      $container->get('file_system'),
      $container->get('logger.factory')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function runTest($test) {

    // Grab config.
    $config = $this->configFactory->get('esm_test_html_val.settings');

    // Set Time for this report.
    $created = new \DateTime("now", $this->utcTz());
    $created->setTimezone($this->utcTz());
    $timestamp = $created->format("Ymd-His");

    // Prepare Directory.
    $target_dir = $config->get('dir') . "/" . $timestamp;
    $this->fileSystem->prepareDirectory($target_dir, FileSystemInterface::CREATE_DIRECTORY);

    // Grab test URLs.
    $test_url_string_arr = array_map(
      function ($el) {
        return $el['uri'];
      },
      $test->getTestingUrls()
    );

    $api_test_base = "https://validator.w3.org/nu/";

    // Prepare results entities.
    $results_entities = [];
    foreach ($test_url_string_arr as $url) {
      $result = Result::create([
        'bundle' => $this->pluginDefinition['test_result_type'],
        'created' => $created->getTimestamp(),
        'title' => "Test Results for " . $test->label(),
        'test' => $test->id(),
        'field_url' => $url,
      ]);
      $result->save();

      $url_params = [
        "doc" => $url,
        "out" => "json",
      ];

      $full_url = $api_test_base . "?" . http_build_query($url_params);

      $ch = curl_init($full_url);
      curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
      curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'User-Agent: Curl/2000',
      ]);

      $response = curl_exec($ch);
      $response_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);

      if ($errno = curl_errno($ch)) {
        $this->loggerFactory->get('esm:html_val')->debug("Response Code: @code", ["@code" => $response_code]);
        $error_message = curl_strerror($errno);
        $this->loggerFactory->get('esm:html_val')->error("cURL erro ({@errno}): @err", [
          "@errno" => $errno,
          "@err" => $error_message,
        ]);
      }

      if ($response_code < 200 || $response_code >= 300) {
        $this->loggerFactory->get('esm:html_val')->debug("Response Code: @code", ["@code" => $response_code]);
      }

      // Close the connection, release resources used.
      curl_close($ch);

      // Get Data to update Result entity.
      $data = $this->processResponseJson($response);

      $result->field_info = $data['info'];
      $result->field_error = $data['error'];
      $result->field_non_doc_error = $data['non-document-error'];

      // Save JSON to file.
      $dest_full_uri = $target_dir . "/data--" . $result->id() . ".json";
      $file_uri = $this->saveJsonToFile($response, $dest_full_uri);

      $file = $this->createFile($file_uri, $result, 'esm_test_html_val', 'result');

      $result->field_html_val_json_report = $file->id();
      $result->save();

      $results_entities[] = $result;

      // Update Test with lastupdate time.
      $created = new \DateTime("now", $this->utcTz());
      $created->setTimestamp($result->getCreatedTime());

      $test->setNewRevision();
      $test->revision_log = "Test Results";
      $test->last_run = $created->format("Y-m-d\TH:i:s");
      $test->save();
    }

  }

  /**
   * Process the Response data to get number of each type of message.
   */
  protected function processResponseJson($data) {
    $data = json_decode($data);

    $response = [
      'info' => 0,
      'error' => 0,
      'non-document-error' => 0,
    ];

    if (isset($data->messages)) {
      foreach ($data->messages as $message) {
        $response[$message->type]++;
      }
    }

    return $response;
  }

  /**
   * {@inheritDoc}
   */
  public function buildResultsSummary($test, &$build) {
    foreach ($test->getTestingUrls() as $url_field_data) {
      $results = views_get_view_result("html_val_test_results", "base", $test->id(), $url_field_data['uri']);
      if ($result = $results[0]->_entity) {
        $badge = $this->getStatusBadge($results[0]->_entity);
        $build['status_' . $results[0]->_entity->id()] = $badge->renderArray();
      }
    }
  }

  /**
   * {@inheritDoc}
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
            'view' => views_embed_view("html_val_test_results", "chart_base", $test->id(), $url_field_data['uri']),
          ],
          [
            '#attributes' => [
              'class' => ["o-url-result--table"],
            ],
            'view' => views_embed_view("html_val_test_results", "base", $test->id(), $url_field_data['uri']),
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

    $badge = new StatusBadge('html_val_test');
    $badge->addLibrary('esm_test_html_val/status_badge');

    $badge->addLabel(str_replace(['https://', "http://"], "", $result->field_url->uri));
    if ($result->field_error->value !== NULL) {
      $badge->addItem(($result->field_error->value > 1) ? "error" : StatusBadgeStatus::Success, $result->field_error->value);
    }
    else {
      $badge->addItem(StatusBadgeStatus::Info, "-");
    }

    if ($result->field_info->value !== NULL) {
      $badge->addItem(($result->field_info->value > 1) ? "error" : StatusBadgeStatus::Success, $result->field_info->value);
    }
    else {
      $badge->addItem(StatusBadgeStatus::Info, "-");
    }

    if ($result->field_non_doc_error->value !== NULL) {
      $badge->addItem(($result->field_non_doc_error->value > 1) ? "error" : StatusBadgeStatus::Success, $result->field_non_doc_error->value);
    }
    else {
      $badge->addItem(StatusBadgeStatus::Info, "-");
    }

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
          if ($result = $this->getMostRecentResult($test, ['url' => $url_field_data['uri']])) {

            $data[0] += $result->field_error->value;
            $data[1] += $result->field_info->value;
            $data[2] += $result->field_non_doc_error->value;

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
      if ($data[0] > 0) {
        $status = "error";
      }
      $badge->addItem($status, number_format($data[0], 2), "Errors");

      // 7 day.
      $status = StatusBadgeStatus::Success;
      if ($data[1] > 0) {
        $status = "error";
      }
      $badge->addItem($status, number_format($data[1], 2), "Infos");

      // 30 day.
      $status = StatusBadgeStatus::Success;
      if ($data[2] > 0) {
        $status = "error";
      }
      $badge->addItem($status, number_format($data[2], 2), "Non Doc error");

      return $badge;
    }

    return new StatusBadge();
  }

}
