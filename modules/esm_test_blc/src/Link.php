<?php

namespace Drupal\esm_test_blc;

use Drupal\Core\File\FileSystemInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;

/**
 * Page class.
 */
class Link {

  const LT_UNDECIDED = 0;
  const LT_PAGE = 1;
  const LT_SITEMAP = 2;
  const LT_ASSET = 3;
  const LT_EXT_PAGE = 4;

  public $id = NULL;
  public $result = NULL;
  public $full_url = "";
  public $checked = 0;
  public $link_type = self::LT_UNDECIDED;
  public $external = 0;
  public $http_status = NULL;
  public $data = [
    'parents' => [],
    'effective_url' => "",
    'headers' => [],
    'on_page_links' => [],
  ];

  static function create(array $data) {
    return new Link($data);
  }

  static function load(int $id) {

    $results = Link::loadByProperties(['id' => $id], [], 1, FALSE, TRUE);

    return new Link((array) $results[0]);
  }

  static function loadByProperties(array $props, $order_by = [], $limit = NULL, $count = FALSE, $full = FALSE) {
    $query = Link::getDatabase()->select("esm_blc_link", "l");

    if ($full) {
      $query->fields('l');
    }
    else {
      $query->fields('l', ['id']);
    }

    foreach ($props as $field => $value) {
      if (is_array($value)) {
        $query->condition($field, $value[0], $value[1]);
      }
      else {
        $query->condition($field, $value);
      }
    }

    if (!empty($order_by)) {
      foreach ($order_by as $o) {
        $query->orderBy($o[0], $o[1]);
      }
    }

    if (!empty($limit)) {
      $query->range(0, $limit);
    }

    if ($count) {
      return $query->countQuery()->execute()->fetchField();
    }

    $result = $query->execute()->fetchAll();

    // Return result IDs
    return $result;
  }

  static function addInBulk(array $links) {

    $dbConn = Link::getDatabase();

    $insert = $dbConn->insert("esm_blc_link");
    $insert->fields(['result', 'full_url', 'checked', 'link_type', 'external', 'http_status', 'data']);

    foreach ($links as $l) {
      $l['data'] = serialize($l['data']);
      $insert->values($l);
    }

    $r = $insert->execute();
  }

  /**
   * Constructor.
   */
  function __construct(array $data) {
    foreach ($data as $k => $v) {
      switch ($k) {
        case "id":
        case "result":
        case "checked":
        case "link_type":
        case "external":
          $this->{$k} = (int) $v;
          break;

        case "http_status":
          $this->{$k} = is_null($v) ? NULL : (int) $v;
          break;

        case "data":
          $this->{$k} = is_array($v) ? array_merge_recursive($this->data, $v) : unserialize($v);
          break;

        default:
          $this->{$k} = $v;
      }
    }
  }

  /**
   * Merge data from a previous link.
   */
  public function mergeLinkData($link) {

    // Update Parents.
    if (isset($link['data']['parents'])) {
      foreach ($link['data']['parents'] as $p) {
        $this->addParent($p);
      }
    }

    // Update Headers. @todo: ??

    // Update on_page_links. @todo: ??

    $this->save();
  }

  public function setChecked($c) {
    $this->checked = $c;
  }

  public function setLinkType($type) {
    $this->link_type = $type;
  }

  public function setHttpStatus($status) {
    $this->http_status = $status;
  }

  public function setHeaders($headers) {
    $this->data['headers'] = $headers;
  }

  public function setEffectiveUrl($ef_url) {
    $this->data['effective_url'] = $ef_url;
  }

  public function addParent($parent) {
    if (count($this->data['parents']) <= 50) {
      $this->data['parents'][] = $parent;
    }
    elseif (count($this->data['parents']) == 51) {
      $this->data['parents'][] = "...";
    }
  }

  public function addLink($link) {
    $this->data['on_page_links'][] = $link;
  }

  public function setRedirectLocation($loc) {
    $this->data['redirect_loc'] = $loc;
  }

  /**
   * Save the link.
   */
  public function save() {
    $dbConn = Link::getDatabase();

    if (is_null($this->id)) {
      $insert = $dbConn->insert("esm_blc_link");
      $insert->fields([
        'result',
        'full_url',
        'checked',
        'link_type',
        'external',
        'http_status',
        'data',
      ]);
      $insert->values([
        'result' => $this->result,
        'full_url' => $this->full_url,
        'checked' => $this->checked,
        'link_type' => $this->link_type,
        'external' => $this->external,
        'http_status' => $this->http_status,
        'data' => serialize($this->data),
      ]);

      $this->id = $insert->execute();
    }
    else {
      $update = $dbConn->update("esm_blc_link");
      $update->fields([
        'result' => $this->result,
        'full_url' => $this->full_url,
        'checked' => $this->checked,
        'link_type' => $this->link_type,
        'external' => $this->external,
        'http_status' => $this->http_status,
        'data' => serialize($this->data),
      ]);
      $update->condition('id', $this->id);

      $update->execute();
    }
  }

  /**
   * Delete the link.
   */
  public function delete() {
    $dbConn = Link::getDatabase();

    $delete = $dbConn->delete("esm_blc_link");
    $delete->condition('id', $this->id);
    $delete->execute();
  }









  /**
   * Get the db connection.
   */
  public static function getDatabase() {
    return \Drupal::service('database');
  }

}
