<?php
require_once('global.php');

$hash = mysqlEscape($_GET['hash']);
$fileid = intval($_GET['fileid']);
$time = intval($_GET['time']);

if ($time && $fileid) {
  $file = sqlArr("SELECT f.size, f.mime, v.salt, v.iv, v.contents, v.time FROM {$sqlPrefix}files AS f, {$sqlPrefix}fileVersions AS v WHERE v.fileid = $fileid AND UNIX_TIMESTAMP(v.time) = $time AND f.id = v.fileid LIMIT 1");
}
elseif ($fileid) {
  $file = sqlArr("SELECT f.size, f.mime, v.salt, v.iv, v.contents, v.time FROM {$sqlPrefix}files AS f, {$sqlPrefix}fileVersions AS v WHERE v.fileid = $fileid AND f.id = v.fileid ORDER BY v.time DESC LIMIT 1");
}
elseif ($hash) {
  $file = sqlArr("SELECT f.size, f.mime, v.salt, v.iv, v.contents, v.time FROM {$sqlPrefix}files AS f, {$sqlPrefix}fileVersions AS v WHERE v.md5hash = '$hash' AND f.id = v.fileid LIMIT 1");
}

$file = vrim_decrypt($file,'contents');

header('Content-Type: ' . $file['mime']);
echo $file['contents'];
?>