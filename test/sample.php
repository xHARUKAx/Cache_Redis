<?php
/**
 * Sample script for Cache_Redis
 */

require_once '../src/Redis.php';

$cache = new Cache_Redis();

$cacheId = 'test';
if ($data = $cache->get($cacheId)) {
  var_dump($data);
} else {
  $data = array(
    'id' => 'this is a test',
  );
  $cache->save($data, $cacheId);
}
$data2 = $cache->get($cacheId);
var_dump($data2);
?>
