<?php
/* FreezeMessenger Copyright © 2014 Joseph Todd Parsons

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

/**
 * Displays an Database-Stored File
 * Though it follows much of the same logic, this is not part of the standard API as it does not return data in the standard way, and thus some global directives do not work.
 *
 * @param timestamp time
 * @param string md5hash
 * @param string sha256hash
 * @param string fileId
*/

$reqPhrases = true;

require('global.php');



/* Get Request Data */
$request = fim_sanitizeGPC('g', array(
  'time' => array(
    'cast' => 'int',
    'require' => false,
    'default' => 0,
  ),

  'md5hash' => array(
    'cast' => 'string',
    'require' => false,
  ),

  'sha256hash' => array(
    'cast' => 'string',
    'require' => false,
  ),

  'fileId' => array(
    'cast' => 'int',
    'require' => false,
    'default' => 0,
  ),
  
  'userId' => array(
    'cast' => 'int',
    'require' => false,
    'default' => $user['userId'],
  ),

  // Because file.php must NOT require a session token, we want to allow APIs to define these seperately (and, yes, this is very much by design -- again, the parental control system is not locked-down).
  'parentalAge' => array(
    'cast' => 'int',
    'valid' => $config['parentalAges'],
    'default' => $config['parentalAgeDefault'],
  ),

  'parentalFlags' => array(
    'cast' => 'csv',
    'valid' => $config['parentalFlags'],
  ),
));

$queryParts['fileSelect']['columns'] = array(
  "{$sqlPrefix}files" => 'userId, fileId ffileId, fileType, parentalAge, parentalFlags',
  "{$sqlPrefix}fileVersions" => 'fileId vfileId, salt, iv, contents, md5hash, sha256hash, time',
);
$queryParts['fileSelect']['conditions'] = array(
  'both' => array(
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
);


/* Get File from Database */
if ($request['time'] && $request['fileId']) {
  $queryParts['fileSelect']['conditions']['both'][] = array(
    'type' => 'e',
    'left' => array(
      'type' => 'column',
      'value' => 'time'
    ),
    'right' => array(
      'type' => 'int',
      'value' => (int) $request['time'],
    ),
  );
  $queryParts['fileSelect']['conditions']['both'][] = array(
    'type' => 'e',
    'left' => array(
      'type' => 'column',
      'value' => 'vfileId'
    ),
    'right' => array(
      'type' => 'int',
      'value' => (int) $request['fileId'],
    ),
  );
}
elseif ($request['fileId']) {
  $queryParts['fileSelect']['conditions']['both'][] = array(
    'type' => 'e',
    'left' => array(
      'type' => 'column',
      'value' => 'vfileId'
    ),
    'right' => array(
      'type' => 'int',
      'value' => (int) $request['fileId'],
    ),
  );
}
elseif ($request['sha256hash']) {
  $queryParts['fileSelect']['conditions']['both'][] = array(
    'type' => 'e',
    'left' => array(
      'type' => 'column',
      'value' => 'sha256hash'
    ),
    'right' => array(
      'type' => 'string',
      'value' => $request['sha256hash'],
    ),
  );
}
elseif ($request['md5hash']) {
  $queryParts['fileSelect']['conditions']['both'][] = array(
    'type' => 'e',
    'left' => array(
      'type' => 'column',
      'value' => 'md5hash'
    ),
    'right' => array(
      'type' => 'string',
      'value' => $request['sha256hash'],
    ),
  );
}
else {
  die('No criteria specified');
}


$file = $database->select(
  $queryParts['fileSelect']['columns'],
  $queryParts['fileSelect']['conditions'],
  false,
  1);
$file = $file->getAsArray(false);




/* Start Processing */
if ($config['parentalEnabled']) {
  if (isset($request['parentalAge'])) $user['parentalAge'] = $request['parentalAge'];
  if (isset($request['parentalFlags'])) $user['parentalFlags'] = implode(',', $request['parentalFlags']);

  if ($file['parentalAge'] > $user['parentalAge']) $parentalBlock = true;
  elseif (fim_inArray(explode(',', $user['parentalFlags']), explode(',', $file['parentalFlags']))) $parentalBlock = true;
}

if ($request['userId'] && ($request['userId'] !== $file['userId'])) {
  $parentalBlock = false; // Disable the parental block if the user themself uploaded the image.
}

if ($parentalBlock) {
  $file['contents'] = ''; // TODO: Placeholder

  header('Content-Type: ' . $file['fileType']);
  echo $file['contents'];
}
else {
  if ($file['salt']) {
    $file = fim_decrypt($file,'contents');
  }
  else {
    $file['contents'] = base64_decode($file['contents']);
  }

  header('Content-Type: ' . $file['fileType']);
  echo $file['contents'];

//  print_r($file); var_dump($user);
}
?>