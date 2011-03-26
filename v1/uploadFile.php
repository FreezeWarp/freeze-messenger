<?php
ini_set('max_execution_time','10');
error_reporting(E_ALL);

require_once('global.php');

$room = intval($_GET['room']);
$room = sqlArr("SELECT * FROM {$sqlPrefix}rooms WHERE id = $room");

switch ($_POST['method']) {
  case 'url':
  if ($_POST['linkEmail']) {
    $linkUrl = $_POST['linkEmail'];
    $linkText = $_POST['linkEmail'];

    $message = "[email]{$linkText}[/email]";
  }
  elseif ($_POST['linkUrl']) {
    $linkUrl = $_POST['linkUrl'];

    if ($_POST['linkText']) $linkText = $_POST['linkText'];
    else $linkText = $_POST['linkUrl'];

    $message = "[url=$linkUrl]{$linkText}[/url]";
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
      $vPart = preg_replace('/^(.+)?v=([a-zA-Z0-9\-\_]+)(&|)(.*)$/i','$2',$_POST['youtubeUpload']);
      $message = '[youtube]' . $vPart . '[/youtube]';
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

    if ($status != 200) $errorMessage = 'That image does not exist or refuses to load.';
    elseif (!in_array($mime,$validTypes)) $errorMessage = 'That image is not of a valid type.';
    else $message = '[img]' . $urlUpload . '[/img]';
  }
  else {
    $validTypes = array('image/gif','image/jpeg','image/png','image/pjpeg','application/octet-stream');
    $validExts = array('gif','jpg','jpeg','png');
    $extParts = explode('.',$_FILES['fileUpload']['name']);
    $ext = $extParts[count($extParts) - 1];

    $fileLocation = "/var/www/chatinterface/htdocs/userdata/uploads/$user[userid]/" . preg_replace('/[^a-zA-Z0-9_\.]/','',$_FILES['fileUpload']['name']);
    $serverLocation = "userdata/uploads/$user[userid]/" . preg_replace('/[^a-zA-Z0-9_\.]/','',$_FILES['fileUpload']['name']);

    if (!hasPermission($room,$user)) $errorMessage = 'You do not have permission to do this.';
    elseif (!in_array($_FILES['fileUpload']['type'],$validTypes)) $errorMessage = 'You must upload a PNG, GIF, or JPEG file.';
    elseif (!in_array($ext,$validExts) && $_FILES['fileUpload']['type'] == 'application/octet-stream') $errorMessage = 'You must upload a PNG, GIF, or JPEG file.';
    elseif ($_FILES['fileUpload']['size'] > 4 * 1000 * 1000) $errorMessage = 'The file you are trying to upload is too large.';
    elseif ($_FILES['fileUpload']['error'] > 0) $errorMessage = 'Other Error: ' . $_FILES['fileUpload']['error'];
    else {
      if (file_exists($fileLocation)) $message = '[img]http://vrim.victoryroad.net/' . $serverLocation . '[/img]';
      else {
        if (!is_dir("userdata/uploads/$user[userid]")) mkdir ("userdata/uploads/$user[userid]",0755);

        if (!move_uploaded_file($_FILES['fileUpload']['tmp_name'],$fileLocation)) {
          $errorMessage = 'Could not upload file. (' . $serverLocation . ')';
        }
        else $message = '[img]http://vrim.victoryroad.net/' . $serverLocation . '[/img]';
      }
    }
  }
  break;
}
?>

<script type="text/javascript"><?php if (!$errorMessage) { echo 'window.top.window.stopUpload(1,\'' . $message . '\');'; } else { echo 'window.top.window.alert(\'' . $errorMessage . '\');'; } ?></script>