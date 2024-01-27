<?php

namespace Drupal\esm_test_base\Plugin;

use Drupal\Component\Plugin\PluginBase;
use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\esm_site\Entity\Site;
use Drupal\esm_test_base\Entity\Test;
use Drupal\esm_test_result_base\Entity\Result;
use Drupal\esm_test_base\StatusBadge;
use Drupal\esm_test_base\StatusBadgeStatus;
use Drupal\external_site_monitor\DateTimeTrait;
use Drupal\external_site_monitor\EntityTypeBundleTrait;
use Drupal\file\Entity\File;
use Drupal\views\Views;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class EsmTestRunnerBase.
 *
 * Base Function for test runners.
 */
class EsmTestRunnerBase extends PluginBase implements EsmTestRunnerInterface, ContainerFactoryPluginInterface {

  use DateTimeTrait;
  use EntityTypeBundleTrait;
  use StringTranslationTrait;

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
  public function runTest(Test $test) {}

  /**
   * {@inheritdoc}
   */
  public function buildResultsSummary(Test $test, &$build) {}

  /**
   * {@inheritdoc}
   */
  public function buildResultsTable(Test $test, &$build) {}

  /**
   * {@inheritdoc}
   */
  public function getStatusBadge(Result $result): StatusBadge {
    return new StatusBadge();
  }

  /**
   * {@inheritdoc}
   */
  public function getStatusBadgeSummary(Site $site): ?StatusBadge {
    $badge = new StatusBadge('site-summary');
    $bundles = $this->entityTypeBundleInfo()->getBundleInfo('test');

    $badge->addLabel($bundles[$this->pluginDefinition['test_type']]['label'] . " Summary");
    $badge->addItem(StatusBadgeStatus::Success, "", "");

    // Grab Tests.
    $tests = $site->getTests([
      ['bundle', $this->pluginDefinition['test_type']],
      ['status', '1'],
    ]);

    if (empty($tests)) {
      return NULL;
    }

    return $badge;
  }

  /**
   * {@inheritdoc}
   */
  public function getMostRecentResult(Test $test, array $args = []): ?Result {

    $view = Views::getView('test_results');
    $view->setDisplay("latest_result");

    if (!isset($args['test'])) {
      $args['test'] = $test->id();
    }

    if ($args) {
      $view->setExposedInput($args);
    }

    $view->preExecute();
    $view->execute();
    $result = $view->result;

    if (!empty($result) && $result[0]->_entity) {
      return $result[0]->_entity;
    }
    return NULL;
  }

  /**
   * Save Data to a file.
   */
  protected function saveJsonToFile($json, $dest):string {
    $file_uri = $this->fileSystem->saveData($json, $dest, $this->fileSystem::EXISTS_RENAME);
    return $file_uri;
  }

  /**
   * Create a File entity from existing file uri.
   */
  protected function createFile($file_uri, $entity, $module_name, $type) {

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
    // $file_usage = \Drupal::service('file.usage');
    // $file_usage->add($file, $module_name, $type, $entity->id());
    return $file;
  }

}
