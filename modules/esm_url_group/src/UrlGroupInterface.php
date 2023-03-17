<?php

namespace Drupal\esm_url_group;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\user\EntityOwnerInterface;
use Drupal\Core\Entity\EntityChangedInterface;

/**
 * Provides an interface defining an url group entity type.
 */
interface UrlGroupInterface extends ContentEntityInterface, EntityOwnerInterface, EntityChangedInterface {

  /**
   * Gets the url group title.
   *
   * @return string
   *   Title of the url group.
   */
  public function getTitle();

  /**
   * Sets the url group title.
   *
   * @param string $title
   *   The url group title.
   *
   * @return \Drupal\esm_url_group\UrlGroupInterface
   *   The called url group entity.
   */
  public function setTitle($title);

  /**
   * Gets the url group creation timestamp.
   *
   * @return int
   *   Creation timestamp of the url group.
   */
  public function getCreatedTime();

  /**
   * Sets the url group creation timestamp.
   *
   * @param int $timestamp
   *   The url group creation timestamp.
   *
   * @return \Drupal\esm_url_group\UrlGroupInterface
   *   The called url group entity.
   */
  public function setCreatedTime($timestamp);

  /**
   * Returns the url group status.
   *
   * @return bool
   *   TRUE if the url group is enabled, FALSE otherwise.
   */
  public function isEnabled();

  /**
   * Sets the url group status.
   *
   * @param bool $status
   *   TRUE to enable this url group, FALSE to disable.
   *
   * @return \Drupal\esm_url_group\UrlGroupInterface
   *   The called url group entity.
   */
  public function setStatus($status);

}
