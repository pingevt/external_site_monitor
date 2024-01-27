<?php

namespace Drupal\esm_test_base\Plugin\EsmTestRunner;

use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\esm_test_base\Plugin\EsmTestRunnerBase;
use Drupal\esm_test_base\Plugin\EsmTestRunnerInterface;
use Drupal\esm_test_base\StatusBadge;
use Drupal\esm_test_result_base\Entity\Result;

/**
 * Class WptTestRunner.
 *
 * @EsmTestRunner(
 *   id = "example_test_runner",
 *   test_type = "example_test",
 *   test_result_type = "example_test_result"
 * )
 */
class ExampleTestRunner extends EsmTestRunnerBase implements EsmTestRunnerInterface, ContainerFactoryPluginInterface {

  /**
   * {@inheritdoc}
   */
  public function runTest($test) {
    $score = rand(0, 100);

    // Create a result.
    $result = Result::create([
      'bundle' => $this->pluginDefinition['test_result_type'],
      'title' => "Test Results for " . $test->label(),
      'test' => $test->id(),
      'field_example_test_result_value' => $score,
    ]);

    $result->save();

    $created = new \DateTime("now", $this->utcTz());
    $created->setTimestamp($result->getCreatedTime());

    $test->setNewRevision();
    $test->revision_log = "Test Results";
    $test->last_run = $created->format("Y-m-d\TH:i:s");
    $test->save();
  }

  /**
   * {@inheritdoc}
   */
  public function buildResultsSummary($test, &$build) {

    $result = $this->getMostRecentResult($test);

    $badge = $this->getStatusBadge($result);
    $build['status_' . $result->id()] = $badge->renderArray();
  }

  /**
   * {@inheritdoc}
   */
  public function buildResultsTable($test, &$build) {
    $build['header'] = [
      '#markup' => $test->label() . " Results",
    ];

    $header = ["Label", "Time", "Result"];
    $rows = [];

    $result_storage = $this->entityTypeManager->getStorage('result');
    $results = $result_storage->loadByProperties([
      'test' => $test->id(),
    ]);

    foreach ($results as $result) {

      $cdate = new \Datetime();
      $cdate->setTimestamp($result->created->value);

      $rows[] = [
        $result->label(),
        $cdate->format("Y-m-d H:i:s"),
        $result->field_example_test_result_value->value,
      ];
    }

    $build['table'] = [
      '#type' => 'table',
      '#header' => $header,
      '#rows' => $rows,
      '#empty' => $this->t('No content has been found.'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getStatusBadge(Result $result):StatusBadge {
    $badge = new StatusBadge();
    $badge->addLabel($result->getTitle());

    if ($result->field_example_test_result_value->value != NULL) {
      if ($result->field_example_test_result_value->value <= 50) {
        $badge->addItem("error", $result->field_example_test_result_value->value);
      }
      elseif ($result->field_example_test_result_value->value > 50 && $result->field_example_test_result_value->value <= 75) {
        $badge->addItem("warning", $result->field_example_test_result_value->value);
      }
      elseif ($result->field_example_test_result_value->value > 75 && $result->field_example_test_result_value->value <= 90) {
        $badge->addItem("info", $result->field_example_test_result_value->value);
      }
      elseif ($result->field_example_test_result_value->value > 90) {
        $badge->addItem("success", $result->field_example_test_result_value->value);
      }
    }
    else {
      $badge->addItem("info", "-");
    }

    return $badge;
  }

}
