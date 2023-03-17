<?php

namespace Drupal\external_site_monitor;

/**
 * Trait to deal with Site times and Timezones.
 */
trait DateTimeTrait {

  /**
   * Get the UTC DateTimezone object.
   */
  protected static function utcTz() {
    return new \DateTimezone('UTC');
  }

  /**
   * Get the Site DateTimezone object.
   */
  protected static function siteTz() {
    $config = \Drupal::config('system.date');
    $config_data_default_timezone = $config->get('timezone.default');
    return new \DateTimezone($config_data_default_timezone);
  }

  /**
   * Get DateTime object from entity created timestamp.
   */
  protected function getDateTimeFromEntity($entity) {

    $date = new \DateTime("now", $this->utcTz());
    $date->setTimestamp($entity->created->value);

    return $date;
  }

}
