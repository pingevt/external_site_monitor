<?php

namespace Drupal\esm_test_blc\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemInterface;
use Drupal\Core\Field\Plugin\Field\FieldFormatter\StringFormatter;
use Drupal\esm_test_blc\SiteCrawlerController;

/**
 * Plugin implementation of the 'blc_status' formatter.
 *
 * @FieldFormatter(
 *   id = "blc_status",
 *   label = @Translation("BLC Status"),
 *   field_types = {
 *     "string",
 *   }
 * )
 */
class BlcStatus extends StringFormatter {

  /**
   * {@inheritdoc}
   */
  protected function viewValue(FieldItemInterface $item) {

    // The text value has no text format assigned to it, so the user input
    // should equal the output, including newlines.
    return [
      '#type' => 'inline_template',
      '#template' => '{{ value|nl2br }}',
      '#context' => ['value' => SiteCrawlerController::LABELS[$item->value]],
    ];
  }

}
