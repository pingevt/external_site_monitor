<?php

namespace Drupal\esm_url_group;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityViewBuilder;

/**
 * Provides a view controller for an url group entity type.
 */
class UrlGroupViewBuilder extends EntityViewBuilder {

  /**
   * {@inheritdoc}
   */
  protected function getBuildDefaults(EntityInterface $entity, $view_mode) {
    $build = parent::getBuildDefaults($entity, $view_mode);
    // The url group has no entity template itself.
    unset($build['#theme']);
    return $build;
  }

}
