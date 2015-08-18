#!/usr/bin/env php
<?php

$token        = '';
$organization = '';
$application  = '';

$timeout      = 7;

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
$servers = json_decode($result);
if (0 == count($servers)) {
  exit(0);
}

# make list of servers to be deleted
$expiary = strtotime("-{$timeout} days");
$expired = array();
foreach ($servers as $server) {
  if (strtotime($server->last_sync) < $expiary)  {
    $expired[] = $server->server;
  }
}
if (0 == count($expired)) {
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
file_get_contents($apiUrl, false, $context);

?>
