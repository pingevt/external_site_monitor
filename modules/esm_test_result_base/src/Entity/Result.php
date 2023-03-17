<?php

namespace Drupal\esm_test_result_base\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\esm_test_result_base\ResultInterface;

/**
 * Defines the result entity class.
 *
 * @ContentEntityType(
 *   id = "result",
 *   label = @Translation("Test Result"),
 *   label_collection = @Translation("Test Results"),
 *   bundle_label = @Translation("Test Result type"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\esm_test_result_base\ResultListBuilder",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "form" = {
 *       "add" = "Drupal\esm_test_result_base\Form\ResultForm",
 *       "edit" = "Drupal\esm_test_result_base\Form\ResultForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm"
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\external_site_monitor\Entity\Routing\EsmRouteProvider",
 *     }
 *   },
 *   base_table = "result",
 *   admin_permission = "administer result types",
 *   entity_keys = {
 *     "id" = "id",
 *     "bundle" = "bundle",
 *     "label" = "title",
 *     "uuid" = "uuid"
 *   },
 *   links = {
 *     "add-form" = "/result/add/{result_type}",
 *     "add-page" = "/result/add",
 *     "canonical" = "/result/{result}",
 *     "edit-form" = "/result/{result}/edit",
 *     "delete-form" = "/result/{result}/delete",
 *     "collection" = "/admin/site-monitor/result"
 *   },
 *   bundle_entity_type = "result_type",
 *   field_ui_base_route = "entity.result_type.edit_form"
 * )
 */
class Result extends ContentEntityBase implements ResultInterface {

  /**
   * {@inheritdoc}
   */
  public function getTitle() {
    return $this->get('title')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setTitle($title) {
    $this->set('title', $title);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getCreatedTime() {
    return $this->get('created')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setCreatedTime($timestamp) {
    $this->set('created', $timestamp);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {

    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['title'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Title'))
      ->setDescription(t('The title of the result entity.'))
      ->setRequired(TRUE)
      ->setSetting('max_length', 255)
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => -5,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayOptions('view', [
        'label' => 'hidden',
        'type' => 'string',
        'weight' => -5,
      ])
      ->setDisplayConfigurable('view', TRUE);

    $fields['test'] = BaseFieldDefinition::create('entity_reference')
      ->setRevisionable(TRUE)
      ->setTranslatable(TRUE)
      ->setLabel(t('Test'))
      ->setDescription(t('The test ID of the test site.'))
      ->setSetting('target_type', 'test')
      ->setDisplayOptions('form', [
        'type' => 'entity_reference_autocomplete',
        'settings' => [
          'match_operator' => 'CONTAINS',
          'size' => 60,
          'placeholder' => '',
        ],
        'weight' => 15,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'entity_reference_label',
        'weight' => 15,
      ])
      ->setDisplayConfigurable('view', TRUE);

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Authored on'))
      ->setDescription(t('The time that the result was created.'))
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'timestamp',
        'weight' => 20,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayOptions('form', [
        'type' => 'datetime_timestamp',
        'weight' => 20,
      ])
      ->setDisplayConfigurable('view', TRUE);
    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Changed'))
      ->setTranslatable(TRUE)
      ->setDescription(t('The time that the test was last edited.'));

    return $fields;
  }

  /**
   * Get Badge data for test.
   */
  public function getStatusBadge() {

    $runnerManager = \Drupal::service('plugin.manager.esm_test_runner');
    $plugin_id = $this->test->entity->bundle() . "_runner";
    $runner = $runnerManager->createInstance($plugin_id);
    return $runner->getStatusBadge($this);
  }

}
