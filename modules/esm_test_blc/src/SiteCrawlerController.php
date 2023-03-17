<?php

namespace Drupal\esm_test_blc;

use Drupal\Core\File\FileSystemInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;
use Drupal\esm_test_result_base\Entity\Result;
use Drupal\esm_test_base\Dom2ArrayTrait;

/**
 * SiteCrawlerController class.
 */
class SiteCrawlerController {

  use Dom2ArrayTrait;

  // Class Constants.
  const NEEDS_ROOT = -50;
  const NEEDS_SITEMAP = -10;
  const PROCCESSING_SITEMAP = -8;
  const WORKING = -2;
  const FINISHED = 1;

  const LABELS = [
    self::NEEDS_ROOT => "Needs Root",
    self::NEEDS_SITEMAP => "Needs Sitemap",
    self::PROCCESSING_SITEMAP => "Processing Sitemap",
    self::WORKING => "Working...",
    self::FINISHED => "Finished",
  ];

  private $result = NULL;
  protected $baseUrl = "";
  protected $scheme = "";
  protected $host = "";
  protected $status = NULL;

  /**
   * Start time of the run.
   *
   * @var string
   */
  protected $startTime = 0;

  static function create(Result $result) {
    return new SiteCrawlerController($result);
  }

  /**
   * Constructor.
   */
  function __construct(Result $result) {
    $this->result = $result;

    // set a few more things.
    $this->baseUrl = $this->getBaseUrl();
    $this->scheme = parse_url($this->baseUrl, PHP_URL_SCHEME);
    $this->host = parse_url($this->baseUrl, PHP_URL_HOST);
    $this->status = $this->getStatusFromResult();
  }

  /**
   * Grab the base URL of this test Result.
   */
  private function getBaseUrl():string|NULL {
    return $this->result->field_url->uri;
  }

  /**
   * Grab the status of this test Result.
   */
  private function getStatusFromResult() {
    return is_null($this->result->field_blc_status->value) ? self::NEEDS_ROOT : (int) $this->result->field_blc_status->value;
  }

  /**
   * Set the current Status.
   */
  protected function setStatus($status) {
    // @todo: check that this is a valid status.
    $this->status = $status;
  }

  /**
   * Get the current Status.
   */
  public function getStatus() {
    return $this->status;
  }

  public function getPageCount() {
    return ($this->getPages([], TRUE));
  }

  public function getPages($order_by = [], $count = FALSE) {
    $results = Link::loadByProperties([
      'result' => $this->result->id(),
      'link_type' => Link::LT_PAGE,
      'external' => 0,
    ], $order_by, NULL, $count);

    return $results;
  }

  public function getLinkCount() {
    return ($this->getLinks([], TRUE));
  }

  public function getLinks($order_by = [], $count = FALSE) {
    $results = Link::loadByProperties([
      'result' => $this->result->id(),
      'external' => 0,
    ], $order_by, NULL, $count);

    return $results;
  }

  public function getProblemLinkCount() {
    return ($this->getProblemLinks([], TRUE));
  }

  public function getProblemLinks($order_by = [], $count = FALSE) {

    // $results = Link::loadByProperties([
    //   'result' => $this->result->id(),
    //   'http_status' => [300, ">="],
    // ], $order_by);

    $props = [
      'result' => $this->result->id(),
      'http_status' => [300, ">="],
    ];

    $query = Link::getDatabase()->select("esm_blc_link", "l");
    $query->fields('l');

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

    $query->range(0, 10);

    if ($count) {
      return $query->countQuery()->execute()->fetchField();
    }

    $result = $query->execute();

    $results = [];
    foreach ($result as $record) {
      $results[] = Link::create((array) $record);
    }

    return $results;
  }

  public function getUnprocessedLinksCount() {
    return ($this->getUnprocessedLinks([], TRUE));
  }

  public function getUnprocessedLinks($order_by = []) {

    $results = Link::loadByProperties([
      'result' => $this->result->id(),
      'checked' => [0, "<="],
    ], $order_by, NULL, $count);

    return $results;
  }

  public function getProcessedLinksCount() {
    return ($this->getProcessedLinks([], TRUE));
  }

  public function getProcessedLinks($order_by = [], $count = FALSE) {

    $results = Link::loadByProperties([
      'result' => $this->result->id(),
      'checked' => [0, ">"],
    ], $order_by, NULL, $count);

    return $results;
  }

  public function getAllLinksCount() {
    return ($this->getAllLinks([], TRUE));
  }

  public function getAllLinks($order_by = [], $count = FALSE) {

    $results = Link::loadByProperties([
      'result' => $this->result->id(),
    ], $order_by, NULL, $count);

    return $results;
  }

  /**
   * Run. Process the data.
   */
  public function run() {
    if ($this->status === self::FINISHED) {
      return;
    }

    // Set Start Time.
    $this->startTime = time();

    $total_count = 0;

    switch($this->status) {
      case (self::NEEDS_ROOT):
        $link_data = [
          'result' => $this->result->id(),
          'full_url' => $this->baseUrl,
          'checked' => 0,
          'link_type' => Link::LT_PAGE,
          'external' => 0,
          'http_status' => NULL,
          'data' => [
            'parents' => ["**original**"],
          ],
        ];

        $this->addLink($this->baseUrl, $link_data);

        // Move On!
        $this->setStatus(self::NEEDS_SITEMAP);
        break;

      case (self::NEEDS_SITEMAP):
        // ksm("Need to check for sitemaps");

        // Default to '/sitemap.xml'.
        $sitemapurl = rtrim($this->baseUrl, "/") . "/sitemap.xml";

        $link_data = [
          'result' => $this->result->id(),
          'full_url' => $sitemapurl,
          'checked' => 0,
          'link_type' => Link::LT_SITEMAP,
          'external' => 0,
          'http_status' => NULL,
          'data' => [
            'parents' => ["**original**"],
          ],
        ];

        $this->addLink($sitemapurl, $link_data);

        // Move On!
        $this->setStatus(self::PROCCESSING_SITEMAP);

        break;

      case (self::PROCCESSING_SITEMAP):
        // ksm("PROCCESSING_SITEMAP");
        $time_cap = 10;

        if ($this->hasUnprocessedSitemaps()) {
          $count = 0;
          while ((time() - $this->startTime) <= $time_cap && $count <= 15) {
            $this->processSitemap();
            $count++;
          }
        }

        //  Move status if we can.
        if (!$this->hasUnprocessedSitemaps()) {
          $this->setStatus(self::WORKING);
        }
        break;

      case (self::WORKING):
        // ksm("keep working");
        $time_cap = 12;

        // Process non-slow URLs.
        if ($this->hasUnprocessedPages()) {
          $count = 0;
          while ((time() - $this->startTime) <= $time_cap) {
            $this->processPage([Link::LT_PAGE]);
            $count++;
            $total_count++;
          }
        }

        // Process non-slow links.
        if ($this->hasUnprocessedLinks()) {
          $count = 0;
          while ((time() - $this->startTime) <= $time_cap) {
            $this->processPage([Link::LT_UNDECIDED, Link::LT_ASSET]);
            $count++;
            $total_count++;
          }
        }

        // Process slow URLs.
        if ($this->hasUnprocessedSlowPages()) {
          $count = 0;
          while ((time() - $this->startTime) <= $time_cap) {
            $this->processPage([Link::LT_PAGE], TRUE);
            $count++;
            $total_count++;
          }
        }

        // Process slow links.
        if ($this->hasUnprocessedSlowLinks()) {
          $count = 0;
          while ((time() - $this->startTime) <= $time_cap) {
            $this->processPage([Link::LT_UNDECIDED, Link::LT_ASSET], TRUE);
            $count++;
            $total_count++;
          }
        }

        if (!$this->hasUnprocessedLinks() && !$this->hasUnprocessedPages() && !$this->hasUnprocessedSlowLinks() && !$this->hasUnprocessedSlowPages()) {
          $this->setStatus(self::FINISHED);
        }

        //  Move status if we can.
        if ($this->hasUnprocessedSitemaps()) {
          $this->setStatus(self::PROCCESSING_SITEMAP);
        }

        break;
    }

    // ksm((time() - $this->startTime), $total_count);
  }

  public function addLink(string $url, $link_data = []) {
    $existing_links = Link::loadByProperties([
      'result' => $this->result->id(),
      'full_url' => $url,
    ]);

    if (empty($existing_links)) {
      $link_data['full_url'] = $url;

      // Make sure result is set.
      if (!isset($link_data['result'])) {
        $link_data['result'] = $this->result->id();
      }

      // Make sure external is set.
      if (!isset($link_data['external'])) {
        $link_data['external'] = $this->isExternal($url);
      }

      $l = Link::create($link_data);
      $l->save();
      return $l;
    }
    else {
      $link = Link::load($existing_links[0]->id);
      $link->mergeLinkData($link_data);
    }

    return $link;
  }

  public function addLinksInBulk($links) {
    foreach($links as &$link_data) {
      // Make sure result is set.
      if (!isset($link_data['result'])) {
        $link_data['result'] = $this->result->id();
      }

      // Make sure external is set.
      if (!isset($link_data['external'])) {
        $link_data['external'] = $this->isExternal($link_data['full_url']);
      }
    }

    Link::addInBulk($links);
  }











  /**
   * Check for unprocessed Sitemaps.
   */
  public function hasUnprocessedSitemaps() {
    // @todo: somehow cachec this.
    $unprocessed_sitemaps = Link::loadByProperties([
      'result' => $this->result->id(),
      'link_type' => Link::LT_SITEMAP,
      'checked' => [0, "<="],
    ], [], 1);

    return !empty($unprocessed_sitemaps);
  }

  /**
   * Check for unprocessed Pages.
   */
  public function hasUnprocessedPages() {
    // @todo: somehow cache this.
    $unprocessed_sitemaps = Link::loadByProperties([
      'result' => $this->result->id(),
      'link_type' => Link::LT_PAGE,
      'checked' => 0,
    ], [], 1);

    return !empty($unprocessed_sitemaps);
  }

  /**
   * Check for unprocessed Slow Pages.
   */
  public function hasUnprocessedSlowPages() {
    // @todo: somehow cache this.
    $unprocessed_sitemaps = Link::loadByProperties([
      'result' => $this->result->id(),
      'link_type' => Link::LT_PAGE,
      'checked' => [0, "<"],
    ], [], 1);

    return !empty($unprocessed_sitemaps);
  }

  /**
   * Check for unprocessed Link.
   */
  public function hasUnprocessedLinks() {
    // @todo: somehow cache this.
    $unprocessed_sitemaps = Link::loadByProperties([
      'result' => $this->result->id(),
      'link_type' => [[Link::LT_UNDECIDED, Link::LT_ASSET] , "IN"],
      'checked' => 0,
    ], [], 1);

    return !empty($unprocessed_sitemaps);
  }

  /**
   * Check for unprocessed Slow Links.
   */
  public function hasUnprocessedSlowLinks() {
    // @todo: somehow cache this.
    $unprocessed_sitemaps = Link::loadByProperties([
      'result' => $this->result->id(),
      'link_type' => [[Link::LT_UNDECIDED, Link::LT_ASSET] , "IN"],
      'checked' => [0, "<"],
    ]);

    return !empty($unprocessed_sitemaps);
  }









  /**
   * Process a sitemap.
   */
  public function processSitemap() {
    $unprocessed_sitemaps = Link::loadByProperties([
      'result' => $this->result->id(),
      'link_type' => Link::LT_SITEMAP,
      'checked' => [0, "<="],
    ], [], 1);

    if (empty($unprocessed_sitemaps)) {
      return;
    }

    $sitemap = Link::load($unprocessed_sitemaps[0]->id);

    // Call Curl.
    list($content, $headers, $http_code) = $this->callCurl($sitemap->full_url);

    // Set Status Code.
    $sitemap->setHttpStatus($http_code);

    // Handle and save redirects.
    if ((int) $http_code >= 300 && (int) $http_code <= 399 ) {
      if (isset($headers['location']) && !empty($headers['location'])) {

        $sitemap->setChecked(3);
        $sitemap->save();

        // Redirected Link.
        $new_loc = reset($headers['location']);
        $new_link_data = [
          'result' => $this->result->id(),
          'full_url' => $new_loc,
          'checked' => 0,
          'link_type' => Link::LT_SITEMAP,
          'external' => 0,
          'http_status' => NULL,
        ];
        $this->addLink($new_loc, $new_link_data);

        return FALSE;
      }
    }

    if ('200' != $http_code) {
      $sitemap->setChecked(1);
      $sitemap->save();
      return FALSE;
    }

    $xml = new \SimpleXMLElement($content, LIBXML_NOBLANKS);

    if (!$xml) {
      $sitemap->setChecked(-1);
      $sitemap->save();
      return FALSE;
    }

    $sitemap->setChecked(1);

    $links_to_add = [];
    switch ($xml->getName()) {
      case 'sitemapindex':
        foreach ($xml->sitemap as $s) {
          // $this->addLink(reset($s->loc), [
          //   'link_type' => Link::LT_SITEMAP,
          //   'data' => [
          //     'parents' => [[$sitemap->id, $sitemap->full_url]],
          //   ],
          // ]);

          $links_to_add[] = [
            'full_url' => reset($s->loc),
            'checked' => 0,
            'link_type' => Link::LT_SITEMAP,
            'external' => 0,
            'http_status' => NULL,
            'data' => [
              'parents' => [[$sitemap->id, $sitemap->full_url]],
            ],
          ];
        }
        break;

      case 'urlset':
        foreach ($xml->url as $url) {
          // $this->addLink(reset($url->loc), [
          //   'link_type' => Link::LT_PAGE,
          //   'data' => [
          //     'parents' => [[$sitemap->id, $sitemap->full_url]],
          //   ],
          // ]);

          $links_to_add[] = [
            'full_url' => reset($url->loc),
            'checked' => 0,
            'link_type' => Link::LT_PAGE,
            'external' => 0,
            'http_status' => NULL,
            'data' => [
              'parents' => [[$sitemap->id, $sitemap->full_url]],
            ],
          ];
        }
        break;

      default:
        break;
    }

    $this->addLinksInBulk($links_to_add);

    $sitemap->save();
  }

  public function processPage(array $link_types, $include_slow = FALSE) {
    $unprocessed_pages = Link::loadByProperties([
      'result' => $this->result->id(),
      'link_type' => [$link_types , "IN"],
      'checked' => ($include_slow) ? [0, "<="] : 0,
    ], [], 1);

    if (empty($unprocessed_pages)) {
      return;
    }

    $page = Link::load($unprocessed_pages[0]->id);

    $method = "";
    if (in_array($page->link_type, [Link::LT_PAGE, Link::LT_SITEMAP])) {
      $method = "GET";
    }
    else {
      $method = "HEAD";

      if ((int) $page->checked < 0) {
        $method = "GET";
      }
    }

    // Call Curl.
    list($content, $headers, $http_code, $effective_url) = $this->callCurl($page->full_url, $method);

    // Set Status Code.
    $page->setHttpStatus($http_code);
    $page->setEffectiveUrl($effective_url);
    $page->setHeaders($headers);

    // Handle and save redirects.
    if ((int) $http_code >= 300 && (int) $http_code <= 399) {
      $page->setChecked(3);

      if (isset($headers['location']) && !empty($headers['location'])) {
        // Redirected Link.
        $new_loc = reset($headers['location']);

        $parsed_parent = parse_url($page->full_url);
        $parsed_link = parse_url($new_loc);
        if (!isset($parsed_link['scheme'])) {
          $parsed_link['scheme'] = $parsed_parent['scheme'];
        }
        if (!isset($parsed_link['host'])) {
          $parsed_link['host'] = $parsed_parent['host'];
        }

        $new_loc = $this->unparse_url($parsed_link);

        $new_link_data = [
          'full_url' => $new_loc,
          'checked' => 0,
          'link_type' => Link::LT_PAGE,
          'http_status' => NULL,
        ];

        $rl = $this->addLink($new_loc, $new_link_data);
        $page->setRedirectLocation($new_loc);
      }

      $page->save();

      return;
    }

    // Handle HEAD Calls that 404.
    if ((int) $http_code >= 400 && (int) $http_code <= 499) {
      if ($method == "HEAD") {
        $page->setChecked(-4);
      }
      else {
        $page->setChecked(1);
      }
      $page->save();

      return;
    }

    // Catch a timeout.
    if ($http_code == 0) {
      // Assuming timeout.
      $page->setChecked(-1);
      $page->save();

      return;
    }

    if ('200' != $http_code) {
      $page->setChecked(1);
      $page->save();

      return;
    }

    // Double check we are a PAGE.
    if (!$this->isHtmlPage($headers, $http_code)) {

      $page->setChecked(1);
      $page->setLinkType(Link::LT_ASSET);
      $page->save();

      return;
    }

    // Process page content for links.
    // Check document for links.
    if (!empty($content) && !$page->external) {
      $dom = new \DOMDocument();
      @$dom->loadHTML($content);

      $links = $this->findLinks($dom);

      $checking_url_parsed = parse_url($page->full_url);
      foreach ($links as $tag) {
        // Skip mailto and data links and HP
        if (strpos($tag, "mailto:") === 0
          || strpos($tag, "data:") === 0
          || strpos($tag, "tel:") === 0
          || $tag == "//:0"
          || strpos($tag, "#") === 0
          || $tag === "/") {

          continue;
        }

        // We have a valid link.
        $parsed_link = parse_url($tag);

        // Check for host, scheme and external/internal.
        if (!isset($parsed_link['host'])) {
          $parsed_link['host'] = $this->host;

          // Resolve relative paths.
          $context = isset($checking_url_parsed['path']) ? $checking_url_parsed['path'] : "/";
          $resolved_path = $this->fixRelativePaths($context, $parsed_link['path']);

          $parsed_link['path'] = $resolved_path;
        }

        if (!isset($parsed_link['scheme'])) {
          $parsed_link['scheme'] = $this->scheme;
        }

        $new_full_url = $this->unparse_url($parsed_link);

        $new_link = $this->addLink($new_full_url, [
          'full_url' => $new_full_url,
          'checked' => 0,
          'link_type' => Link::LT_UNDECIDED,
          'external' => $this->isExternal($new_full_url),
          'data' => [
            'parents' => [[$page->id, $page->full_url]],
          ],
        ]);

        $page->addLink([$new_link->id, $tag]);
      }
    }

    $page->setChecked(1);
    $page->save();
  }









  protected function callCurl($url, $method = "GET") {

    $ch = curl_init();
    $headers = [];
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, FALSE);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $this->claculateTimeoutTime());
    curl_setopt($ch, CURLOPT_TIMEOUT, $this->claculateTimeoutTime());
    curl_setopt($ch, CURLOPT_USERAGENT, "curl/7.68.0");
    if ($method == "HEAD") {
      curl_setopt($ch, CURLOPT_NOBODY, TRUE);
    }
    curl_setopt($ch, CURLOPT_HEADERFUNCTION, function ($curl, $header) use (&$headers) {
      $len = strlen($header);
      $header = explode(':', $header, 2);
      // Ignore invalid headers.
      if (count($header) < 2) {
        return $len;
      }

      $headers[strtolower(trim($header[0]))][] = trim($header[1]);

      return $len;
    });

    $content = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $effective_url = curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);

    if ($http_code > 400 && $http_code) {
      // ksm($method, $content, curl_getinfo($ch));
    }

    curl_close($ch);

    // Return data.
    return [
      $content,
      $headers,
      $http_code,
      $effective_url,
    ];
  }

  protected function isHtmlPage($headers, $http_return_code) {
    $isHtml = FALSE;

    if ($http_return_code > 299) {
      return FALSE;
    }

    if (isset($headers['content-type'])) {
      foreach($headers['content-type'] as $h) {
        if (strpos($h, "text/html") !== FALSE) {
          $isHtml = TRUE;
        }
      }
    }

    return $isHtml;
  }

  protected function isExternal($url_to_check) {
    $parse_test_url = parse_url($this->baseUrl);
    $parsed_link = parse_url($url_to_check);

    return ($parsed_link['host'] != $parse_test_url['host'] || $parsed_link['scheme'] != $parse_test_url['scheme']);
  }

  protected function findLinks($dom) {

    $xpath = new \DomXpath($dom);
    $links = [];

    $lists = [];

    $lists[] = $dom->getElementsByTagName("a");
    $lists[] = $dom->getElementsByTagName("link");
    $lists[] = $xpath->query("//*[@src]");
    $lists[] = $xpath->query("//*[@srcset]");

    foreach ($lists as $list) {
      foreach ($list as $tag) {
        $tag_array = $this->dom2Array($tag);

        if (isset($tag_array['_attributes']['href']) && !in_array($tag_array['_attributes']['href'], $links)) {
          $links[] = $tag_array['_attributes']['href'];
        }
        if (isset($tag_array['_attributes']['src']) && !in_array($tag_array['_attributes']['src'], $links) && !empty($tag_array['_attributes']['src'])) {
          $links[] = $tag_array['_attributes']['src'];
        }
        if (isset($tag_array['_attributes']['srcset'])) {
          $srcset_ex = explode(",", $tag_array['_attributes']['srcset']);

          foreach ($srcset_ex as $s) {
            $ss = explode(" ", $s);

            if (!in_array($ss[0], $links)) {
              $links[] = $ss[0];
            }
          }
        }
      }
    }

    return $links;
  }

  protected function fixRelativePaths($context, $path) {

    $context = str_replace(array('/', '\\'), DIRECTORY_SEPARATOR, $context);
    $path = str_replace(array('/', '\\'), DIRECTORY_SEPARATOR, $path);

    if (strpos($path, DIRECTORY_SEPARATOR) === 0) {
      return $path;
    }

    $context_path_info = pathinfo($context);
    if (strpos($path, "..") === FALSE) {
      return $context_path_info['dirname'] . DIRECTORY_SEPARATOR . $path;
    }

    $url_exp = ($context_path_info['dirname'] == DIRECTORY_SEPARATOR) ? [] : explode(DIRECTORY_SEPARATOR, $context_path_info['dirname']);
    $parts = array_filter(explode(DIRECTORY_SEPARATOR, $path), 'strlen');
    foreach ($parts as $part) {
      if ('.' == $part) continue;
      if ('..' == $part) {
        array_pop($url_exp);
      } else {
        $url_exp[] = $part;
      }
    }

    if ($url_exp[0] !== "") {
      array_unshift($url_exp, "");
    }

    return implode(DIRECTORY_SEPARATOR, $url_exp);
  }

  protected function unparse_url($parsed_url) {

    $scheme   = isset($parsed_url['scheme']) ? $parsed_url['scheme'] . '://' : '';
    $host     = isset($parsed_url['host']) ? $parsed_url['host'] : '';
    $port     = isset($parsed_url['port']) ? ':' . $parsed_url['port'] : '';
    $user     = isset($parsed_url['user']) ? $parsed_url['user'] : '';
    $pass     = isset($parsed_url['pass']) ? ':' . $parsed_url['pass']  : '';
    $pass     = ($user || $pass) ? "$pass@" : '';
    $path     = isset($parsed_url['path']) ? $parsed_url['path'] : '';
    $query    = isset($parsed_url['query']) ? '?' . $parsed_url['query'] : '';
    $fragment = isset($parsed_url['fragment']) ? '#' . $parsed_url['fragment'] : '';

    return "$scheme$user$pass$host$port$path$query$fragment";
  }

  protected function claculateTimeoutTime() {
    $t = 30 - (time() - $this->startTime) - 2;
    // Cap at 10 seconds.
    return min($t, 10);
  }

  public function save() {
    $this->result->field_blc_status = $this->status;

    $this->result->field_blc_page_count = $this->getPageCount();
    $this->result->field_blc_link_count = $this->getLinkCount();
    $this->result->field_blc_problem_link_count = $this->getProblemLinkCount();

    $this->result->save();
  }

}
