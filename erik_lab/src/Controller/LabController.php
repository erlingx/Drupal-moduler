<?php

namespace Drupal\erik_lab\Controller;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Controller\ControllerBase;
use Drupal\erik_lab\Service\NodeLister;
use Drupal\node\NodeInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Lab controller — demonstrates routing, DI, Entity API, Cache API.
 *
 * Constructor injection is the Drupal 10 preferred pattern.
 * Never use \Drupal::service() in a class that can use DI.
 */
class LabController extends ControllerBase {

  /**
   * Our custom service, injected by Drupal's container.
   */
  public function __construct(
    protected readonly NodeLister $nodeLister,
    protected readonly TimeInterface $time,
  ) {}

  /**
   * DI factory — Drupal calls this to build the controller.
   */
  public static function create(ContainerInterface $container): static {
    return new static(
      $container->get('erik_lab.node_lister'),
      $container->get('datetime.time'),
    );
  }

  /**
   * Render array listing published nodes.
   */
  public function nodeList(): array {
    $nodes = $this->nodeLister->getPublishedNodes(10);

    $rows = [];
    foreach ($nodes as $node) {
      $rows[] = [
        $node->id(),
        $node->bundle(),
        $node->label(),
        $node->get('uid')->entity?->getDisplayName() ?? '—',
        date('Y-m-d', $node->getCreatedTime()),
      ];
    }

    $build = [
      '#type'    => 'table',
      '#header'  => ['NID', 'Bundle', 'Title', 'Author', 'Created'],
      '#rows'    => $rows,
      '#empty'   => $this->t('No published nodes found.'),

      // Cache metadata:
      // tags    → invalidated when any node changes
      // contexts → vary per user roles (anonymous vs editor sees same
      // here, but good practice)
      // max-age  → 1 hour TTL.
      '#cache'   => [
        'tags'     => ['node_list'],
        'contexts' => ['user.roles'],
        'max-age'  => 3600,
      ],
    ];

    return $build;
  }

  /**
   * Demonstrates cache tags, contexts and max-age on a render array.
   */
  public function cacheDemo(): array {
    $site_name = $this->config('system.site')->get('name');
    $time      = $this->time->getRequestTime();

    return [
      '#theme'  => 'item_list',
      '#title'  => $this->t('Cache API demo'),
      '#items'  => [
        $this->t('Site name from Config API: <strong>@name</strong>', ['@name' => $site_name]),
        $this->t('Rendered at: @time (cached for 60s)', ['@time' => date('H:i:s', $time)]),
        $this->t('Cache tag: <code>config:system.site</code> — auto-invalidated when site name changes.'),
        $this->t(
          'Cache context: <code>url.path</code> — unique cache entry per path.'
        ),
      ],

      '#cache'  => [
        // Drupal invalidates this cache entry automatically when
        // system.site config changes.
        'tags'     => ['config:system.site'],
        'contexts' => ['url.path'],
        'max-age'  => 60,
      ],
    ];
  }

  /**
   * ParamConverter / entity upcasting demo.
   *
   * Drupal's EntityConverter automatically loads the Node for us based on
   * the {node} placeholder in the route path.  By the time this method
   * runs $node is already a fully loaded NodeInterface — no manual
   * load() call needed.  A missing NID triggers a 404 automatically.
   *
   * Route: /erik-lab/node/{node}
   * Try:   /erik-lab/node/1
   *
   * @param \Drupal\node\NodeInterface $node
   *   The node loaded by the ParamConverter (upcasted from the NID in
   *   the URL).
   *
   * @return array
   *   A render array.
   */
  public function nodeView(NodeInterface $node): array {
    return [
      '#theme'  => 'item_list',
      '#title'  => $this->t(
        'ParamConverter demo — node @nid',
        ['@nid' => $node->id()]
      ),
      '#items'  => [
        $this->t('NID: @v', ['@v' => $node->id()]),
        $this->t('Bundle: @v', ['@v' => $node->bundle()]),
        $this->t('Title: @v', ['@v' => $node->label()]),
        $this->t(
          'Status: @v',
          ['@v' => $node->isPublished() ? 'Published' : 'Unpublished']
        ),
        $this->t(
          'Author: @v',
          ['@v' => $node->get('uid')->entity?->getDisplayName() ?? '—']
        ),
        $this->t(
          'Created: @v',
          ['@v' => date('Y-m-d H:i:s', $node->getCreatedTime())]
        ),
      ],
      // Cache this per node — invalidated automatically when the node
      // is saved or deleted.
      '#cache'  => [
        'tags'     => $node->getCacheTags(),
        'contexts' => ['user.roles'],
        'max-age'  => 3600,
      ],
    ];
  }

}
