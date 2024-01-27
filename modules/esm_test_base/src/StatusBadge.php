<?php

namespace Drupal\esm_test_base;

use Drupal\Core\Template\Attribute;

// enum StatusBadgeStatus: string {
//   case Success = 'success';
//   case Info = 'info';
//   case Warning = 'warning';
//   case Error = 'error';
// }

/**
 * Class to handle Status badges for Site Monitor.
 */
class StatusBadge {

  private $badgeType = "";
  private $label = "";
  private $items = [];
  private $libraries = [
    'esm_test_base/status_badge',
  ];

  /**
   * Constructor.
   */
  public function __construct(string $badge_type = "") {
    $this->badgeType = $badge_type;
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

    if ($status instanceof StatusBadgeStatus) {
      $s = $status->value;
    }
    elseif ($status) {
      // @trigger_error('Seting $status as a string is deprecated', \E_USER_DEPRECATED);
      trigger_deprecation("esm_test_base", "1.0.0", "Use the enum");
      $s = $status;
    }

    $this->items[] = [
      'status' => $s,
      'text' => $text,
      'hover' => $hover,
    ];
  }

  /**
   * Add a library to attach to the badge.
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
      '#attributes' => [],
      'badge_data' => [
        'label' => $this->label,
        'items' => [],
        'empty' => "Empty"
      ],
    ];

    if ($this->badgeType) {
      $render['#attributes']['data-badge-type'] = $this->badgeType;
    }

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
