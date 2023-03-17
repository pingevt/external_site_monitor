<?php

namespace Drupal\esm_test_base\Plugin\Derivative;

use Drupal\Component\Plugin\Derivative\DeriverBase;
use Drupal\Core\Plugin\Discovery\ContainerDeriverInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines dynamic local tasks.
 */
class DynamicLocalTasks extends DeriverBase implements ContainerDeriverInterface {

  /**
   * Entity Type Manager.
   *
   * @var Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The base plugin ID.
   *
   * @var string
   */
  protected $basePluginId;

  /**
   * {@inheritdoc}
   */
  public function __construct($base_plugin_id, EntityTypeManagerInterface $entity_type_manager) {
    $this->basePluginId = $base_plugin_id;
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, $base_plugin_id) {
    return new static(
      $base_plugin_id,
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getDerivativeDefinitions($base_plugin_definition) {
    $test_storage = $this->entityTypeManager->getStorage('test');
    $tests = $test_storage->loadByProperties([]);

    foreach ($tests as $test) {
      $route_id = $this->basePluginId . "." . $test->id();

      $this->derivatives[$route_id] = $base_plugin_definition;
      $this->derivatives[$route_id]['title'] = $test->label() . " Results";
      $this->derivatives[$route_id]['route_name'] = "test.test_results";
      $this->derivatives[$route_id]['parent_id'] = "entity.site.tests";
      $this->derivatives[$route_id]['route_parameters'] = [
        // 'site' => $test->site->target_id,
        'test' => $test->id(),
      ];
    }

    return parent::getDerivativeDefinitions($base_plugin_definition);
  }

}
