<?php

namespace Drupal\esm_test_result_base;

use Drupal\Core\Template\Attribute;

/**
 * Class to handle Status badges for Site Monitor.
 */
class StatusBadge {

  private $label = "";
  private $items = [];

  /**
   * Add label to badge.
   */
  public function addLabel($label) {
    $this->label = $label;
  }

  /**
   * Add an items to the badge.
   */
  public function addItem($status, $text, $hover = "") {
    $this->items[] = [
      'status' => $status,
      'text' => $text,
      'hover' => $hover,
    ];
  }

  /**
   * Build a render array.
   */
  public function renderArray():array {
    $render = [
      '#theme' => 'status_badge',
      '#attached' => ['library' => ["esm_test_result_base/status_badge"]],
      'badge_data' => [
        'label' => $this->label,
        'items' => [],
      ],
    ];

    foreach ($this->items as $item) {
      $attr = [
        'class' => [
          'sb-item',
          'sb-item--' . $item['status'],
        ],
      ];

      if ($item['hover']) {
        $attr['title'] = $item['hover'];
      }

      $render['badge_data']['items'][] = [
        'status' => $item['status'],
        'text' => $item['text'],
        'attributes' => new Attribute($attr),
      ];
    }
    return $render;
  }

}
