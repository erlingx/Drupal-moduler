<?php
/**
 * @file
 * Seed fake AnklagerUpdate messages for testing.
 *
 * Environments:
 *   ddev  – local DDEV (default when inside DDEV container)
 *   test  – remote TEST server (31.31.83.25)
 *
 * Usage:
 *   ddev php seed-test-messages.php           # ddev is auto-detected
 *   ddev php seed-test-messages.php ddev
 *   php seed-test-messages.php test
 *
 * Remove all seeded rows:
 *   ddev php seed-test-messages.php --clear
 *   php seed-test-messages.php test --clear
 */

$logFile = __DIR__ . '/rss-import-error.log';
ini_set('log_errors', 1);
ini_set('error_log', $logFile);

// CLI only – never expose this script via HTTP.
if (PHP_SAPI !== 'cli') {
  http_response_code(403);
  exit('403 Forbidden – run from CLI only.');
}

// Auto-detect DDEV when no argument given.
$default_env = getenv('IS_DDEV_PROJECT') === 'true' ? 'ddev' : 'test';
$env          = strtolower($argv[1] ?? $default_env);
$clear        = in_array('--clear', $argv, TRUE);
$no_invalidate = in_array('--no-invalidate', $argv, TRUE);

// Inside DDEV you can only seed the local DDEV database.
// To seed TEST, SSH into the TEST server and run: php seed-test-messages.php test
if (getenv('IS_DDEV_PROJECT') === 'true' && $env === 'test') {
  echo "ERROR: Cannot seed TEST from inside DDEV – the TEST database is not reachable from the DDEV container.\n";
  echo "SSH into the TEST server and run: php seed-test-messages.php test\n";
  exit(1);
}

// Safety guard: never run on production.
if ($env === 'prd') {
  echo "ERROR: seed-test-messages.php must not be run on PRD.\n";
  exit(1);
}

$environments = [
  'ddev' => [
    'host'  => getenv('DDEV_HOSTNAME') ?: 'anklagemyndigheden.ddev.site',
    'port'  => '80',
    'https' => FALSE,
  ],
  'test' => [
    'host'  => '31.31.83.25',
    'port'  => '80',
    'https' => FALSE,
  ],
];

if (!isset($environments[$env])) {
  echo "Unknown environment: $env\n";
  exit(1);
}

$config = $environments[$env];

define('DRUPAL_DIR', __DIR__);
chdir(DRUPAL_DIR);

$_SERVER['HTTP_HOST']      = $config['host'];
$_SERVER['REMOTE_ADDR']    = '127.0.0.1';
$_SERVER['REQUEST_URI']    = '/';
$_SERVER['REQUEST_METHOD'] = 'GET';
$_SERVER['SERVER_NAME']    = $config['host'];
$_SERVER['SERVER_PORT']    = $config['port'];
$_SERVER['SCRIPT_NAME']    = '/index.php';
if ($config['https']) {
  $_SERVER['HTTPS'] = 'on';
}

$autoloader = require DRUPAL_DIR . '/autoload.php';

use Drupal\Core\DrupalKernel;
use Symfony\Component\HttpFoundation\Request;

$request = Request::createFromGlobals();
$kernel  = DrupalKernel::createFromRequest($request, $autoloader, 'prod');
$kernel->boot();
$kernel->preHandle($request);

$connection = \Drupal::database();
$logger     = \Drupal::logger('anklagerupdate_rss');

if ($clear) {
  $deleted = $connection->delete('anklagerupdate_messages')
    ->condition('guid', 'TEST-%', 'LIKE')
    ->execute();
  if (!$no_invalidate) {
    \Drupal\Core\Cache\Cache::invalidateTags(['anklagerupdate_messages']);
  }
  $logger->info('Seed script cleared @n test rows (env=@env).', ['@n' => $deleted, '@env' => $env]);
  echo "Cleared $deleted test rows" . ($no_invalidate ? ' (cache NOT invalidated)' : ' (cache invalidated)') . ".\n";
  exit(0);
}

// Random 5-letter token to make each seed run identifiable.
$word = substr(str_shuffle('abcdefghijklmnopqrstuvwxyz'), 0, 5);

// Fake messages to insert.
$now = time();
$messages = [
  [
    'publisher_id'   => '13563078',
    'publisher_name' => 'Anklagemyndigheden',
    'title'          => "[TEST:$word] Pressemeddelelse om ny sag",
    'description'    => 'Dette er en testbesked til verifikation af RSS-import og cacheugyldiggørelse.',
    'link'           => 'https://example.com/test-1',
    'category'       => 'Pressemeddelelse',
    'pub_date'       => $now - 600,
  ],
  [
    'publisher_id'   => '13563079',
    'publisher_name' => 'Rigsadvokaten',
    'title'          => "[TEST:$word] Orientering fra Rigsadvokaten",
    'description'    => 'Endnu en testbesked fra Rigsadvokaten til kontrol af blokvisning.',
    'link'           => 'https://example.com/test-2',
    'category'       => 'Orientering',
    'pub_date'       => $now - 300,
  ],
  [
    'publisher_id'   => '13563080',
    'publisher_name' => 'Statsadvokaten i København',
    'title'          => "[TEST:$word] Afgørelse i straffesag",
    'description'    => 'Testbesked nummer tre – kontrollerer paginering og filtrering efter udgiver.',
    'link'           => 'https://example.com/test-3',
    'category'       => 'Afgørelse',
    'pub_date'       => $now,
  ],
];

$inserted = 0;
foreach ($messages as $i => $msg) {
  $guid = 'TEST-' . ($i + 1) . '-' . time();

  $connection->insert('anklagerupdate_messages')
    ->fields([
      'guid'           => $guid,
      'publisher_id'   => $msg['publisher_id'],
      'publisher_name' => $msg['publisher_name'],
      'title'          => $msg['title'],
      'description'    => $msg['description'],
      'link'           => $msg['link'],
      'pub_date'       => $msg['pub_date'],
      'category'       => $msg['category'],
      'created'        => $now,
      'updated'        => $now,
    ])
    ->execute();

  $inserted++;
}

$logger->info('Seed script inserted @n test messages (env=@env).', ['@n' => $inserted, '@env' => $env]);
if (!$no_invalidate) {
  \Drupal\Core\Cache\Cache::invalidateTags(['anklagerupdate_messages']);
  echo "Inserted $inserted test messages. Cache invalidated.\n";
}
else {
  echo "Inserted $inserted test messages. Cache NOT invalidated – run php run-rss-import.php test to bust it.\n";
}


