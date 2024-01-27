<?php

namespace Drupal\esm_test_base;

use Drupal\esm_test_base\Plugin\EsmTestRunnerManager;

trait EsmTestRunnerTrait {

  /**
   * Runner Plugin Manager.
   *
   * @var Drupal\esm_test_base\Plugin\EsmTestRunnerManager
   */
  private $runnerManager;

  /**
   * Sets the entity type manager.
   *
   * @param \Drupal\esm_test_base\Plugin\EsmTestRunnerManager $runnerManager
   *   The entity type manager.
   */
  public function setEsmTestRunnerManager(EsmTestRunnerManager $runnerManager) {
    $this->runnerManager = $runnerManager;
  }

  /**
   * Gets the entity type manager.
   *
   * @return \Drupal\esm_test_base\Plugin\EsmTestRunnerManager $runnerManager
   *   The entity type manager.
   */
  public function esmTestRunnerManager() {
    if (!isset($this->runnerManager)) {
      $this->runnerManager = \Drupal::service('plugin.manager.esm_test_runner');
    }
    return $this->runnerManager;
  }

}
