<?php

namespace Drupal\esm_test_base\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBundleBase;

/**
 * Defines the Test type configuration entity.
 *
 * @ConfigEntityType(
 *   id = "test_type",
 *   label = @Translation("Test type"),
 *   handlers = {
 *     "form" = {
 *       "add" = "Drupal\esm_test_base\Form\TestTypeForm",
 *       "edit" = "Drupal\esm_test_base\Form\TestTypeForm",
 *       "delete" = "Drupal\Core\Entity\EntityDeleteForm",
 *     },
 *     "list_builder" = "Drupal\esm_test_base\TestTypeListBuilder",
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     }
 *   },
 *   admin_permission = "administer test types",
 *   bundle_of = "test",
 *   config_prefix = "test_type",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "uuid" = "uuid"
 *   },
 *   links = {
 *     "add-form" = "/admin/structure/test_types/add",
 *     "edit-form" = "/admin/structure/test_types/manage/{test_type}",
 *     "delete-form" = "/admin/structure/test_types/manage/{test_type}/delete",
 *     "collection" = "/admin/structure/test_types"
 *   },
 *   config_export = {
 *     "id",
 *     "label",
 *     "allow_manual",
 *     "uuid",
 *   }
 * )
 */
class TestType extends ConfigEntityBundleBase {

  /**
   * The machine name of this test type.
   *
   * @var string
   */
  protected $id;

  /**
   * The human-readable name of the test type.
   *
   * @var string
   */
  protected $label;

  /**
   * Boolean if this test allows manual entries.
   *
   * @var bool
   */
  protected $allow_manual;

  /**
   *
   */
  public function allowManual() {
    return $this->allow_manual;
  }

}
