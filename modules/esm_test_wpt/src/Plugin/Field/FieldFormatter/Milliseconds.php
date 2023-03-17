<?php

namespace Drupal\esm_test_wpt\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\Plugin\Field\FieldFormatter\DecimalFormatter;

/**
 * Plugin implementation of the 'milliseconds' formatter.
 *
 * Formats a number saved in milliseconds to proper KB, Mb etc.
 *
 * @FieldFormatter(
 *   id = "milliseconds",
 *   label = @Translation("Milliseconds"),
 *   field_types = {
 *     "decimal",
 *     "float"
 *   }
 * )
 */
class Milliseconds extends DecimalFormatter {

  /**
   * {@inheritdoc}
   */
  protected function numberFormat($number) {
    $number = $number / 1000;
    return number_format($number, $this->getSetting('scale'), $this->getSetting('decimal_separator'), $this->getSetting('thousand_separator')) . "s";
  }

}
