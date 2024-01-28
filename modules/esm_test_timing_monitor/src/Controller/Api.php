<?php

namespace Drupal\esm_test_timing_monitor\Controller;

use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Database\Connection;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

use Drupal\file\Entity\File;

use Drupal\external_site_monitor\DateTimeTrait;
use Drupal\esm_test_base\Entity\Test;

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
   * Database Connection.
   *
   * @var Drupal\Core\Database\Connection
   */
  private $database;

  /**
   * {@inheritdoc}
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, FileSystemInterface $file_system, LoggerChannelFactoryInterface $factory, ConfigFactory $config_factory, Connection $database) {
    $this->entityTypeManager = $entity_type_manager;
    $this->fileSystem = $file_system;
    $this->loggerFactory = $factory;
    $this->configFactory = $config_factory;
    $this->database = $database;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('file_system'),
      $container->get('logger.factory'),
      $container->get('config.factory'),
      $container->get('database')
    );
  }

  /**
   * Save data from API callback.
   */
  public function tmResults(Test $test, Request $request) {

    $data = [
      "status" => "OK",
      "data" => [],
    ];

    // Grab config.
    // $config = $this->configFactory->get('esm_test_timing_monitor.settings');
    // $result = $test->getMostRecentResult();

    // $body = $request->getContent();
    // $body_json = json_decode($body);
    // // $data['request_body'] = $body_json;

    // $query = $this->database->select("esm_pchr_data", 'ep');
    // $query->fields('ep', []);
    // $query->condition('result', $result->id());
    // $query->range(0, 35);
    // $query->orderBy('period', 'DESC');
    // $exisitng_data = $query->execute()->fetchAll();
    // // $data['exisitng_data'] = $exisitng_data;
    // $exisitng_data_assoc = [];
    // foreach ($exisitng_data as $ed) {
    //   $exisitng_data_assoc[$ed->period] = $ed;
    // }
    // // $data['exisitng_data_assoc'] = $exisitng_data_assoc;

    // $data_to_update = [];
    // $data_to_insert = [];
    // foreach ($body_json->timeseries as $timestamp => $day_data) {
    //   $date = \DateTime::createFromFormat('Y-m-d\TH:i:s', $day_data->datetime, $this->utcTz());

    //   if (isset($exisitng_data_assoc[$date->format('Y-m-d')])) {
    //     $data_to_update[] = array_merge((array) $exisitng_data_assoc[$date->format('Y-m-d')], [
    //       'period' => $date->format('Y-m-d'),
    //       'visits' => (int) $day_data->visits,
    //       'pages_served' => (int) $day_data->pages_served,
    //       'cache_hits' => (int) $day_data->cache_hits,
    //       'cache_misses' => (int) $day_data->cache_misses,
    //       'cache_hit_ratio' => (float) $day_data->cache_hit_ratio,
    //     ]);
    //   }
    //   else {
    //     $data_to_insert[] = [
    //       'result' => (int) $result->id(),
    //       'period' => $date->format('Y-m-d'),
    //       'visits' => (int) $day_data->visits,
    //       'pages_served' => (int) $day_data->pages_served,
    //       'cache_hits' => (int) $day_data->cache_hits,
    //       'cache_misses' => (int) $day_data->cache_misses,
    //       'cache_hit_ratio' => (float) $day_data->cache_hit_ratio,
    //     ];
    //   }
    // }

    // // $data['data_to_update'] = $data_to_update;
    // // $data['data_to_insert'] = $data_to_insert;

    // // Update Queries.
    // if (!empty($data_to_update)) {
    //   foreach ($data_to_update as $u) {
    //     $updateq = $this->database->update('esm_pchr_data');
    //     $updateq->fields($u);
    //     $updateq->condition('id', $u['id']);
    //     $updateq->execute();
    //   }
    // }

    // // Insert Queries.
    // if (!empty($data_to_insert)) {
    //   $insertq = $this->database->insert('esm_pchr_data');
    //   $insertq->fields(array_keys($data_to_insert[0]));
    //   foreach($data_to_insert as $di) {
    //     $insertq->values($di);
    //   }
    //   $insertq->execute();
    // }

    // // Update Result.
    // // Hook will update fields.
    // $result->save();

    // // Setup response.
    // $response = new JsonResponse($data);

    return $response;
  }

}
