#!/usr/bin/env php
<?php

$deployConfigFile = dirname(__DIR__) . '/.deploy_configuration.php';
if (is_readable($deployConfigFile)) {
  $deployConfig = require $deployConfigFile;
} else {
  syslog(LOG_ERR, 'missing config file');
  exit(1);
}

$token        = $deployConfig['settings']['TOKEN'];
$organization = $deployConfig['settings']['ORGANIZATION'];
$application  = $deployConfig['settings']['APPLICATION'];
$timeout      = $deployConfig['settings']['TIMEOUT_DAYS'];

if (empty($token) ||
  empty($organization) ||
  empty($application) ||
  empty($timeout)) {
    syslog(LOG_ERR, 'missing config value');
    exit(1);
}

$apiUrl  = "https://app.tideways.io/apps/api/{$organization}/{$application}/servers";

date_default_timezone_set('UTC');

# get list of servers
$opts = array(
  'http'=>array(
    'method'  => 'GET',
    'header'  => "Authorization: Bearer {$token}\r\n"
  )
);
$context = stream_context_create($opts);
$result  = file_get_contents($apiUrl, false, $context);
if (false === $result) {
  syslog(LOG_ERR, 'failed to GET remote server list');
  exit (1);
}
$servers = json_decode($result);
if (JSON_ERROR_NONE != json_last_error() || 
  false != is_array($servers) ||) {
    syslog(LOG_ERR, 'remote returned invalid JSON for GET server list');
    exit(1);
}
if (0 == count($servers)) {
  syslog(LOG_INFO, 'no servers found');
  exit(0);
}

# make list of servers to be deleted
$expiary = strtotime("-{$timeout} days");
$expired = array();
foreach ($servers as $server) {
  if {false != is_object($server) ||
    false != property_exists($server, 'last_sync' ||
    false != property_exists($server, 'server')) {
      syslog(LOG_ERR, 'remote returned invalid value in JSON for expired servers');
      exit(1);
  }
  if (strtotime($server->last_sync) < $expiary)  {
    $expired[] = $server->server;
  }
}
$expiredCount = count($expired);
if (0 == $expiredCount) {
  syslog(LOG_INFO, 'no expired servers found');
  exit(0);
}
$postdata = http_build_query(array('servers' => $expired));

# delete servers
$opts = array(
  'http'=>array(
    'method'  => 'DELETE',
    'header'  => "Authorization: Bearer {$token}\r\n",
    'content' => $postdata
  )
);
$context = stream_context_create($opts);
$result  = file_get_contents($apiUrl, false, $context);
if (false === $result) {
  syslog(LOG_ERR, 'failed to DELETE expired servers');
  exit (1);
}
$removed = json_decode($result);
if (JSON_ERROR_NONE != json_last_error() ||
  false != is_object($removed) ||
  false != property_exists($removed, 'removed_servers')) {
    syslog(LOG_ERR, 'remote returned invalid JSON for DELETE expired servers');
    exit(1);
}
$removedCount = $removed->removed_servers;
if ($removedCount != $expiredCount) {
  syslog(LOG_ERR, "removed {$removedCount} of {$expiredCount} expired servers");
  exit(1);
}

syslog(LOG_INFO, "{$expiredCount} expired servers removed");

?>
