<?php

namespace Drupal\external_site_monitor;

/**
 * Trait to handle EntityTypeBundleInfo.
 */
trait EntityTypeBundleTrait {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeBundleInfo
   */
  protected $entityTypeBundleInfo;

  /**
   * Sets the entity type manager.
   *
   * @param \Drupal\Core\Entity\EntityTypeBundleInfo $entityTypeBundleInfo
   *   The entity type manager.
   */
  public function setEntityTypeBundleInfo(EntityTypeBundleInfo $entityTypeBundleInfo) {
    $this->entityTypeBundleInfo = $entityTypeBundleInfo;
  }

  /**
   * Gets the entity type manager.
   *
   * @return \Drupal\Core\Entity\EntityTypeBundleInfo
   *   The entity type manager.
   */
  public function entityTypeBundleInfo() {
    if (!isset($this->entityTypeBundleInfo)) {
      $this->entityTypeBundleInfo = \Drupal::service('entity_type.bundle.info');
    }
    return $this->entityTypeBundleInfo;
  }

}
