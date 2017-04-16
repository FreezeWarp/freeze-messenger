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

$ignoreLogin = true;
require('global.php');



/* Get Request Data */
$request = fim_sanitizeGPC('g', array(
    'sha256hash' => array(
        'cast' => 'string',
        'require' => false,
        'default' => '',
    ),

    // Because file.php must NOT require a session token, we want to allow APIs to define these separately (and, yes, this is very much by design -- again, the parental control system is not locked-down).
    'parentalAge' => array(
        'cast' => 'int',
        'valid' => $config['parentalAges'],
        'default' => $config['parentalAgeDefault'],
    ),

    'parentalFlags' => array(
        'cast' => 'list',
        'valid' => $config['parentalFlags'],
        'default' => $config['parentalFlagsDefault'],
    ),

    'thumbnailWidth' => array(
        'cast' => 'int',
    ),

    'thumbnailHeight' => array(
        'cast' => 'int',
    ),
));
$includeThumbnails = $request['thumbnailHeight'] || $request['thumbnailWidth'];


$file = $database->getFiles(array(
    'sha256hashes' => $request['sha256hash'] ? array($request['sha256hash']) : array(),
    'fileIds' => $request['fileId'] ? array($request['fileId']) : array(),
    'vfileIds' => $request['vfileId'] ? array($request['vfileId']) : array(),
    'includeContent' => ($includeThumbnails ? false : true),
    'includeThumbnails' => ($includeThumbnails ? true : false),
))->getAsArray($includeThumbnails ? true : false);

if ($includeThumbnails) {
    $filesIndexed = [];

    /* This algorithm is probably imperfect. It still could use someone to give it a lookover. */
    foreach ($file AS $index => $f) {
        $score = ($request['thumbnailWidth'] ? abs($request['thumbnailWidth'] - $f['width']) / $f['width'] : 1) * ($request['thumbnailHeight'] ? abs($request['thumbnailHeight'] - $f['height']) / $f['height'] : 1);

        $filesIndexed[$score * 1000] = $index;
    }
    ksort($filesIndexed);

    $file = $file[array_values($filesIndexed)[0]];

    $thumbnail = $database->select([$database->sqlPrefix . "fileVersionThumbnails" => "versionId, scaleFactor, contents, iv, salt"], ["versionId" => $file['versionId'], "scaleFactor" => $file['scaleFactor']])->getAsArray(false);
    $file['contents'] = fim_decrypt($thumbnail['contents'], $thumbnail['salt'], $thumbnail['iv']);
}





/* Start Processing */


if ($config['parentalEnabled']) {
    if ($file['parentalAge'] > $request['parentalAge']) $parentalBlock = true;
    elseif (fim_inArray($request['parentalFlags'], explode(',', $file['parentalFlags']))) $parentalBlock = true;
}

if ($parentalBlock) {
    $file['contents'] = ''; // TODO: Placeholder

    header('Content-Type: ' . $file['fileType']);
    echo $file['contents'];
}
else {
    $file['contents'] = fim_decrypt($file['contents'], $file['salt'], $file['iv']);

    header('Content-Type: ' . $file['fileType']);
    echo $file['contents'];
}
?>