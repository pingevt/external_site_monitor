<?php

namespace Drupal\esm_test_base;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\user\EntityOwnerInterface;
use Drupal\Core\Entity\EntityChangedInterface;

/**
 * Provides an interface defining a test entity type.
 */
interface TestInterface extends ContentEntityInterface, EntityOwnerInterface, EntityChangedInterface {

  /**
   * Gets the test title.
   *
   * @return string
   *   Title of the test.
   */
  public function getTitle();

  /**
   * Sets the test title.
   *
   * @param string $title
   *   The test title.
   *
   * @return \Drupal\esm_test_base\TestInterface
   *   The called test entity.
   */
  public function setTitle($title);

  /**
   * Gets the test creation timestamp.
   *
   * @return int
   *   Creation timestamp of the test.
   */
  public function getCreatedTime();

  /**
   * Sets the test creation timestamp.
   *
   * @param int $timestamp
   *   The test creation timestamp.
   *
   * @return \Drupal\esm_test_base\TestInterface
   *   The called test entity.
   */
  public function setCreatedTime($timestamp);

  /**
   * Returns the test status.
   *
   * @return bool
   *   TRUE if the test is enabled, FALSE otherwise.
   */
  public function isEnabled();

  /**
   * Sets the test status.
   *
   * @param bool $status
   *   TRUE to enable this test, FALSE to disable.
   *
   * @return \Drupal\esm_test_base\TestInterface
   *   The called test entity.
   */
  public function setStatus($status);

}
