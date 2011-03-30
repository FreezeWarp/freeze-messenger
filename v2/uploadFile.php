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

ini_set('max_execution_time','10');
error_reporting(E_ALL);

require_once('global.php');

$room = intval($_GET['room']);
$room = sqlArr("SELECT * FROM {$sqlPrefix}rooms WHERE id = $room");


if (!hasPermission($room,$user)) {
  $errorMessage = 'You do not have permission to do this.';
}

switch ($_POST['method']) {
  case 'url':
  if ($_POST['linkEmail']) {
    $flag = 'email';

    if ($parseFlags) {
      $message = $_POST['linkEmail'];
    }
    else {
      $linkUrl = $_POST['linkEmail'];
      $linkText = $_POST['linkEmail'];

      $message = "[email]{$linkText}[/email]";
    }
  }
  elseif ($_POST['linkUrl']) {
    $flag = 'link';

    if ($parseFlags) {
      $message = $_POST['linkUrl'];
    }
    else {
      $linkUrl = $_POST['linkUrl'];

      if ($_POST['linkText']) $linkText = $_POST['linkText'];
      else $linkText = $_POST['linkUrl'];

      $message = "[url={$linkUrl}]{$linkText}[/url]";
    }
  }
  else {
    $errorMessage = 'You did not specify a URL.';
  }
  break;

  case 'youtube':
  if ($_POST['urlLink']) {
    $message = '[url]' . $_POST['urlLink'] . '[/url]';
  }
  elseif ($_POST['youtubeUpload'] && $_POST['youtubeUpload'] != 'http://') {
    if (preg_match('/^(http:\/\/|)(www\.|)youtube.com\/(.*)?v=([a-zA-Z0-9\-\_]+)(&|)(.*)$/i',$_POST['youtubeUpload'])) { // A youtube video
      $flag = 'video';

      if ($parseFlags) {
        $message = $_POST['youtubeUpload'];
      }
      else {
        $vPart = preg_replace('/^(.+)?v=([a-zA-Z0-9\-\_]+)(&|)(.*)$/i','$2',$_POST['youtubeUpload']);
        $message = '[youtube]' . $vPart . '[/youtube]';
      }
    }
    else {
      $errorMessage = 'The URL does not appear to be a Youtube video.';
    }
  }
  break;

  case 'image':
  if ($_POST['urlUpload'] && $_POST['urlUpload'] != 'http://') {
    $validTypes = array('image/gif','image/jpeg','image/png','image/pjpeg');
    $urlUpload = $_POST['urlUpload'];
    $ch = curl_init($urlUpload);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_exec($ch);
    $mime = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
    $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);

    if ($status != 200) {
      $errorMessage = 'That image does not exist or refuses to load.';
    }
    elseif (!in_array($mime,$validTypes)) {
      $errorMessage = 'That image is not of a valid type.';
    }
    else {
      if ($parseFlags) {
        $message = $urlUpload;
      }
      else {
        $message = '[img]' . $urlUpload . '[/img]';
      }
    }
  }
  else {
    $validTypes = array('image/gif','image/jpeg','image/png','image/pjpeg','application/octet-stream');
    $validExts = array('gif','jpg','jpeg','png');
    $extParts = explode('.',$_FILES['fileUpload']['name']);
    $ext = $extParts[count($extParts) - 1];

    $fileLocation = "/var/www/chatinterface/htdocs/v1/userdata/uploads/$user[userid]/" . preg_replace('/[^a-zA-Z0-9_\.]/','',$_FILES['fileUpload']['name']);
    $webLocation = "userdata/uploads/$user[userid]/" . preg_replace('/[^a-zA-Z0-9_\.]/','',$_FILES['fileUpload']['name']);

    if (!in_array($_FILES['fileUpload']['type'],$validTypes)) {
      $errorMessage = 'You must upload a PNG, GIF, or JPEG file.';
    }
    elseif (!in_array($ext,$validExts) && $_FILES['fileUpload']['type'] == 'application/octet-stream') {
      $errorMessage = 'You must upload a PNG, GIF, or JPEG file.';
    }
    elseif ($_FILES['fileUpload']['size'] > 4 * 1000 * 1000) {
      $errorMessage = 'The file you are trying to upload is too large.';
    }
    elseif ($_FILES['fileUpload']['error'] > 0) {
      $errorMessage = 'Other Error: ' . $_FILES['fileUpload']['error'];
    }
    else {
      if (file_exists($fileLocation)) $message = '[img]http://vrim.victoryroad.net/' . $webLocation . '[/img]';
      else {
        if (!is_dir("userdata/uploads/$user[userid]")) mkdir ("userdata/uploads/$user[userid]",0755);

        if (!move_uploaded_file($_FILES['fileUpload']['tmp_name'],$fileLocation)) {
          $errorMessage = 'Could not upload file. (' . $webLocation . ')';
        }
        else {
          $flag = 'image';

          if ($parseFlags) {
            $message = $webLocation;
          }
          else {
            $message = '[img]http://vrim.victoryroad.net/' . $webLocation . '[/img]';
          }
        }
      }
    }
  }
  break;
}

if (!$errorMessage) {
  require_once('functions/parserFunctions.php');
  sendMessage($message,$user,$room,$flag);
}
else {
  echo '<script type="text/javascript">window.top.window.alert(\'' . $errorMessage . '\');</script>';
}