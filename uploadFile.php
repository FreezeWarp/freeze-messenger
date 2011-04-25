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

$room = intval($_GET['room'] ?: $_POST['room']);
$room = sqlArr("SELECT * FROM {$sqlPrefix}rooms WHERE id = $room");

if (!hasPermission($room,$user) && $room) { // Can't post in that room.
  $errorMessage = 'You do not have permission to do this.';
}

elseif (!$room && !$_POST['method'] && $enableGeneralUploads) { // General upload; form.
  require_once('templateStart.php');
  require_once('functions/container.php');

?>
<?php

  function formatSize($size) {

  $fileSuffixes = array(
    'B',
    'KiB',
    'MiB',
    'GiB',
    'PiB',
    'EiB',
    'ZiB',
    'YiB',
  );

  $suffix = 0;

  while ($size > 1024) {
    $suffix++;
    $size /= 1024;
  }

  return $size . $fileSuffixes[$suffix];

  }

  $uploads = sqlArr("SELECT v.fileid, f.mime, f.size, f.name, f.rating, v.md5hash FROM {$sqlPrefix}files AS f, {$sqlPrefix}fileVersions AS v WHERE f.userid = $user[userid] AND f.id = v.fileid",'fileid');
  foreach ($uploads AS $file) {
    $file['size'] = formatSize($file['size']);

    $files .= "    <tr>
      <td><img src=\"/file.php?hash=$file[md5hash]\" style=\"max-height: 100px; max-width: 100px;\" /></td>
      <td>$file[name] ($file[rating]+)</td>
      <td>$file[size]</td>
      <td>$file[mime]</td>
      <td><img src=\"images/edit-delete.png\" /> | <a href=\"#\" onclick=\"$('#rateDialogue').dialog();\">Rate</a></td>
    </tr>";
  }

  echo container('View My Uploads','<table class="page">
  <thead>
    <tr class="hrow">
      <td>Preview</td>
      <td>Name</td>
      <td>Size</td>
      <td>Mime Type</td>
      <td>Actions</td>
    </tr>
  </thead>

  <tbody>
' . $files . '
  </tbody>
</table>');

  echo '<script type="text/javascript">
$(document).ready(function(){
  $("#rateDialogue").submit(function(){
    data = $(this).serialize(); // Serialize the form data for AJAX.
    $.post("ajax/fim-modAction.php?phase=2",data,function(html) {
      quickDialogue(html,\'\',\'rateResultsDialogue\');
    }); // Send the form data via AJAX.

    $("#rateDialogue").dialog(\'close\');

    return false; // Don\'t submit the form.
  });
});
</script>

  <form id="rateDialogue" action="#" method="post" style="display: none;">
    <label for="rating">Rating:</label>

    <select name="rating" id="rating">
      <option value="6">6+ (E/G)</option>
      <option value="10">10+ (E10+/PG)</option>
      <option value="13">13+ (T/PG-13)</option>
      <option value="16">16+ (M/R)</option>
      <option value="18">18+ (AO/NC-17)</option>
    </select>
  </form>';

  echo container('Upload New File','<form action="/uploadFile.php" method="post" enctype="multipart/form-data" target="upload_target" id="uploadFileForm">
  <fieldset>
    <legend>Upload from Computer</legend>
    <label for="fileUpload">File: </label>
    <input name="fileUpload" id="fileUpload" type="file" onChange="upFiles()" /><br /><br />
  </fieldset>
  <fieldset>
    <legend>Preview & Submit</legend>
    <div id="preview"></div><br /><br />

    <button onclick="$(\'#textentryBoxUpload\').dialog(\'close\');" type="button">Cancel</button>
    <button type="submit" id="imageUploadSubmitButton">Upload</button>
  </fieldset>
  <iframe id="upload_target" name="upload_target" class="nodisplay"></iframe>
  <input type="hidden" name="method" value="image" />
  <input type="hidden" name="generalUpload" value="true" />
</form>');

  require_once('templateEnd.php');
}

elseif ($_POST['method']) { // Actual upload; process.
  if (!$room && !$enableGeneralUploads) {
    die();
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
      $flag = 'image';

      if ($_POST['generalUpload']) {
        $generalUpload = true;
      }
      else {
        $generalUpload = false;
      }

      $validTypes = array('image/gif','image/jpeg','image/png','image/pjpeg','application/octet-stream');
      $validExts = array('gif','jpg','jpeg','png');
      $extParts = explode('.',$_FILES['fileUpload']['name']);
      $ext = $extParts[count($extParts) - 1];

      $fileLocation = "{$installLoc}userdata/uploads/{$user[userid]}/" . preg_replace('/[^a-zA-Z0-9_\.]/','',$_FILES['fileUpload']['name']);
      $webLocation = "{$installUrl}userdata/uploads/{$user[userid]}/" . preg_replace('/[^a-zA-Z0-9_\.]/','',$_FILES['fileUpload']['name']);

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
        if ($uploadMethod == 'server') {
          if (file_exists($fileLocation)) {
            if ($parseFlags) {
              $message = $webLocation;
            }
            else {
              $message = "[img]{$webLocation}[/img]";
            }
          }
          else {
            if (!is_dir("{$installLoc}userdata/uploads/$user[userid]")) mkdir ("{$installLoc}userdata/uploads/$user[userid]",0775);

            if (!move_uploaded_file($_FILES['fileUpload']['tmp_name'],$fileLocation)) {
              $errorMessage = 'Could not upload file. (' . $fileLocation . ')';
            }
            else {
              if ($parseFlags) {
                $message = $webLocation;
              }
              else {
                $message = "[img]{$webLocation}[/img]";
              }
            }
          }
        }
        elseif ($uploadMethod == 'database') {
          $contents = file_get_contents($_FILES['fileUpload']['tmp_name']);
          $md5hash = md5($contents);

          $name = mysqlEscape($_FILES['fileUpload']['name']);
          $size = intval(strlen($contents));
          $mime = mysqlEscape($_FILES['fileUpload']['type']);

          if ($encryptUploads) {
            list($contentsEncrypted,$iv,$saltNum) = vrim_encrypt($contents);
            $contentsEncrypted = mysqlEscape($contentsEncrypted);
            $iv = mysqlEscape($iv);
            $saltNum = intval($saltNum);
          }
          else {
            $contentsEncrypted = mysqlEscape(base64_encode($contents));
            $iv = '';
            $saltNum = '';
          }

          if (!$contents) {
            $errorMessage .= 'Could not obtain file contents.';
          }
          else {
            $prefile = sqlArr("SELECT v.id, f.fileid FROM {$sqlPrefix}fileVersions AS v, {$sqlPrefix}files AS f WHERE v.md5hash = '$md5hash' AND v.fileid = f.id AND f.userid = $user[userid]");

            if ($prefile) {
              $webLocation = "{$installUrl}file.php?hash={$prefile[md5hash]}";

              if ($parseFlags) {
                $message = $webLocation;
              }
              else {
                $message = "[img]{$webLocation}[/img]";
              }
            }
            else {
              mysqlQuery("INSERT INTO {$sqlPrefix}files (userid, name, size, mime) VALUES ($user[userid], '$name', '$size', '$mime')");
              $fileid = mysql_insert_id();

              mysqlQuery("INSERT INTO {$sqlPrefix}fileVersions (fileid, md5hash, salt, iv, contents) VALUES ($fileid, '$md5hash', '$saltNum', '$iv', '$contentsEncrypted')");

              $webLocation = "{$installUrl}file.php?hash={$md5hash}";

              if ($parseFlags) {
                $message = $webLocation;
              }
              else {
                $message = "[img]{$webLocation}[/img]";
              }
            }
          }
        }
        else {
          $errorMessage = 'Unknown upload method.';
        }
      }
    }
    break;
  }

  if (!$errorMessage) {
    if ($generalUpload) {
      echo '<script type="text/javascript">window.top.window.location.reload();</script>';
    }
    else {
      require_once('functions/parserFunctions.php');
      sendMessage($message,$user,$room,$flag);
    }
  }
  else {
    echo '<script type="text/javascript">window.top.window.alert(\'' . $errorMessage . '\');</script>';
  }
}