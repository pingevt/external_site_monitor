<?php

namespace Drupal\Tests\external_site_monitor\Unit;

use Drupal\Tests\UnitTestCase;
use Drupal\esm_test_blc\SiteCrawlerController;

/**
 *
 */
class BlcTests extends UnitTestCase {

  protected $siteCrawler;

  /**
   * Before a test method is run, setUp() is invoked.
   * Create new unit object.
   */
  public function setUp() {
    $this->siteCrawler = new SiteCrawlerController();
  }

  /**
   * Test staic vars.
   */
  public function testCheckStaticVars() {

    // Class Constants.
  // const NEEDS_ROOT = -50;
  // const NEEDS_SITEMAP = -10;
  // const PROCCESSING_SITEMAP = -8;
  // const WORKING = -2;
  // const FINISHED = 1;

  // const LABELS = [
  //   self::NEEDS_ROOT => "Needs Root",
  //   self::NEEDS_SITEMAP => "Needs Sitemap",
  //   self::PROCCESSING_SITEMAP => "Processing Sitemap",
  //   self::WORKING => "Working...",
  //   self::FINISHED => "Finished",
  // ];



    $this->assertEquals(-50, SiteCrawlerController::NEEDS_ROOT);
    $this->assertEquals(-10, SiteCrawlerController::NEEDS_SITEMAP);
    $this->assertEquals(-8, SiteCrawlerController::PROCCESSING_SITEMAP);
    $this->assertEquals(-2, SiteCrawlerController::WORKING);
    $this->assertEquals(1, SiteCrawlerController::FINISHED);
  }

  /**
   * Once test method has finished running, whether it succeeded or failed, tearDown() will be invoked.
   * Unset the  object.
   */
  public function tearDown() {
    unset($this->siteCrawler);
  }

}
