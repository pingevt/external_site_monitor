<?php

namespace Drupal\esm_site;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\user\EntityOwnerInterface;
use Drupal\Core\Entity\EntityChangedInterface;

/**
 * Provides an interface defining a site entity type.
 */
interface SiteInterface extends ContentEntityInterface, EntityOwnerInterface, EntityChangedInterface {

  /**
   * Gets the site title.
   *
   * @return string
   *   Title of the site.
   */
  public function getTitle();

  /**
   * Sets the site title.
   *
   * @param string $title
   *   The site title.
   *
   * @return \Drupal\esm_site\SiteInterface
   *   The called site entity.
   */
  public function setTitle($title);

  /**
   * Gets the site creation timestamp.
   *
   * @return int
   *   Creation timestamp of the site.
   */
  public function getCreatedTime();

  /**
   * Sets the site creation timestamp.
   *
   * @param int $timestamp
   *   The site creation timestamp.
   *
   * @return \Drupal\esm_site\SiteInterface
   *   The called site entity.
   */
  public function setCreatedTime($timestamp);

  /**
   * Returns the site status.
   *
   * @return bool
   *   TRUE if the site is enabled, FALSE otherwise.
   */
  public function isEnabled();

  /**
   * Sets the site status.
   *
   * @param bool $status
   *   TRUE to enable this site, FALSE to disable.
   *
   * @return \Drupal\esm_site\SiteInterface
   *   The called site entity.
   */
  public function setStatus($status);

}
