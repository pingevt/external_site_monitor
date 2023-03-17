<?php

namespace Drupal\esm_test_base\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Annotation class.
 *
 * @package Drupal\esm_test_base\Annotation
 *
 * @Annotation
 */
class EsmTestRunner extends Plugin {

  /**
   * The plugin ID.
   *
   * @var string
   */
  public $id;

  /**
   * The bundle of Test.
   *
   * @var string
   */
  public $test_type;

  /**
   * The bundle of test result.
   *
   * @var string
   */
  public $test_result_type;

}
