<?php

namespace Drupal\Tests\external_site_monitor\Functional;

use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Tests\BrowserTestBase;

/**
 *
 *
 * @group external_site_monitor
 */
class BlcFuncTest extends BrowserTestBase {

  /**
   * The modules to load to run the test.
   *
   * @var array
   */
  protected static $modules = [
    'node',
    'field',
    'text',
    'options',
    'external_site_monitor',
  ];

  /**
   * Default theme.
   *
   * @var string
   */
  protected $defaultTheme = 'claro';

  /**
   * A user with administration rights.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $adminUser;

  /**
   * An authenticated user.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $authenticatedUser;

  /**
   * A test menu.
   *
   * @var \Drupal\system\Entity\Menu
   */
  protected $menu;

  /**
   * {@inheritdoc}
   */
  protected function setUp() : void {
    parent::setUp();

    $this->adminUser = $this->drupalCreateUser([
      'access administration pages',
    ]);
    $this->authenticatedUser = $this->drupalCreateUser([]);

  }

  /**
   * Test Basic Functionality.
   */
  public function testBasicFunc() {
    $session = $this->assertSession();

    $this->assertTrue(TRUE);
  }

}
