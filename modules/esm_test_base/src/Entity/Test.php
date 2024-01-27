<?php

namespace Drupal\esm_test_base\Entity;

use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\RevisionableContentEntityBase;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\esm_test_base\TestInterface;
use Drupal\esm_test_result_base\Entity\Result;
use Drupal\user\UserInterface;
use Drupal\views\Views;

/**
 * Defines the test entity class.
 *
 * @ContentEntityType(
 *   id = "test",
 *   label = @Translation("Site Test"),
 *   label_collection = @Translation("Site Tests"),
 *   bundle_label = @Translation("Site Test type"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\esm_test_base\TestListBuilder",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "access" = "Drupal\esm_test_base\TestAccessControlHandler",
 *     "form" = {
 *       "add" = "Drupal\esm_test_base\Form\TestForm",
 *       "edit" = "Drupal\esm_test_base\Form\TestForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm"
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\external_site_monitor\Entity\Routing\EsmRouteProvider",
 *     }
 *   },
 *   base_table = "test",
 *   data_table = "test_field_data",
 *   revision_table = "test_revision",
 *   revision_data_table = "test_field_revision",
 *   show_revision_ui = TRUE,
 *   translatable = TRUE,
 *   admin_permission = "administer test types",
 *   entity_keys = {
 *     "id" = "id",
 *     "revision" = "revision_id",
 *     "langcode" = "langcode",
 *     "bundle" = "bundle",
 *     "label" = "title",
 *     "uuid" = "uuid"
 *   },
 *   revision_metadata_keys = {
 *     "revision_user" = "revision_uid",
 *     "revision_created" = "revision_timestamp",
 *     "revision_log_message" = "revision_log"
 *   },
 *   links = {
 *     "add-form" = "/test/add/{test_type}",
 *     "add-page" = "/test/add",
 *     "canonical" = "/test/{test}",
 *     "edit-form" = "/test/{test}/edit",
 *     "delete-form" = "/test/{test}/delete",
 *     "collection" = "/admin/site-monitor/test"
 *   },
 *   bundle_entity_type = "test_type",
 *   field_ui_base_route = "entity.test_type.edit_form"
 * )
 */
class Test extends RevisionableContentEntityBase implements TestInterface {

  use EntityChangedTrait;

  /**
   * {@inheritdoc}
   *
   * When a new test entity is created, set the uid entity reference to
   * the current user as the creator of the entity.
   */
  public static function preCreate(EntityStorageInterface $storage_controller, array &$values) {
    parent::preCreate($storage_controller, $values);
    $values += ['uid' => \Drupal::currentUser()->id()];
  }

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
  public function isEnabled() {
    return (bool) $this->get('status')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setStatus($status) {
    $this->set('status', $status);
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
  public function getOwner() {
    return $this->get('uid')->entity;
  }

  /**
   * {@inheritdoc}
   */
  public function getOwnerId() {
    return $this->get('uid')->target_id;
  }

  /**
   * {@inheritdoc}
   */
  public function setOwnerId($uid) {
    $this->set('uid', $uid);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setOwner(UserInterface $account) {
    $this->set('uid', $account->id());
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {

    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['title'] = BaseFieldDefinition::create('string')
      ->setRevisionable(TRUE)
      ->setTranslatable(TRUE)
      ->setLabel(t('Title'))
      ->setDescription(t('The title of the test entity.'))
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

    $fields['status'] = BaseFieldDefinition::create('boolean')
      ->setRevisionable(TRUE)
      ->setLabel(t('Status'))
      ->setDescription(t('A boolean indicating whether the test is enabled.'))
      ->setDefaultValue(TRUE)
      ->setSetting('on_label', 'Enabled')
      ->setDisplayOptions('form', [
        'type' => 'boolean_checkbox',
        'settings' => [
          'display_label' => FALSE,
        ],
        'weight' => 0,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayOptions('view', [
        'type' => 'boolean',
        'label' => 'above',
        'weight' => 0,
        'settings' => [
          'format' => 'enabled-disabled',
        ],
      ])
      ->setDisplayConfigurable('view', TRUE);

    $fields['description'] = BaseFieldDefinition::create('text_long')
      ->setRevisionable(TRUE)
      ->setTranslatable(TRUE)
      ->setLabel(t('Description'))
      ->setDescription(t('A description of the test.'))
      ->setDisplayOptions('form', [
        'type' => 'text_textarea',
        'weight' => 10,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayOptions('view', [
        'type' => 'text_default',
        'label' => 'above',
        'weight' => 10,
      ])
      ->setDisplayConfigurable('view', TRUE);

    $fields['site'] = BaseFieldDefinition::create('entity_reference')
      ->setRevisionable(TRUE)
      ->setTranslatable(TRUE)
      ->setLabel(t('Site'))
      ->setDescription(t('The site ID of the test site.'))
      ->setSetting('target_type', 'site')
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

    $fields['schedule'] = BaseFieldDefinition::create('string')
      ->setRevisionable(TRUE)
      ->setTranslatable(FALSE)
      ->setLabel(t('Schedule'))
      ->setDescription(t('The schedule of the test entity.
<br><br>CRON Expressions
<br>================
<br>
<br>A CRON expression is a string representing the schedule for a particular command to execute.  The parts of a CRON schedule are as follows:
<br><pre>
<br>    *    *    *    *    *
<br>    -    -    -    -    -
<br>    |    |    |    |    |
<br>    |    |    |    |    |
<br>    |    |    |    |    +----- day of week (0 - 7) (Sunday=0 or 7)
<br>    |    |    |    +---------- month (1 - 12)
<br>    |    |    +--------------- day of month (1 - 31)
<br>    |    +-------------------- hour (0 - 23)
<br>    +------------------------- min (0 - 59)
</pre><br>
<br>This library also supports a few macros:
<br>
<br>* `@yearly`, `@annually` - Run once a year, midnight, Jan. 1 - `0 0 1 1 *`
<br>* `@monthly` - Run once a month, midnight, first of month - `0 0 1 * *`
<br>* `@weekly` - Run once a week, midnight on Sun - `0 0 * * 0`
<br>* `@daily`, `@midnight` - Run once a day, midnight - `0 0 * * *`
<br>* `@hourly` - Run once an hour, first minute - `0 * * * *`'))
      ->setRequired(FALSE)
      ->setSetting('max_length', 255)
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => 16,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayOptions('view', [
        'label' => 'hidden',
        'type' => 'string',
        'weight' => 16,
      ])
      ->setDisplayConfigurable('view', TRUE)
      ->addConstraint('CronExpressionsConstraint');

    $fields['last_run'] = BaseFieldDefinition::create('string')
      ->setRevisionable(TRUE)
      ->setTranslatable(FALSE)
      ->setLabel(t('Last Run'))
      ->setDescription(t('The last run of the test entity.'))
      ->setRequired(FALSE)
      ->setSetting('max_length', 40)
      ->setDisplayConfigurable('form', FALSE)
      ->setDisplayConfigurable('view', FALSE);

    $fields['uid'] = BaseFieldDefinition::create('entity_reference')
      ->setRevisionable(TRUE)
      ->setTranslatable(TRUE)
      ->setLabel(t('Author'))
      ->setDescription(t('The user ID of the test author.'))
      ->setSetting('target_type', 'user')
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
        'type' => 'author',
        'weight' => 15,
      ])
      ->setDisplayConfigurable('view', TRUE);

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Authored on'))
      ->setTranslatable(TRUE)
      ->setDescription(t('The time that the test was created.'))
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
   * Get the urls for this test.
   */
  public function getTestingUrls() {

    // Priority goes to the url group.
    if ($this->hasField("field_url_group") && !empty($this->field_url_group) && $this->field_url_group->entity) {
      return $this->field_url_group->entity->field_urls->getValue();
    }

    // Secondarily if we have a urls field.
    if ($this->hasField("field_urls") && !empty($this->field_urls)) {
      return $this->field_urls->getValue();
    }

    // Finally, the url of the site.
    $site = $this->site->entity;
    return $site->field_primary_url->getValue();
  }

  /**
   * Get most recent results of this test.
   */
  public function getMostRecentResult(array $args = []): ?Result {

    $view = Views::getView('test_results');
    $view->setDisplay("latest_result");

    if (!isset($args['test'])) {
      $args['test'] = $this->id();
    }

    if ($args) {
      $view->setExposedInput($args);
    }

    $view->preExecute();
    $view->execute();
    $result = $view->result;

    if (!empty($result) && $result[0]->_entity) {
      return $result[0]->_entity;
    }
    return NULL;
  }

}
