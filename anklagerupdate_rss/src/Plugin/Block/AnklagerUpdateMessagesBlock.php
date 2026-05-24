<?php

namespace Drupal\anklagerupdate_rss\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Database\Connection;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Provides an 'AnklagerUpdate Messages' Block.
 *
 * @Block(
 *   id = "anklagerupdate_messages_block",
 *   admin_label = @Translation("AnklagerUpdate Messages"),
 *   category = @Translation("Custom"),
 * )
 */
class AnklagerUpdateMessagesBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected Connection $connection;

  /**
   * The request stack.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected RequestStack $requestStack;

  /**
   * Constructs a new AnklagerUpdateMessagesBlock.
   *
   * @param array $configuration
   *   A configuration array.
   * @param string $plugin_id
   *   The plugin ID.
   * @param mixed $plugin_definition
   *   The plugin definition.
   * @param \Drupal\Core\Database\Connection $connection
   *   The database connection.
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   The request stack.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, Connection $connection, RequestStack $request_stack) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->connection = $connection;
    $this->requestStack = $request_stack;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('database'),
      $container->get('request_stack')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    // Get query parameters from URL.
    $request = $this->requestStack->getCurrentRequest();
    $publisher_id = $request->query->get('publisher_id');
    $page = max(1, (int) $request->query->get('page', 1));
    // Items per page.
    $limit = 10;

    // Build query.
    $query = $this->connection->select('anklagerupdate_messages', 'am')
      ->fields('am', [
        'id',
        'guid',
        'publisher_id',
        'publisher_name',
        'title',
        'description',
        'link',
        'pub_date',
        'category',
      ]);

    // Apply publisher filter if set.
    if (!empty($publisher_id)) {
      $query->condition('publisher_id', $publisher_id);
    }

    // Get total count for pagination.
    $count_query = clone $query;
    $total = $count_query->countQuery()->execute()->fetchField();

    // Add pagination.
    $offset = ($page - 1) * $limit;
    $query->orderBy('pub_date', 'DESC')
      ->range($offset, $limit);

    // Execute query.
    $results = $query->execute()->fetchAll();

    // Get list of publishers for filter dropdown.
    $publishers_query = $this->connection->select('anklagerupdate_messages', 'am')
      ->fields('am', ['publisher_id', 'publisher_name'])
      ->groupBy('publisher_id')
      ->groupBy('publisher_name')
      ->orderBy('publisher_name', 'ASC');
    $publishers = $publishers_query->execute()->fetchAll();

    // Calculate pagination.
    $total_pages = ceil($total / $limit);

    return [
      '#theme' => 'anklagerupdate_messages_block',
      '#messages' => $results,
      '#publishers' => $publishers,
      '#current_publisher' => $publisher_id,
      '#pagination' => [
        'page' => $page,
        'total' => $total,
        'total_pages' => $total_pages,
        'has_next' => $page < $total_pages,
        'has_prev' => $page > 1,
      ],
      '#cache' => [
        // Cache for 5 minutes.
        'max-age' => 300,
        'contexts' => ['url.query_args'],
        'tags' => ['anklagerupdate_messages'],
      ],
    ];
  }

}
