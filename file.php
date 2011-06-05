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


eval(hook('fileStart'));


$hash = mysqlEscape($_GET['hash']);
$fileid = intval($_GET['fileid']);
$time = intval($_GET['time']);


eval(hook('filePrequery'));


if ($time && $fileid) {
  $file = sqlArr("SELECT f.size, f.mime, v.salt, v.iv, v.contents, v.time FROM {$sqlPrefix}files AS f, {$sqlPrefix}fileVersions AS v WHERE v.fileid = $fileid AND UNIX_TIMESTAMP(v.time) = $time AND f.id = v.fileid LIMIT 1");
}
elseif ($fileid) {
  $file = sqlArr("SELECT f.size, f.mime, v.salt, v.iv, v.contents, v.time FROM {$sqlPrefix}files AS f, {$sqlPrefix}fileVersions AS v WHERE v.fileid = $fileid AND f.id = v.fileid ORDER BY v.time DESC LIMIT 1");
}
elseif ($hash) {
  $file = sqlArr("SELECT f.size, f.mime, v.salt, v.iv, v.contents, v.time FROM {$sqlPrefix}files AS f, {$sqlPrefix}fileVersions AS v WHERE v.md5hash = '$hash' AND f.id = v.fileid LIMIT 1");
}


eval(hook('filePostquery'));


$file = fim_decrypt($file,'contents');


eval(hook('filePredisplay'));


header('Content-Type: ' . $file['mime']);
echo $file['contents'];


eval(hook('fileEnd'));
?>