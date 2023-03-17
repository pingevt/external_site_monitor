<?php

namespace Drupal\esm_test_base\Plugin\Validation\Constraint;

use Cron\CronExpression;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

/**
 * Validates the CronExpressionsConstraint constriant.
 */
class CronExpressionsConstraintValidator extends ConstraintValidator {

  /**
   * {@inheritdoc}
   */
  public function validate($value, Constraint $constraint) {
    foreach ($value as $item) {
      if (!CronExpression::isValidExpression($item->value)) {
        $this->context->addViolation($constraint->notValid, ['%value' => $item->value]);
      }
    }
  }

}
