<?php

namespace Drupal\esm_test_lighthouse\Controller;

use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

use Drupal\file\Entity\File;

use Drupal\external_site_monitor\DateTimeTrait;

/**
 * Defines a route controller for ...
 */
class Api extends ControllerBase implements ContainerInjectionInterface {

  use DateTimeTrait;

  /**
   * Entity Type Manager.
   *
   * @var Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

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
   * The Config Factory.
   *
   * @var \Drupal\Core\Config\ConfigFactory
   */
  protected $configFactory;

  /**
   * {@inheritdoc}
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, FileSystemInterface $file_system, LoggerChannelFactoryInterface $factory, ConfigFactory $config_factory) {
    $this->entityTypeManager = $entity_type_manager;
    $this->fileSystem = $file_system;
    $this->loggerFactory = $factory;
    $this->configFactory = $config_factory;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('file_system'),
      $container->get('logger.factory'),
      $container->get('config.factory')
    );
  }

  /**
   * Save data from API callback.
   */
  public function lighthouseResults(Request $request) {

    $data = [
      "status" => "OK",
      "data" => [],
    ];

    // Grab config.
    $config = $this->configFactory->get('esm_test_lighthouse.settings');

    $body = $request->getContent();
    $body_json = json_decode($body);
    $data['request_body'] = $body_json;

    $report_id_arr = explode("+", $body_json->id_string);

    $report_storage = $this->entityTypeManager->getStorage('result');

    foreach ($body_json->results as $i => $result) {
      $reports = $report_storage->loadByProperties([
        'id' => $report_id_arr,
        'field_url' => $result->url,
      ]);

      $data['counts'][] = count($reports);

      if (!empty($reports)) {
        $report = current($reports);

        // Set Summary.
        $report->field_performance = $result->summary->performance;
        $report->field_accessibility = $result->summary->accessibility;
        $report->field_best_practices = $result->summary->{'best-practices'};
        $report->field_seo = $result->summary->seo;
        $report->field_pwa = $result->summary->pwa;

        // Add files.
        $html_filename = explode(".lighthouseci/", $result->htmlPath)[1];
        $json_filename = explode(".lighthouseci/", $result->jsonPath)[1];

        $time = $this->getDateTimeFromEntity($report);
        $target_dir = $config->get('dir') . "/" . $time->format("Ymd-His");

        $target_html_file = $target_dir . "/" . $html_filename;
        $target_json_file = $target_dir . "/" . $json_filename;

        $data['report_files'][$i]['html_file'] = $target_html_file;
        $data['report_files'][$i]['json_file'] = $target_json_file;

        if (file_exists($target_html_file)) {
          $data['report_files'][$i]['html'] = ["exists"];
          $file = $this->createFile($target_html_file, $report);
          $report->field_lh_html_report = $file->id();
        }
        if (file_exists($target_json_file)) {
          $data['report_files'][$i]['json'] = ["exists"];
          $file = $this->createFile($target_json_file, $report);
          $report->field_lh_json_report = $file->id();
        }

        $report->save();
      }
      else {
        // @todo log error
      }
    }

    $response = new JsonResponse($data);

    return $response;
  }

  /**
   * Create a File entity from given URL.
   */
  private function createFile($file_uri, $entity) {

    // Check if File exists first.
    $file_storage = $this->entityTypeManager->getStorage('file');
    $files = $file_storage->loadByProperties(['uri' => $file_uri]);

    if (empty($files)) {
      $file = File::create([
        'filename' => basename($file_uri),
        'uri' => $file_uri,
        'status' => 1,
        'uid' => 1,
      ]);
      $file->save();
    }
    else {
      $file = current($files);
    }

    // Update usage so it doesn't get deleted.
    $file_usage = \Drupal::service('file.usage');
    $file_usage->add($file, 'esm_test_lighthouse', 'result', $entity->id());

    return $file;
  }

}
