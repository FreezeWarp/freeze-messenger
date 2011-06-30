<?php
/* FreezeMessenger Copyright © 2011 Joseph Todd Parsons

 * This program is free software: you can redistribute it and/or modify
   it under the terms of the GNU General Public License as published by
   the Free Software Foundation, either version 3 of the License, or
   (at your option) any later version.

 * This program is distributed in the hope that it will be useful,
   but WITHOUT ANY WARRANTY; without even the implied warranty of
   MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
   GNU General Public License for more details.

 * You should have received a copy of the GNU General Public License
   along with this program.  If not, see <http://www.gnu.org/licenses/>. */


$reqPhrases = true;
$reqHooks = true;
require_once('global.php');


eval(hook('file_start'));


$hash = dbEscape($_GET['sha256hash']);
$fileid = (int) $_GET['fileid'];
$time = (int) $_GET['time'];


eval(hook('file_prequery'));


if ($time && $fileid) {
  $file = dbRows("SELECT f.mime, v.salt, v.iv, v.contents, v.time FROM {$sqlPrefix}files AS f, {$sqlPrefix}fileVersions AS v WHERE v.fileid = $fileid AND UNIX_TIMESTAMP(v.time) = $time AND f.fileId = v.fileid LIMIT 1");
}
elseif ($fileid) {
  $file = dbRows("SELECT f.mime, v.salt, v.iv, v.contents, v.time FROM {$sqlPrefix}files AS f, {$sqlPrefix}fileVersions AS v WHERE v.fileid = $fileid AND f.fileId = v.fileid ORDER BY v.time DESC LIMIT 1");
}
elseif ($hash) {
  $file = dbRows("SELECT f.mime, v.salt, v.iv, v.contents, v.time FROM {$sqlPrefix}files AS f, {$sqlPrefix}fileVersions AS v WHERE v.sha256hash = '$hash' AND f.fileId = v.fileid LIMIT 1");
}

eval(hook('file_postquery'));

if ($file['salt']) {
  $file = fim_decrypt($file,'contents');
}
else {
  $file['contents'] = base64_decode($file['contents']);
}

eval(hook('file_predisplay'));



header('Content-Type: ' . $file['mime']);
echo $file['contents'];


eval(hook('file_end'));
?>