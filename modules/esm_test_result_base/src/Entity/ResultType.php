<?php

namespace Drupal\esm_test_result_base\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBundleBase;

/**
 * Defines the Result type configuration entity.
 *
 * @ConfigEntityType(
 *   id = "result_type",
 *   label = @Translation("Test Result type"),
 *   handlers = {
 *     "form" = {
 *       "add" = "Drupal\esm_test_result_base\Form\ResultTypeForm",
 *       "edit" = "Drupal\esm_test_result_base\Form\ResultTypeForm",
 *       "delete" = "Drupal\Core\Entity\EntityDeleteForm",
 *     },
 *     "list_builder" = "Drupal\esm_test_result_base\ResultTypeListBuilder",
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     }
 *   },
 *   admin_permission = "administer result types",
 *   bundle_of = "result",
 *   config_prefix = "result_type",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "uuid" = "uuid"
 *   },
 *   links = {
 *     "add-form" = "/admin/structure/result_types/add",
 *     "edit-form" = "/admin/structure/result_types/manage/{result_type}",
 *     "delete-form" = "/admin/structure/result_types/manage/{result_type}/delete",
 *     "collection" = "/admin/structure/result_types"
 *   },
 *   config_export = {
 *     "id",
 *     "label",
 *     "uuid",
 *   }
 * )
 */
class ResultType extends ConfigEntityBundleBase {

  /**
   * The machine name of this result type.
   *
   * @var string
   */
  protected $id;

  /**
   * The human-readable name of the result type.
   *
   * @var string
   */
  protected $label;

}
