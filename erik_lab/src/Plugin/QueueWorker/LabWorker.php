<?php

namespace Drupal\erik_lab\Plugin\QueueWorker;

use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Queue\Attribute\QueueWorker;
use Drupal\Core\Queue\QueueWorkerBase;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Processes items in the erik_lab_queue queue.
 *
 * Queue API pattern:
 *   Producer: hook_cron() calls
 *   \Drupal::queue('erik_lab_queue')->createItem($data)
 *   Consumer: this plugin's processItem($data) does the actual work.
 *
 * Run manually:  drush queue:run erik_lab_queue
 * List queues:   drush queue:list
 */
#[QueueWorker(
  id: 'erik_lab_queue',
  title: new TranslatableMarkup('Erik Lab queue worker'),
  cron: ['time' => 30],
)]
class LabWorker extends QueueWorkerBase implements ContainerFactoryPluginInterface {

  /**
   * Constructs a LabWorker.
   *
   * @param array $configuration
   *   Plugin configuration.
   * @param string $plugin_id
   *   Plugin ID.
   * @param mixed $plugin_definition
   *   Plugin definition.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $loggerFactory
   *   The logger factory.
   */
  public function __construct(
    array $configuration,
    string $plugin_id,
    mixed $plugin_definition,
    protected readonly LoggerChannelFactoryInterface $loggerFactory,
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): static {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('logger.factory'),
    );
  }

  /**
   * Processes one queued item.
   *
   * Returning normally deletes the item from the queue (success).
   * Throwing any exception requeues it for retry.
   * Throw SuspendQueueException to halt the entire queue run.
   *
   * @param mixed $data
   *   Data passed to \Drupal::queue('erik_lab_queue')->createItem($data).
   */
  public function processItem($data): void {
    $this->loggerFactory->get('erik_lab')->notice(
      'Queue worker processed: @msg (nid: @nid)',
      [
        '@msg' => $data['message'] ?? '-',
        '@nid' => $data['nid'] ?? '-',
      ]
    );

    // Simulate real work: call an external API, update a node, send email...
    // Uncomment to test retry behaviour (item will be requeued):
    // throw new \RuntimeException('Simulate failure.');.
  }

}
