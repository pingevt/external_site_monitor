<?php

namespace Drupal\esm_test_result_base;

use Drupal\Core\Template\Attribute;

/**
 * Class to handle Status badges for Site Monitor.
 */
class StatusBadge {

  private $testType = "";
  private $label = "";
  private $items = [];
  private $libraries = [
    'esm_test_result_base/status_badge',
  ];

  /**
   * Constructor.
   */
  public function __construct(string $test_type = "") {
    $this->testType = $test_type;
  }

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
   * Add aloibrary to attach to the badge.
   */
  public function addLibrary(string $library) {
    $this->libraries[] = $library;
  }

  /**
   * Build a render array.
   */
  public function renderArray():array {
    $render = [
      '#theme' => 'status_badge',
      '#attached' => ['library' => $this->libraries],
      '#attributes' => [
        'data-test-type' => $this->testType,
      ],
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
