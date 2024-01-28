<?php

namespace Drupal\external_site_monitor;

use Drupal\Core\Database\Connection;

/**
 * Trait to handle the Database connection.
 */
trait DatabaseTrait {

  /**
   * Database Connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  private $database;

  /**
   * Sets the database connection.
   *
   * @param \Drupal\Core\Database\Connection
   *   The database connection.
   */
  public function setDatabase(Connection $database) {
    $this->database = $database;
  }

  /**
   * Gets the database connection.
   *
   * @return \Drupal\Core\Database\Connection
   *   The database connection.
   */
  public function database() {
    if (!isset($this->database)) {
      $this->database = \Drupal::service('database');
    }
    return $this->database;
  }

}
