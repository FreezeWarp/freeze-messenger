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

if (!defined('WEBPRO_INMOD')) {
  die();
}
else {
  $request = fim_sanitizeGPC(array(
    'request' => array(
      'extension' => array(
        'context' => array(
          'type' => 'int',
        ),
      ),
    ),

    'post' => array(
      'extension' => array(
        'context' => array(
          'type' => 'string',
        ),
      ),

      'mime' => array(
        'context' => array(
          'type' => 'string',
        ),
      ),

      'maxSize' => array(
        'context' => array(
          'type' => 'string',
        ),
      ),

      'container' => array(
        'context' => array(
          'type' => 'string',
        ),
        'valid' => array('video', 'image', 'audio', 'text', 'html', 'archive', 'other'),
        'default' => 'other',
      ),
    ),
  ));

  if ($user['adminDefs']['modFiles']) {
    switch ($_GET['do2']) {
      case 'view':
      case false:
      $uploadTypes = $database->select(array(
        "{$sqlPrefix}uploadTypes" => "extension, container, mime, maxSize",
      ));
      $uploadTypes = $uploadTypes->getAsArray(true);

      foreach ($uploadTypes AS $uploadType) {
        $rows .= "<tr><td>$uploadType[extension]</td><td>$uploadType[container]</td><td>$uploadType[mime]</td><td>$uploadType[maxSize]</td><td align=\"center\"><a href=\"./moderate.php?do=ftypes&do2=edit&extension=$uploadType[extension]\"><img src=\"./images/document-edit.png\" /></td></tr>";
      }

      echo container('File types<a href="./moderate.php?do=ftypes&do2=edit"><img src="./images/document-new.png" style="float: right;" /></a>', '<table class="page rowHover" border="1">
  <thead>
    <tr class="hrow ui-widget-header">
      <td>File type</td>
      <td>Container</td>
      <td>Mime Type</td>
      <td>Max Size</td>
      <td>Actions</td>
    </tr>
  </thead>
  <tbody>
' . $rows . '
  </tbody>
</table>');
      break;

      case 'edit':
      if ($request['extension']) {
        $uploadType = $database->getUploadType($request['extension']);
        $title = 'Edit File type "' . $uploadType['extension'] . '"';
      }
      else {
        $uploadType = array(
          'extension' => '',
          'extension' => 0,
          'mime' => '',
          'maxSize' => '',
        );

        $title = 'Create New FileType';
      }

      $selectBlock = fimHtml_buildSelect('container', array(
        'video' => 'Video (e.g. .mpeg)',
        'audio' => 'Audio (e.g. .mp3)',
        'image' => 'Image (e.g. .png)',
        'text' => 'Text (e.g. .txt)',
        'html' => 'HTML (e.g. .html)',
        'archive' => 'Archive (e.g. .zip)',
        'other' => 'Other',
      ), $uploadType['container']);

      echo container($title, '<form action="./moderate.php?do=ftypes&do2=edit2" method="post">
  <table border="1" class="ui-widget page">
    <tr>
      <td>Extension:</td>
      <td>' . ($request['extension'] ? '<input type="hidden" name="extension" value="' . $uploadType['extension'] . '" />' . $uploadType['extension'] : '<input type="text" name="extension" />') . '</td>
    </tr>
    <tr>
      <td>Container:</td>
      <td>
        ' . $selectBlock . '<br />
        <small>The general type of the file, used by clients to determine how to display the upload.</small>
      </td>
    </tr>
    <tr>
      <td>Mimetype:</td>
      <td>
        <input type="text" name="mime" value="' . $uploadType['mime'] . '" /><br />
        <small>The mimetype of the file. "application/octet-stream" will usually work if you are unsure.</small>
      </td>
    </tr>
    <tr>
      <td>Maximum Size:</td>
      <td>
        <input type="text" name="maxSize" value="' . $uploadType['maxSize'] . '" /><br />
        <small>The maximum size of the file in bytes.<br />Quick Ref: 1KB = 1024B, 1MB = 10,485,76B, 1GB = 1,073,741,824B.</small>
      </td>
    </tr>
  </table>

  <button type="submit">Submit</button>
  <button type="reset">Reset</button>
</form>');
      break;

      case 'edit2':
      $uploadType = $database->getUploadType($request['extension']);

      if ($request['extension']) {
        $database->modLog('editFileType', $uploadType['wordId']);
        $database->fullLog('editFileType', array('uploadType' => $uploadType));

        $database->update("{$sqlPrefix}uploadTypes", array(
          'container' => $request['container'],
          'mime' => $request['mime'],
          'maxSize' => $request['maxSize'],
        ), array(
          'extension' => $request['extension'],
        ));

        echo container('File Type Updated', 'The bbcode has been updated.<br /><br /><form method="post" action="moderate.php?do=ftypes"><button type="submit">Return to Viewing BBCode</button></form>');
      }
      else {
        $uploadType = array(
          'extension' => $request['extension'],
          'container' => $request['container'],
          'mime' => $request['mime'],
          'maxSize' => $request['maxSize'],
        );

        $database->insert("{$sqlPrefix}uploadTypes", $uploadType);
        $uploadType['extension'] = $database->insertId;

        $database->modLog('createFileType', $uploadType['wordId']);
        $database->fullLog('createFileType', array('uploadType' => $uploadType));

        echo container('File Type Added', 'The bbcode has been added.<br /><br /><form method="post" action="moderate.php?do=ftypes"><button type="submit">Return to Viewing File Types</button></form>');
      }
      break;

      case 'delete':
      $uploadType = $database->getUploadType($request['extension']);

      if ($uploadType) {
        $database->modLog('deleteFileType', $uploadType['wordId']);
        $database->fullLog('deleteFileType', array('uploadType' => $uploadType));

        $database->delete("{$sqlPrefix}uploadTypes", array(
          'extension' => $request['extension'],
        ));

        echo container('File Type Deleted', 'The bbcode entry has been deleted.<br /><br /><form method="post" action="moderate.php?do=ftypes"><button type="submit">Return to Viewing File Types</button></form>');
      }
      else {
        echo container('File Type Not Found', 'The bbcode specified was not found.<br /><br /><form method="post" action="moderate.php?do=ftypes"><button type="submit">Return to Viewing File Types</button></form>');
      }
      break;
    }
  }
  else {
    echo 'You do not have permission to manage File Types.';
  }
}
?>