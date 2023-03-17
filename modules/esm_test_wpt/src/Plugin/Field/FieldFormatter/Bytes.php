<?php

namespace Drupal\esm_test_wpt\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\Plugin\Field\FieldFormatter\DecimalFormatter;

/**
 * Plugin implementation of the 'bytes' formatter.
 *
 * Formats a number saved in bytes to proper KB, Mb etc.
 *
 * @FieldFormatter(
 *   id = "bytes",
 *   label = @Translation("Bytes"),
 *   field_types = {
 *     "decimal",
 *     "float"
 *   }
 * )
 */
class Bytes extends DecimalFormatter {

  /**
   * {@inheritdoc}
   */
  protected function numberFormat($number) {

    if ($number == 0) {
      return $number;
    }

    $units = ['B', 'KB', 'MB', 'GB', 'TB', 'PB', 'EB', 'ZB', 'YB'];
    $power = $number > 0 ? floor(log($number, 1024)) : 0;
    return number_format($number / pow(1024, $power), $this->getSetting('scale'), $this->getSetting('decimal_separator'), $this->getSetting('thousand_separator')) . ' ' . $units[$power];
  }

}
