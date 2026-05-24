<?php

namespace Drupal\anklagerupdate_rss\Http;

use Drupal\Core\Http\ClientFactory;
use GuzzleHttp\Client;

/**
 * Decorates the core HTTP client factory to disable SSL verification on
 * Windows servers that lack a proper CA bundle.
 */
class WindowsSslClientFactory {

  /**
   * The Windows production/test hosts that have no valid CA bundle.
   */
  private const WINDOWS_HOSTS = [
    'anklagemyndigheden.dk',
    'www.anklagemyndigheden.dk',
    '31.31.83.25',
  ];

  public function __construct(private ClientFactory $inner) {}

  /**
   * Creates a Guzzle client, disabling SSL verification on Windows hosts.
   */
  public function fromOptions(array $config = []): Client {
    $host = $_SERVER['HTTP_HOST'] ?? '';
    if (in_array($host, self::WINDOWS_HOSTS, TRUE)) {
      $config['verify'] = FALSE;
    }
    return $this->inner->fromOptions($config);
  }

}

