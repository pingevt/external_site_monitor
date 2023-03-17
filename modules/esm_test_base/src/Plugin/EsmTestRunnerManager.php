<?php

namespace Drupal\esm_test_base\Plugin;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;

/**
 * Class EsmTestRunner.
 *
 * @see plugin_api
 */
class EsmTestRunnerManager extends DefaultPluginManager {

  /**
   * EntityTypeClassManager constructor.
   *
   * @param \Traversable $namespaces
   *   An object that implements \Traversable which contains the root paths
   *   keyed by the corresponding namespace to look for plugin
   *   implementations.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache_backend
   *   Cache backend instance to use.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler to invoke the alter hook with.
   */
  public function __construct(\Traversable $namespaces, CacheBackendInterface $cache_backend, ModuleHandlerInterface $module_handler) {
    parent::__construct(
      'Plugin/EsmTestRunner',
      $namespaces,
      $module_handler,
      'Drupal\esm_test_base\Plugin\EsmTestRunnerInterface',
      'Drupal\esm_test_base\Annotation\EsmTestRunner'
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getInstance(array $options) {
    $test_type = $options['testType'];
    foreach ($this->getDefinitions() as $plugin_id => $definition) {
      if ($definition['testType'] == $test_type) {
        return $this->createInstance($plugin_id, $options);
      }
    }
  }

}
