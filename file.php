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



/* Get Request Data */
$request = fim_sanitizeGPC(array(
  'get' => array(
    'time' => array(
      'type' => 'string',
      'require' => false,
      'default' => 0,
      'context' => array(
        'type' => 'int',
      ),
    ),

    'hash' => array(
      'type' => 'string',
      'require' => false,
    ),

    'fileId' => array(
      'type' => 'string',
      'require' => false,
      'default' => 0,
      'context' => array(
        'type' => 'int',
      ),
    ),
  ),
));



/* Get File from Database */
if ($request['time'] && $request['fileId']) {
  //$file = dbRows("SELECT f.mime, v.salt, v.iv, v.contents, v.time FROM {$sqlPrefix}files AS f, {$sqlPrefix}fileVersions AS v WHERE v.fileid = $fileid AND UNIX_TIMESTAMP(v.time) = $time AND f.fileId = v.fileid LIMIT 1");
  $file = $database->select(
    array(
      "{$sqlPrefix}files" => array(
        'fileId' => 'ffileId',
        'fileType' => 'fileType',
      ),
      "{$sqlPrefix}fileVersions" => array(
        'fileId' => 'vfileId',
        'salt' => 'salt',
        'iv' => 'iv',
        'contents' => 'contents',
        'md5hash' => 'md5hash',
        'sha256hash' => 'sha256hash',
        'time' => array(
          'context' => 'time',
          'name' => 'time',
        ),
      ),
    ),
    array(
      'both' => array(
        array(
          'type' => 'e',
          'left' => array(
            'type' => 'column',
            'value' => 'time'
          ),
          'right' => array(
            'type' => 'int',
            'value' => (int) $request['time'],
          ),
        ),
        array(
          'type' => 'e',
          'left' => array(
            'type' => 'column',
            'value' => 'ffileId',
          ),
          'right' => array(
            'type' => 'column',
            'value' => 'vfileId',
          ),
        ),
      ),
    ),
    false,
    false,
    1
  );
}
elseif ($request['fileId']) {
//  $file = dbRows("SELECT f.mime, v.salt, v.iv, v.contents, v.time FROM {$sqlPrefix}files AS f, {$sqlPrefix}fileVersions AS v WHERE v.fileid = $fileid AND f.fileId = v.fileid ORDER BY v.time DESC LIMIT 1");

  $file = $database->select(
    array(
      "{$sqlPrefix}files" => array(
        'fileId' => 'ffileId',
        'fileType' => 'fileType',
      ),
      "{$sqlPrefix}fileVersions" => array(
        'fileId' => 'vfileId',
        'salt' => 'salt',
        'iv' => 'iv',
        'md5hash' => 'md5hash',
        'sha256hash' => 'sha256hash',
        'contents' => 'contents',
        'time' => array(
          'context' => 'time',
          'name' => 'time',
        ),
      ),
    ),
    array(
      'both' => array(
        array(
          'type' => 'e',
          'left' => array(
            'type' => 'column',
            'value' => 'vfileId'
          ),
          'right' => array(
            'type' => 'int',
            'value' => (int) $request['fileId'],
          ),
        ),
        array(
          'type' => 'e',
          'left' => array(
            'type' => 'column',
            'value' => 'ffileId',
          ),
          'right' => array(
            'type' => 'column',
            'value' => 'vfileId',
          ),
        ),
      ),
    ),
    false,
    false,
    1
  );
}
elseif ($request['hash']) {
  $file = $database->select(
    array(
      "{$sqlPrefix}files" => array(
        'fileId' => 'ffileId',
        'fileType' => 'fileType',
      ),
      "{$sqlPrefix}fileVersions" => array(
        'fileId' => 'vfileId',
        'salt' => 'salt',
        'iv' => 'iv',
        'md5hash' => 'md5hash',
        'sha256hash' => 'sha256hash',
        'contents' => 'contents',
        'time' => array(
          'context' => 'time',
          'name' => 'time',
        ),
      ),
    ),
    array(
      'both' => array(
        array(
          'type' => 'e',
          'left' => array(
            'type' => 'column',
            'value' => 'sha256hash'
          ),
          'right' => array(
            'type' => 'string',
            'value' => $request['hash'],
          ),
        ),
        array(
          'type' => 'e',
          'left' => array(
            'type' => 'column',
            'value' => 'ffileId',
          ),
          'right' => array(
            'type' => 'column',
            'value' => 'vfileId',
          ),
        ),
      ),
    ),
    false,
    false,
    1
  )->getAsArray(false);
}
else {
  die('No criteria specified');
}



/* Start Processing */
if ($file['salt']) {
  $file = fim_decrypt($file,'contents');
}
else {
  $file['contents'] = base64_decode($file['contents']);
}


header('Content-Type: ' . $file['fileType']);
echo $file['contents'];
?>