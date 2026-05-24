<?php

namespace Drupal\anklagerupdate_rss\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * REST API controller for AnklagerUpdate messages.
 */
class ApiController extends ControllerBase {

  /**
   * Get paginated messages.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The current request.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   JSON response with messages and pagination metadata.
   */
  public function getMessages(Request $request) {
    $connection = $this->database();

    // Get query parameters.
    $publisherId = $request->query->get('publisher_id');
    $page = max(1, (int) $request->query->get('page', 1));
    $limit = min(100, max(1, (int) $request->query->get('limit', 10)));
    $search = $request->query->get('search');

    // Build query.
    $query = $connection->select('anklagerupdate_messages', 'am')
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
        'created',
      ]);

    // Apply filters.
    if (!empty($publisherId)) {
      $query->condition('publisher_id', $publisherId);
    }

    if (!empty($search)) {
      $or = $query->orConditionGroup()
        ->condition('title', '%' . $connection->escapeLike($search) . '%', 'LIKE')
        ->condition('description', '%' . $connection->escapeLike($search) . '%', 'LIKE');
      $query->condition($or);
    }

    // Get total count.
    $countQuery = clone $query;
    $total = $countQuery->countQuery()->execute()->fetchField();

    // Add pagination.
    $offset = ($page - 1) * $limit;
    $query->orderBy('pub_date', 'DESC')
      ->range($offset, $limit);

    // Execute query.
    $results = $query->execute()->fetchAll();

    // Format results.
    $messages = [];
    foreach ($results as $row) {
      $messages[] = [
        'id' => (int) $row->id,
        'guid' => $row->guid,
        'publisher' => [
          'id' => $row->publisher_id,
          'name' => $row->publisher_name,
        ],
        'title' => $row->title,
        'description' => $row->description,
        'link' => $row->link,
        'pubDate' => date('c', $row->pub_date),
        'pubDateTimestamp' => (int) $row->pub_date,
        'category' => $row->category,
        'created' => date('c', $row->created),
      ];
    }

    // Calculate pagination metadata.
    $totalPages = ceil($total / $limit);

    return new JsonResponse([
      'data' => $messages,
      'pagination' => [
        'page' => $page,
        'limit' => $limit,
        'total' => (int) $total,
        'totalPages' => $totalPages,
        'hasNext' => $page < $totalPages,
        'hasPrev' => $page > 1,
      ],
    ]);
  }

  /**
   * Get list of publishers with message counts.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   JSON response with publisher list.
   */
  public function getPublishers() {
    $connection = $this->database();

    $query = $connection->select('anklagerupdate_messages', 'am')
      ->fields('am', ['publisher_id', 'publisher_name']);

    $query->addExpression('COUNT(*)', 'message_count');
    $query->addExpression('MAX(pub_date)', 'latest_message');

    $query->groupBy('publisher_id')
      ->groupBy('publisher_name')
      ->orderBy('publisher_name', 'ASC');

    $results = $query->execute()->fetchAll();

    $publishers = [];
    foreach ($results as $row) {
      $publishers[] = [
        'id' => $row->publisher_id,
        'name' => $row->publisher_name,
        'messageCount' => (int) $row->message_count,
        'latestMessage' => $row->latest_message ? date('c', $row->latest_message) : NULL,
      ];
    }

    return new JsonResponse([
      'data' => $publishers,
      'total' => count($publishers),
    ]);
  }

}
