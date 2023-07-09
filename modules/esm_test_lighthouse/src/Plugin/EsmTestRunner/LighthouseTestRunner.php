<?php

namespace Drupal\esm_test_lighthouse\Plugin\EsmTestRunner;

use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Url;
use Drupal\esm_test_base\Plugin\EsmTestRunnerBase;
use Drupal\esm_test_base\Plugin\EsmTestRunnerInterface;
use Drupal\esm_test_result_base\StatusBadge;
use Drupal\esm_test_result_base\Entity\Result;
use Drupal\external_site_monitor\DateTimeTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class WptTestRunner.
 *
 * @EsmTestRunner(
 *   id = "lighthouse_test_runner",
 *   test_type = "lighthouse_test",
 *   test_result_type = "lighthouse_test_result"
 * )
 */
class LighthouseTestRunner extends EsmTestRunnerBase implements EsmTestRunnerInterface, ContainerFactoryPluginInterface {

  use DateTimeTrait;

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
    $config = $this->configFactory->get('esm_test_lighthouse.settings');

    // Set Time for this report.
    $created = new \DateTime("now", $this->utcTz());
    $created->setTimezone($this->utcTz());
    $timestamp = $created->format("Ymd-His");

    // Prepare Directory.
    $target_dir = $config->get('dir') . "/" . $timestamp;
    $short_dir = explode("://", $target_dir)[1];
    $this->fileSystem->prepareDirectory($target_dir, FileSystemInterface::CREATE_DIRECTORY);

    // Grab test URLs.
    $test_url_string_arr = array_map(
      function ($el) {
        return $el['uri'];
      },
      $test->getTestingUrls()
    );

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
      $results_entities[] = $result;
    }

    $result_ids_arr = array_map(
      function ($el) {
        return $el->id();
      },
      $results_entities
    );

    $test_url_string = implode(",", $test_url_string_arr);

    if ($config->get('github_token')) {
      $key_storage = $this->entityTypeManager->getStorage("key");
      $key = $key_storage->load($config->get('github_token'));

      $callback_url = Url::fromRoute('esm_test_lighthouse.lighthouse_results', [], ['absolute' => TRUE])->toString();

      $post_data = [
        'ref' => $config->get('branch'),
        'inputs' => [
          'urls' => $test_url_string,
          'dir' => $short_dir,
          'result_ids' => implode("+", $result_ids_arr),
          'callback' => $callback_url,
        ],
      ];

      $auth_string = 'Authorization: Bearer ' . $key->getKeyValue();

      $this->loggerFactory->get('esm:lighthouse')->debug("Attempting to Call Github Action: <pre>" . print_r([
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

      $this->loggerFactory->get('esm:lighthouse')->debug("Response Code: @code", ["@code" => $response_code]);

      if ($errno = curl_errno($ch)) {
        $error_message = curl_strerror($errno);
        $this->loggerFactory->get('esm:lighthouse')->error("cURL erro ({@errno}): @err", [
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
            'view' => views_embed_view("lighthouse_test_result", "chart_base", $test->id(), $url_field_data['uri']),
          ],
          [
            '#attributes' => [
              'class' => ["o-url-result--table"],
            ],
            'view' => views_embed_view("lighthouse_test_result", "base", $test->id(), $url_field_data['uri']),
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

    // All fields share the same conditions.
    $fields_to_check = [
      "field_performance",
      "field_best_practices",
      "field_accessibility",
      "field_seo",
      "field_pwa",
    ];

    foreach ($fields_to_check as $field) {

      if ($result->{$field}->value != NULL) {
        if ($result->{$field}->value <= 0.5) {
          $badge->addItem("error", $result->{$field}->value, str_replace("field_", "", $field));
        }
        elseif ($result->{$field}->value > 0.5 && $result->{$field}->value <= 0.85) {
          $badge->addItem("warning", $result->{$field}->value, str_replace("field_", "", $field));
        }
        elseif ($result->{$field}->value > 0.85 && $result->{$field}->value <= 0.92) {
          $badge->addItem("info", $result->{$field}->value, str_replace("field_", "", $field));
        }
        elseif ($result->{$field}->value > 0.92) {
          $badge->addItem("success", $result->{$field}->value, str_replace("field_", "", $field));
        }
      }
      else {
        $badge->addItem("info", "-", str_replace("field_", "", $field));
      }
    }

    return $badge;
  }

}
