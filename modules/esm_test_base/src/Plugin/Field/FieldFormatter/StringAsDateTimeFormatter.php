<?php

namespace Drupal\esm_test_base\Plugin\Field\FieldFormatter;

use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\datetime\Plugin\Field\FieldFormatter\DateTimeDefaultFormatter;
use Drupal\external_site_monitor\DateTimeTrait;

/**
 * Plugin implementation of the String As Datetime formatter.
 *
 * @FieldFormatter(
 *   id = "string_as_datetime",
 *   label = @Translation("String as Datetime"),
 *   field_types = {
 *     "string"
 *   }
 * )
 */
class StringAsDateTimeFormatter extends DateTimeDefaultFormatter {

  use DateTimeTrait;

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      // 'format_type' => 'medium',
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  protected function formatDate($date) {
    $format_type = $this->getSetting('format_type');
    $timezone = $this->getSetting('timezone_override') ?: $date->getTimezone()->getName();
    return $this->dateFormatter->format($date->getTimestamp(), $format_type, '', $timezone != '' ? $timezone : NULL);
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $form = parent::settingsForm($form, $form_state);

    $time = new DrupalDateTime();
    $format_types = $this->dateFormatStorage->loadMultiple();
    $options = [];
    foreach ($format_types as $type => $type_info) {
      $format = $this->dateFormatter->format($time->getTimestamp(), $type);
      $options[$type] = $type_info->label() . ' (' . $format . ')';
    }

    $form['format_type'] = [
      '#type' => 'select',
      '#title' => $this->t('Date format'),
      '#description' => $this->t("Choose a format for displaying the date. Be sure to set a format appropriate for the field, i.e. omitting time for a field that only has a date."),
      '#options' => $options,
      '#default_value' => $this->getSetting('format_type'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = parent::settingsSummary();

    $date = new DrupalDateTime();
    $summary[] = $this->t('Format: @display', ['@display' => $this->formatDate($date)]);

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];

    foreach ($items as $delta => $item) {

      if ($item->value) {
        $d_obj = new \DateTime($item->value, $this->utcTz());
        $item->date = DrupalDateTime::createFromDateTime($d_obj);
        /** @var \Drupal\Core\Datetime\DrupalDateTime $date */
        $date = $item->date;
        $elements[$delta] = $this->buildDateWithIsoAttribute($date);

        if (!empty($item->_attributes)) {
          $elements[$delta]['#attributes'] += $item->_attributes;
          // Unset field item attributes since they have been included in the
          // formatter output and should not be rendered in the field template.
          unset($item->_attributes);
        }
      }
    }

    return $elements;
  }

}
