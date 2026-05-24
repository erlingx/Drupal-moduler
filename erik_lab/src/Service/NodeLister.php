<?php

namespace Drupal\erik_lab\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\node\NodeInterface;

/**
 * Service: NodeLister.
 *
 * Demonstrates proper Dependency Injection pattern in a Drupal 10 service.
 * Defined in erik_lab.services.yml, injected into LabController.
 *
 * Key concepts:
 *   - entityTypeManager: gateway to all entity storage
 *   - entityQuery: builds SQL-free queries on entities
 *   - Constructor injection: no static \Drupal:: calls
 */
class NodeLister {

  public function __construct(
    protected readonly EntityTypeManagerInterface $entityTypeManager,
    protected readonly LoggerChannelFactoryInterface $loggerFactory,
  ) {}

  /**
   * Returns published nodes, newest first.
   *
   * Entity API pattern:
   *   1. entityQuery  → get matching IDs
   *   2. loadMultiple → load full entities by those IDs.
   *
   * @return \Drupal\node\NodeInterface[]
   *   Published node entities, newest first.
   */
  public function getPublishedNodes(int $limit = 10): array {
    // Step 1: Build a query — no SQL, works across DB backends.
    $nids = $this->entityTypeManager
      ->getStorage('node')
      ->getQuery()
    // Respect node access (roles/permissions)
      ->accessCheck(TRUE)
      ->condition('status', NodeInterface::PUBLISHED)
      ->sort('created', 'DESC')
      ->range(0, $limit)
      ->execute();

    if (empty($nids)) {
      return [];
    }

    // Step 2: Load full node entities.
    /** @var \Drupal\node\NodeInterface[] $nodes */
    $nodes = $this->entityTypeManager
      ->getStorage('node')
      ->loadMultiple($nids);

    $this->loggerFactory
      ->get('erik_lab')
      ->notice('NodeLister: loaded @count nodes.', ['@count' => count($nodes)]);

    return $nodes;
  }

  /**
   * Load a single node by ID — simplest Entity API call.
   *
   * Compare with: \Drupal::entityTypeManager()->getStorage('node')->load($nid)
   */
  public function loadNode(int $nid): ?NodeInterface {
    return $this->entityTypeManager->getStorage('node')->load($nid);
  }

  /**
   * Demonstrate field value access patterns.
   *
   * Uncomment and call from a controller to see output in the log.
   */
  public function demoFieldAccess(int $nid): void {
    $node = $this->loadNode($nid);
    if (!$node) {
      return;
    }

    // Scalar field value.
    $title = $node->label();

    // Long-text field with format.
    $body_value = $node->get('body')->value;
    $body_format = $node->get('body')->format;

    // Entity reference — ->entity gives the referenced entity object.
    $author = $node->get('uid')->entity;
    $author_name = $author?->getDisplayName();

    // Multi-value field — iterate with getValue() or use
    // ->referencedEntities().
    // $items = $node->get('field_tags')->referencedEntities();
    $this->loggerFactory->get('erik_lab')->notice(
      'Node @nid: title=@title, body=@body, author=@author, body_format=@format',
      [
        '@nid'    => $nid,
        '@title'  => $title,
        '@body'   => $body_value,
        '@author' => $author_name,
        '@format' => $body_format,
      ]
    );
  }

}
