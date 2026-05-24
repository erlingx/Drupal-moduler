<?php
/**
 * @file
 * Run ONLY the AnklagerUpdate RSS import from CLI.
 *
 * This is a lightweight alternative to full Drupal cron.
 * Use this for frequent (every 10 min) RSS feed checks.
 * Full Drupal cron should run separately (every hour).
 *
 * Usage:
 *   php run-rss-import.php test
 *   php run-rss-import.php prd
 *
 * If no argument is given, defaults to TEST.
 */

// Log all PHP errors to a file next to this script.
$logFile = __DIR__ . '/rss-import-error.log';
ini_set('log_errors', 1);
ini_set('error_log', $logFile);
ini_set('display_errors', 0);
error_reporting(E_ALL);

// Also catch fatal errors / uncaught exceptions.
set_exception_handler(function (\Throwable $e) use ($logFile) {
  $msg = date('Y-m-d H:i:s') . ' FATAL: ' . get_class($e) . ': ' . $e->getMessage()
    . ' in ' . $e->getFile() . ':' . $e->getLine() . "\n"
    . $e->getTraceAsString() . "\n";
  file_put_contents($logFile, $msg, FILE_APPEND);
  echo $msg;
  exit(1);
});

// Determine environment from command line argument.
$env = strtolower($argv[1] ?? 'test');

file_put_contents($logFile, date('Y-m-d H:i:s') . " START env=$env\n", FILE_APPEND);

// Environment configuration.
$environments = [
  'test' => [
    'host' => '31.31.83.25',
    'port' => '80',
    'https' => false,
  ],
  'prd' => [
    'host' => 'anklagemyndigheden.dk',
    'port' => '443',
    'https' => true,
  ],
];

if (!isset($environments[$env])) {
  file_put_contents($logFile, date('Y-m-d H:i:s') . " ERROR: Unknown environment '$env'\n", FILE_APPEND);
  exit(1);
}

$config = $environments[$env];

define('DRUPAL_DIR', __DIR__);
chdir(DRUPAL_DIR);

// Set server variables so Drupal can find the correct site directory.
$_SERVER['HTTP_HOST'] = $config['host'];
$_SERVER['REMOTE_ADDR'] = '127.0.0.1';
$_SERVER['REQUEST_URI'] = '/';
$_SERVER['REQUEST_METHOD'] = 'GET';
$_SERVER['SERVER_NAME'] = $config['host'];
$_SERVER['SERVER_PORT'] = $config['port'];
$_SERVER['SCRIPT_NAME'] = '/index.php';
if ($config['https']) {
  $_SERVER['HTTPS'] = 'on';
}

// Autoloader must be captured as a return value.
$autoloader = require DRUPAL_DIR . '/autoload.php';

use Drupal\Core\DrupalKernel;
use Symfony\Component\HttpFoundation\Request;

$request = Request::createFromGlobals();
$kernel = DrupalKernel::createFromRequest($request, $autoloader, 'prod');
$kernel->boot();
$kernel->preHandle($request);

$logger = \Drupal::logger('anklagerupdate_rss');
$logger->info('RSS import started (env=@env).', ['@env' => $env]);

// Run ONLY the RSS import (not full Drupal cron).
// Load the module file to access the fetch function.
\Drupal::moduleHandler()->loadInclude('anklagerupdate_rss', 'module');

// Call the RSS import directly.
anklagerupdate_rss_import();

// Invalidate the block cache tag so new messages appear immediately,
// without needing to disable site-wide caching.
\Drupal\Core\Cache\Cache::invalidateTags(['anklagerupdate_messages']);
$logger->info('Cache tag anklagerupdate_messages invalidated.');

$logger->info('RSS import completed.');
file_put_contents($logFile, date('Y-m-d H:i:s') . " DONE\n", FILE_APPEND);

