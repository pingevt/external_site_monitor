<?php

namespace Drupal\external_site_monitor;

use GuzzleHttp\ClientInterface;

/**
 * Trait to handle EntityTypeManager.
 */
trait HttpClientTrait {

  /**
   * The HTTP client to fetch the feed data with.
   *
   * @var \GuzzleHttp\ClientInterface
   */
  protected $httpClient;

  /**
   * Sets the httpClient.
   *
   * @param \GuzzleHttp\ClientInterface $httpClient
   *   The httpClient.
   */
  public function setHttpClient(ClientInterface $http_client) {
    $this->httpClient = $http_client;
  }

  /**
   * Gets the httpClient.
   *
   * @return \Drupal\Core\Entity\EntityTypeManagerInterface
   *   The httpClient.
   */
  public function httpClient() {
    if (!isset($this->httpClient)) {
      $this->httpClient = \Drupal::httpClient();
    }
    return $this->httpClient;
  }

}
