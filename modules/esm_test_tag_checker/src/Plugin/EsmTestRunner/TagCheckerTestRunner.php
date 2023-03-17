<?php

namespace Drupal\esm_test_tag_checker\Plugin\EsmTestRunner;

use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\esm_test_base\Plugin\EsmTestRunnerInterface;
use Drupal\esm_test_base\Plugin\EsmTestRunnerBase;
use Drupal\esm_test_result_base\StatusBadge;
use Drupal\esm_test_result_base\Entity\Result;

/**
 * Class TagCheckerTestRunner runs the tag checker test.
 *
 * @EsmTestRunner(
 *   id = "tag_checker_test_runner",
 *   test_type = "tag_checker_test",
 *   test_result_type = "tag_checker_test_result"
 * )
 */
class TagCheckerTestRunner extends EsmTestRunnerBase implements EsmTestRunnerInterface, ContainerFactoryPluginInterface {

  /**
   * {@inheritdoc}
   */
  public function runTest($test) {

    // Grab config.
    $config = $this->configFactory->get('esm_test_tag_checker.settings');

    // Set Time for this report.
    $created = new \DateTime(NULL, $this->utcTz());
    $created->setTimezone($this->utcTz());
    $timestamp = $created->format("Ymd-His");

    // Prepare Directory.
    $target_dir = $config->get('dir') . "/" . $timestamp;
    $this->fileSystem->prepareDirectory($target_dir, FileSystemInterface::CREATE_DIRECTORY);

    // Grab test URLs.
    $test_url_string_arr = array_map(
      function ($el) {
        return $el['uri'];
      },
      $test->getTestingUrls()
    );

    // Prepare results entities.
    $results_entities = [];
    foreach ($test_url_string_arr as $url) {
      $result = Result::create([
        'bundle' => $this->pluginDefinition['test_result_type'],
        'created' => $created->getTimestamp(),
        'title' => "Test Results for " . $test->label(),
        'test' => $test->id(),
        'field_url' => $url,
      ]);
      $result->save();

      $ch = curl_init($url);
      curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
      curl_setopt($ch, CURLOPT_FOLLOWLOCATION, TRUE);
      curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'User-Agent: Curl/2000',
      ]);

      $response = curl_exec($ch);

      $response_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);

      if ($errno = curl_errno($ch)) {
        $this->loggerFactory->get('esm:tag_checker')->debug("Response Code: @code", ["@code" => $response_code]);
        $error_message = curl_strerror($errno);
        $this->loggerFactory->get('esm:tag_checker')->error("cURL erro ({@errno}): @err", [
          "@errno" => $errno,
          "@err" => $error_message,
        ]);
      }

      // Close the connection, release resources used.
      curl_close($ch);

      $result_data = $this->parseHtmlFile($response);

      $result->field_meta_tag_count = count($result_data['meta_tags']);

      $id_count = 0;
      array_walk($result_data['ids'], function (&$item, $key) use ($id_count) {
        if ($item['count'] > 1) {
          $id_count++;
        }
      });

      $result->field_duplicate_ids = $id_count;

      $result->field_h1_tags = count($result_data['h1']);
      $result->field_h2_tags = count($result_data['h2']);
      $result->field_h3_tags = count($result_data['h3']);
      $result->field_h4_tags = count($result_data['h4']);
      $result->field_h5_tags = count($result_data['h5']);
      $result->field_h6_tags = count($result_data['h6']);

      // Save JSON to file.
      $dest_full_uri = $target_dir . "/data--" . $result->id() . ".json";
      $file_uri = $this->saveJsonToFile(json_encode($result_data), $dest_full_uri);

      $file = $this->createFile($file_uri, $result, "esm_test_tag_checker", "result");

      $result->field_tc_json_report = $file->id();

      $result->save();

      $results_entities[] = $result;

      // Update Test with lastupdate time.
      $created = new \DateTime(NULL, $this->utcTz());
      $created->setTimestamp($result->getCreatedTime());

      $test->setNewRevision();
      $test->revision_log = "Test Results";
      $test->last_run = $created->format("Y-m-d\TH:i:s");
      $test->save();
    }
  }

  /**
   * Process HTML.
   */
  protected function parseHtmlFile($html) {
    $data = [];

    $dom = new \DOMDocument();
    @$dom->loadHTML($html);

    $head = $dom->getElementsByTagName("head")->item(0);

    // Check title tag.
    $title_tags = $head->getElementsByTagName("title");
    $data['title'] = [];
    foreach ($title_tags as $t) {
      $data['title'][] = $t->textContent;
    }

    // Check Meta Tags.
    $meta_tags = $dom->getElementsByTagName("meta");
    $data['meta_tags'] = [];
    foreach ($meta_tags as $tag) {
      $data['meta_tags'][] = $this->dom2Array($tag);
    }

    // Check for structured data.
    $xpath = new \DomXpath($dom);
    $structured_data = $xpath->query('//script[@type="application/ld+json"]');
    $data['structured_data'] = [];
    foreach ($structured_data as $sd) {
      $data['structured_data'][] = $this->dom2Array($sd);
    }

    // Check H tags.
    for ($h = 1; $h <= 6; $h++) {
      $htags = $dom->getElementsByTagName("h" . $h);
      $data['h' . $h] = [];
      foreach ($htags as $tag) {
        $tag_array = $this->dom2Array($tag);
        $tag_array['_lineNo'] = $tag->getLineNo();
        $tag_array['_nodePath'] = $tag->getNodePath();
        $data['h' . $h][] = $tag_array;
      }
    }

    // Check for ids.
    $xpath = new \DomXpath($dom);
    $ids = $xpath->query('//*[@id]');
    $ids_to_check = [];
    $data['ids'] = [];
    foreach ($ids as $id) {
      $tag = $this->dom2Array($id);

      if (!isset($data['ids'][$tag['_attributes']['id']])) {
        $data['ids'][$tag['_attributes']['id']] = [
          'tags' => [],
          'count' => 0,
        ];
      }
      $data['ids'][$tag['_attributes']['id']]['tags'][] = $tag;
    }

    array_walk($data['ids'], function (&$item, $key) use ($data) {
      // $c = count($item['tags']);
      // if ($c > 1) {
        $item['count'] = count($item['tags']);
      // }
    });

    return $data;
  }

  /**
   * {@inheritdoc}
   */
  public function buildResultsSummary($test, &$build) {
    foreach ($test->getTestingUrls() as $url_field_data) {
      if ($result = $this->getMostRecentResult($test, ['url' => $url_field_data['uri']])) {
        $badge = $this->getStatusBadge($result);
        $build['status_' . $result->id()] = $badge->renderArray();
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function buildResultsTable($test, &$build) {

    $build = [
      '#theme' => 'site_test_results',
      '#test' => $test,
      '#title' => $test->label() . " Results",
    ];

    $views = [];
    foreach ($test->getTestingUrls() as $url_field_data) {
      $views[] = [

        '#attributes' => [
          'class' => ["o-url-result"],
        ],
        "group" => [
          [
            '#attributes' => [
              'class' => ["o-url-result--table"],
            ],
            'view' => views_embed_view("tag_checker_test_results", "base", $test->id(), $url_field_data['uri']),
          ],
        ],
      ];
    }

    $build['#content'] = [
      "data" => $views,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getStatusBadge(Result $result):StatusBadge {
    $badge = new StatusBadge();

    $badge->addLabel(str_replace(['https://', "http://"], "", $result->field_url->uri));
    $badge->addItem("info", $result->field_meta_tag_count->value, "Meta Tag Count");

    $dup_id_c = $result->field_duplicate_ids->value;
    if ($dup_id_c > 0) {
      $badge->addItem("error", $dup_id_c, "Duplicate Ids");
    }
    else {
      $badge->addItem("success", $dup_id_c, "Duplicate Ids");
    }

    return $badge;
  }

  /**
   * {@inheritdoc}
   */
  protected function dom2Array($root) {
    $array = [];

    // List attributes.
    if ($root->hasAttributes()) {
      foreach ($root->attributes as $attribute) {
        $array['_attributes'][$attribute->name] = $attribute->value;
      }
    }

    // Handle classic node.
    if ($root->nodeType == XML_ELEMENT_NODE) {
      $array['_type'] = $root->nodeName;
      if ($root->hasChildNodes()) {
        $children = $root->childNodes;
        for ($i = 0; $i < $children->length; $i++) {
          $child = $this->dom2Array($children->item($i));

          // don't keep textnode with only spaces and newline.
          if (!empty($child)) {
            $array['_children'][] = $child;
          }
        }
      }

      // Handle text node.
    }
    elseif ($root->nodeType == XML_TEXT_NODE || $root->nodeType == XML_CDATA_SECTION_NODE) {
      $value = $root->nodeValue;
      if (!empty($value)) {
        $array['_type'] = '_text';
        $array['_content'] = $value;
      }
    }

    return $array;
  }

}
