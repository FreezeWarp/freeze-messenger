<?php
/* FreezeMessenger Copyright © 2017 Joseph Todd Parsons

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
$request = \Fim\Utilities::sanitizeGPC('g', array(
    'sha256hash' => array(
        'cast' => 'string',
        'require' => false,
        'default' => '',
    ),

    // Because file.php must NOT require a session token, we want to allow APIs to define these separately (and, yes, this is very much by design -- again, the parental control system is not locked-down).
    'parentalAge' => array(
        'cast' => 'int',
        'valid' => \Fim\Config::$parentalAges,
        'default' => \Fim\Config::$parentalAgeDefault,
    ),

    'parentalFlags' => array(
        'cast' => 'list',
        'valid' => \Fim\Config::$parentalFlags,
        'default' => \Fim\Config::$parentalFlagsDefault,
    ),

    'thumbnailWidth' => array(
        'cast' => 'int',
    ),

    'thumbnailHeight' => array(
        'cast' => 'int',
    ),
));
$includeThumbnails = isset($request['thumbnailHeight']) || isset($request['thumbnailWidth']);


$file = \Fim\Database::instance()->getFiles(array(
    'sha256hashes' => $request['sha256hash'] ? array($request['sha256hash']) : array(),
    'includeContent' => ($includeThumbnails ? false : true),
    'includeThumbnails' => ($includeThumbnails ? true : false),
))->getAsObjects($includeThumbnails ? '\\Fim\\FileThumbnail' : '\\Fim\\File');

if ($includeThumbnails) {
    $filesIndexed = [];

    // If this has happened, the thumbnail most likely didn't finish resizing/uploading at the time the file was requested. Retry for the full-version instead.
    if (count($file) === 0) {
        $file = \Fim\Database::instance()->getFiles(array(
            'sha256hashes' => $request['sha256hash'] ? array($request['sha256hash']) : array(),
            'includeContent' => true,
        ))->getAsObject('\\Fim\\File');
    }

    else {
        // Only one file found. Somewhat common, if only one thumbnail has generated so far.
        if (count($file) === 1) {
            $file = $file[0];
        }

        // Sort between the multiple thumbnails, trying to find the one with the closest match. This algorithm is probably imperfect. It still could use someone to give it a lookover.
        else {
            foreach ($file AS $index => $f) {
                $score = ($request['thumbnailWidth'] ? abs($request['thumbnailWidth'] - $f->width) / $f->width : 1) * ($request['thumbnailHeight'] ? abs($request['thumbnailHeight'] - $f->height) / $f->height : 1);

                $filesIndexed[$score * 1000] = $index;
            }
            ksort($filesIndexed);

            $file = $file[array_values($filesIndexed)[0]];
        }

        $thumbnail = \Fim\Database::instance()->select([\Fim\Database::$sqlPrefix . "fileVersionThumbnails" => "versionId, scaleFactor, contents"], ["versionId" => $file->versionId, "scaleFactor" => $file->scaleFactor])->getAsArray(false);

        $file->contents = $thumbnail['contents'];
        $file->name = $file->name . '.thumb.jpg';
    }
}

else {
    $file = $file[0];
}



/* Set File Security Controls */
// Our mimetype should be considered authoritative. We do this to allow flexibility in controlling file uploads; as we use mimetypes to define content controls, being able to subvert a mimetype would substantially weaken our assumptions.
// (For instance, when a user uploads a .jpg file, we don't test to see if it is, in-fact, a JPEG file. This would be very difficult to do for all files, of-course, and we want to allow administrators flexibility in deciding what filetypes can be uploaded. Instead, we just assume that it is, in-fact, a JPEG file, and send it with the JPEG mimetype (as defined by the administrator). The client sees this mimetype and knows that it should load it as a JPEG. If it assumes that it is, instead, a textfile, then the filesize limit on textfiles could be ignored by disguising them as JPEGs. If it assumes that it is an EXE, then users could upload viruses disguised as JPEG files.)
header('X-Content-Type-Options: nosniff');

// Some extra protection to prevent our files from being used in XSS.
header("X-XSS-Protection: 1; mode=block");

// We don't really want our files to be used for rendering HTML pages.
if (\Fim\Config::$blockFrames) header("X-Frame-Options: DENY");


/* Set File Caching Controls */
// Because we identify files by their SHA hashes, and internally expect no collisions to occur, tell the browsers that files should never have to be revalidated.
header("Cache-Control: public, max-age=365000000, immutable");



/* Output File */
header('Content-Type: ' . $file->mime);
echo $file->contents;
?>