<?php

namespace Drupal\esm_test_result_base;

use Drupal\Core\Entity\ContentEntityInterface;

/**
 * Provides an interface defining a result entity type.
 */
interface ResultInterface extends ContentEntityInterface {

  /**
   * Gets the result title.
   *
   * @return string
   *   Title of the result.
   */
  public function getTitle();

  /**
   * Sets the result title.
   *
   * @param string $title
   *   The result title.
   *
   * @return \Drupal\esm_test_result_base\ResultInterface
   *   The called result entity.
   */
  public function setTitle($title);

  /**
   * Gets the result creation timestamp.
   *
   * @return int
   *   Creation timestamp of the result.
   */
  public function getCreatedTime();

  /**
   * Sets the result creation timestamp.
   *
   * @param int $timestamp
   *   The result creation timestamp.
   *
   * @return \Drupal\esm_test_result_base\ResultInterface
   *   The called result entity.
   */
  public function setCreatedTime($timestamp);

}
