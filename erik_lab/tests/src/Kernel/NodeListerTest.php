<?php

namespace Drupal\Tests\erik_lab\Kernel;

use Drupal\KernelTests\KernelTestBase;
use Drupal\node\Entity\Node;
use Drupal\node\Entity\NodeType;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Kernel tests for the NodeLister service.
 *
 * Kernel tests are the right choice here: we need the Entity API and
 * database but not a browser/HTTP layer, so Kernel is faster than
 * Functional while still being a real integration test.
 *
 * In Kernel tests NOTHING is installed automatically — you must call
 * installEntitySchema(), installConfig(), etc. in setUp().
 *
 * Run:
 *   ddev exec vendor/bin/phpunit modules/custom/erik_lab/tests/src/Kernel
 *
 * @group erik_lab
 */
#[RunTestsInSeparateProcesses]
class NodeListerTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   *
   * Kernel tests only boot the modules listed here.  Dependencies are NOT
   * resolved automatically — list every module the test actually needs.
   */
  protected static $modules = ['erik_lab', 'node', 'user', 'system', 'field'];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Create database tables for the entities we need.
    $this->installEntitySchema('user');
    $this->installEntitySchema('node');

    // node_access table is required for accessCheck(TRUE) entity queries.
    $this->installSchema('node', 'node_access');

    // Install minimal system config (node module config includes body field
    // definitions that require the text module — we don't need them here).
    $this->installConfig(['system']);

    // Create a minimal content type so we can create test nodes.
    NodeType::create(['type' => 'page', 'name' => 'Page'])->save();
  }

  /**
   * GetPublishedNodes() returns only published nodes, not drafts.
   */
  public function testGetPublishedNodesOnlyReturnsPublished(): void {
    Node::create(['type' => 'page', 'title' => 'Published A', 'status' => 1])->save();
    Node::create(['type' => 'page', 'title' => 'Draft', 'status' => 0])->save();
    Node::create(['type' => 'page', 'title' => 'Published B', 'status' => 1])->save();

    /** @var \Drupal\erik_lab\Service\NodeLister $lister */
    $lister = $this->container->get('erik_lab.node_lister');
    $result = $lister->getPublishedNodes();

    $this->assertCount(2, $result, 'Only the two published nodes are returned.');

    $titles = array_map(fn($n) => $n->label(), $result);
    $this->assertContains('Published A', $titles);
    $this->assertContains('Published B', $titles);
    $this->assertNotContains('Draft', $titles);
  }

  /**
   * GetPublishedNodes() respects the $limit argument.
   */
  public function testGetPublishedNodesRespectsLimit(): void {
    for ($i = 1; $i <= 5; $i++) {
      Node::create(['type' => 'page', 'title' => "Node $i", 'status' => 1])->save();
    }

    /** @var \Drupal\erik_lab\Service\NodeLister $lister */
    $lister = $this->container->get('erik_lab.node_lister');

    $this->assertCount(3, $lister->getPublishedNodes(3));
    $this->assertCount(1, $lister->getPublishedNodes(1));
  }

  /**
   * LoadNode() returns the correct entity for a known NID.
   */
  public function testLoadNodeReturnsCorrectEntity(): void {
    $node = Node::create(['type' => 'page', 'title' => 'Findable', 'status' => 1]);
    $node->save();

    /** @var \Drupal\erik_lab\Service\NodeLister $lister */
    $lister = $this->container->get('erik_lab.node_lister');
    $loaded = $lister->loadNode((int) $node->id());

    $this->assertNotNull($loaded);
    $this->assertSame((int) $node->id(), (int) $loaded->id());
    $this->assertSame('Findable', $loaded->label());
  }

  /**
   * LoadNode() returns NULL for a NID that does not exist.
   */
  public function testLoadNodeReturnsNullForMissingNid(): void {
    /** @var \Drupal\erik_lab\Service\NodeLister $lister */
    $lister = $this->container->get('erik_lab.node_lister');

    $this->assertNull($lister->loadNode(99999));
  }

}
