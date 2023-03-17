<?php

namespace Drupal\esm_test_base\Plugin\Validation\Constraint;

use Symfony\Component\Validator\Constraint;

/**
 * Checks that the submitted value is a CRON Expression.
 *
 * @Constraint(
 *   id = "CronExpressionsConstraint",
 *   label = @Translation("CRON Expression constriangt", context = "Validation"),
 *   type = "string"
 * )
 */
class CronExpressionsConstraint extends Constraint {

  /**
   * The message that will be shown if the value is not an integer.
   */
  public $notValid = '%value is not a proper CRON Expression';

}
