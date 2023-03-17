<?php

namespace Drupal\esm_test_base\Plugin;

use Drupal\Component\Plugin\PluginInspectionInterface;
use Drupal\esm_test_base\Entity\Test;
use Drupal\esm_test_result_base\Entity\Result;
use Drupal\esm_test_result_base\StatusBadge;

/**
 * Defines an interface for EsmTestRunner plugins.
 */
interface EsmTestRunnerInterface extends PluginInspectionInterface {

  /**
   * Run the given Test.
   */
  public function runTest(Test $test);

  /**
   * Provide render array for a summary.
   */
  public function buildResultsSummary(Test $test, &$build);

  /**
   * Provide render array of results for this test.
   */
  public function buildResultsTable(Test $test, &$build);

  /**
   * Provide status badge data on Given Result.
   */
  public function getStatusBadge(Result $result):StatusBadge;

  /**
   * Provide status badge data on Given Result.
   */
  public function getMostRecentResult(Test $test, array $args = []):?Result;

}
