<?php

namespace Drupal\esm_site\Commands;

use Consolidation\OutputFormatters\StructuredData\ListDataFromKeys;
use Consolidation\OutputFormatters\StructuredData\RowsOfFields;
use Drush\Commands\DrushCommands;
use Drupal\Core\Entity\EntityTypeManager;

/**
 * Undocumented class
 */
class SiteCommands extends DrushCommands {

  var $entityTypeManager;

  public function __construct(EntityTypeManager $entity_type_manager) {
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * List Sites.
   *
   * @command esm:site:list
   * @field-labels
   *   id: ID
   *   label: Label
   *   status: Status
   *   type: Type(s)
   * @default-fields id,label,status,type
   * @usage esm:site:list
   * @return \Consolidation\OutputFormatters\StructuredData\RowsOfFields
   */
  public function listSites() {
    $sites_table = [];
    $site_storage = $this->entityTypeManager->getStorage('site');
    $sites = $site_storage->loadByProperties([]);

    foreach ($sites as $site) {
      $types = [];
      foreach ($site->field_site_type->referencedEntities() as $term) {
        $types[] = $term->label();
      }

      $sites_table[] = [
        'id' => $site->id(),
        'label' => $site->label(),
        'status' => $site->isEnabled() ? "Enabled" : "Disabled",
        'type' => implode(", ", $types),
      ];
    }

    return new RowsOfFields($sites_table);
  }

  /**
   * Enable Site
   *
   * @command esm:site:enable
   * @param $site_id The id of the site.
   */
  public function enableSite($site_id) {
    $site_storage = $this->entityTypeManager->getStorage('site');
    $site = current($site_storage->loadByProperties(['id' => $site_id]));

    if (empty($site)) {
      $this->logger()->warning('There is no Site by that id.');
      return;
    }

    if ($site->id() != $site_id) {
      $this->logger()->warning('Somethign is wrong');
      return;
    }

    $this->output()->writeln(dt('The following site will be enabled: !id => !label', ['!id' => $site->id(), '!label' => $site->label()]));
    if (!$this->io()->confirm(dt('Do you want to continue?'))) {
        throw new UserAbortException();
    }

    $site->setStatus(1);
    $site->save();

    $this->logger()->success(dt('Successfully enabled: !id => !label', ['!id' => $site->id(), '!label' => $site->label()]));
  }

  /**
   * Disable Site
   *
   * @command esm:site:disable
   * @param $site_id The id of the site.
   */
  public function disableSite($site_id) {
    $site_storage = $this->entityTypeManager->getStorage('site');
    $site = current($site_storage->loadByProperties(['id' => $site_id]));

    if (empty($site)) {
      $this->logger()->warning('There is no Site by that id.');
      return;
    }

    if ($site->id() != $site_id) {
      $this->logger()->warning('Somethign is wrong');
      return;
    }

    $this->output()->writeln(dt('The following site will be disabled: !id => !label', ['!id' => $site->id(), '!label' => $site->label()]));
    if (!$this->io()->confirm(dt('Do you want to continue?'))) {
        throw new UserAbortException();
    }

    $site->setStatus(0);
    $site->save();

    $this->logger()->success(dt('Successfully disabled: !id => !label', ['!id' => $site->id(), '!label' => $site->label()]));
  }

}
