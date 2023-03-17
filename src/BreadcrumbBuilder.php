<?php

namespace Drupal\external_site_monitor;

use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Component\Utility\SortArray;
use Drupal\Core\Breadcrumb\Breadcrumb;
use Drupal\Core\Breadcrumb\BreadcrumbBuilderInterface;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Controller\TitleResolverInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\Core\Language\Language;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Link;
use Drupal\Core\Lock\LockBackendInterface;
use Drupal\Core\Menu\MenuActiveTrail;
use Drupal\Core\Menu\MenuActiveTrailInterface;
use Drupal\Core\Menu\MenuLinkManagerInterface;
use Drupal\Core\Routing\AdminContext;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Url;
use Drupal\menu_link_content\Plugin\Menu\MenuLinkContent;
use Drupal\node\NodeInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Request;

/**
 * {@inheritdoc}
 */
class BreadcrumbBuilder implements BreadcrumbBuilderInterface {
  use StringTranslationTrait;

  /**
   * The configuration object generator.
   *
   * @var \Drupal\Core\Config\ConfigFactory
   */
  protected $configFactory;

  /**
   * The menu active trail interface.
   *
   * @var \Drupal\Core\Menu\MenuActiveTrailInterface
   */
  protected $menuActiveTrail;

  /**
   * The menu link manager interface.
   *
   * @var \Drupal\Core\Menu\MenuLinkManagerInterface
   */
  protected $menuLinkManager;

  /**
   * The admin context generator.
   *
   * @var \Drupal\Core\Routing\AdminContext
   */
  protected $adminContext;

  /**
   * The title resolver.
   *
   * @var \Drupal\Core\Controller\TitleResolverInterface
   */
  protected $titleResolver;

  /**
   * The current request.
   *
   * @var \Symfony\Component\HttpFoundation\Request
   */
  protected $currentRequest;

  /**
   * The language manager.
   *
   * @var \Drupal\Core\Language\LanguageManager
   */
  protected $languageManager;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The caching backend.
   *
   * @var \Drupal\Core\Cache\CacheBackendInterface
   */
  protected $cacheMenu;

  /**
   * The locking backend.
   *
   * @var \Drupal\Core\Lock\LockBackendInterface
   */
  protected $lock;

  /**
   * The menu where the current page or taxonomy match has taken place.
   *
   * @var string
   */
  private $menuName;

  /**
   * The menu trail leading to this match.
   *
   * @var string
   */
  private $menuTrail;

  /**
   * Node of current path if taxonomy attached.
   *
   * @var \Drupal\node\Entity\Node
   */
  private $taxonomyAttachment;

  /**
   * Content language code (used in both applies() and build()).
   *
   * @var string
   */
  private $contentLanguage;

  /**
   * {@inheritdoc}
   */
  public function __construct(
    ConfigFactoryInterface $config_factory,
    MenuActiveTrailInterface $menu_active_trail,
    MenuLinkManagerInterface $menu_link_manager,
    AdminContext $admin_context,
    TitleResolverInterface $title_resolver,
    RequestStack $request_stack,
    LanguageManagerInterface $language_manager,
    EntityTypeManagerInterface $entity_type_manager,
    CacheBackendInterface $cache_menu,
    LockBackendInterface $lock
  ) {
    $this->configFactory = $config_factory;
    $this->menuActiveTrail = $menu_active_trail;
    $this->menuLinkManager = $menu_link_manager;
    $this->adminContext = $admin_context;
    $this->titleResolver = $title_resolver;
    $this->currentRequest = $request_stack->getCurrentRequest();
    $this->languageManager = $language_manager;
    $this->entityTypeManager = $entity_type_manager;
    $this->cacheMenu = $cache_menu;
    $this->lock = $lock;
  }



  /**
   * {@inheritdoc}
   */
  public function applies(RouteMatchInterface $route_match) {
    // No route name means no active trail:
    $route_name = $route_match->getRouteName();

    if (!$route_name) {
      return FALSE;
    }

    $routes = [
      'entity.site.canonical',
      'site.tests',
      'entity.test.canonical',
      'test.test_results',
      'entity.result.canonical',
    ];

    if (in_array($route_name, $routes)) {
      return TRUE;
    }

    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function build(RouteMatchInterface $route_match) {
    $breadcrumb = new Breadcrumb();

    $objs = [
      'site' => $route_match->getParameter('site'),
      'test' => $route_match->getParameter('test'),
      'result' => $route_match->getParameter('result'),
    ];

    $links = [];

    // Set Home Link.
    $home_link = Link::createFromRoute("Sites", 'entity.site.collection');
    $links[] = $home_link;

    if ($objs['result']) {
      $objs['test'] = $objs['result']->test->entity;
    }

    if ($objs['test']) {
      $objs['site'] = $objs['test']->site->entity;
    }

    foreach ($objs as $type => $entity) {
      if (!is_null($entity)) {
        switch ($type) {
          case 'result':
            $links[] = Link::createFromRoute($objs['test']->label() . " Results", 'test.test_results', ['test' => $objs['test']->id()]);
            break;
          case 'test':
            $links[] = Link::createFromRoute($objs['site']->label() . " Tests", 'site.tests', ['site' => $objs['site']->id()]);
            break;
        }
        $links[] = Link::createFromRoute($entity->label(), 'entity.' . $type . '.canonical', [$type => $entity->id()]);
      }
    }

    // Add cacheable stuff.
    $breadcrumb->addCacheContexts(['url.path']);

    $breadcrumb->setLinks($links);

    return $breadcrumb;
  }

}
