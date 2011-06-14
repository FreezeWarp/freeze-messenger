<?php
$validTypes = ($uploadMimes ? $uploadMimes :
  array('image/gif','image/jpeg','image/png','image/pjpeg','application/octet-stream'));

$validExts = ($uploadExtensions ? $uploadExtensions :
  array('gif','jpg','jpeg','png'));

$uploadMethod = $_POST['uploadMethod'];

switch ($uploadMethod) {
  case 'file':
  if (!in_array($_FILES['fileUpload']['type'],$validTypes)) {
    $errorMessage = $phrases['uploadErrorBadType'];
  }
  elseif (!in_array($ext,$validExts) && $_FILES['fileUpload']['type'] == 'application/octet-stream') {
    $errorMessage = $phrases['uploadErrorBadType'];
  }
  elseif ($_FILES['fileUpload']['size'] > 4 * 1000 * 1000) {
    $errorMessage = $phrases['uploadErrorSize'];
  }
  elseif ($_FILES['fileUpload']['error'] > 0) {
    $errorMessage = $phrases['uploadErrorOther'] . $_FILES['fileUpload']['error'];
  }
  else {
    $contents = file_get_contents($_FILES['fileUpload']['tmp_name']);
    $md5hash = md5($contents);

    $name = mysqlEscape($_FILES['fileUpload']['name']);
    $size = intval(strlen($contents));
    $mime = mysqlEscape($_FILES['fileUpload']['type']);

    $extParts = explode('.',$_FILES['fileUpload']['name']);
    $ext = $extParts[count($extParts) - 1];
  }
  break;

  case 'raw':
  $name = $_POST['file_name'];
  $data = $_POST['file_data'];
  $size = (int) $_POST['file_size'];
  $md5hash = $_POST['file_md5hash'];

  $dataEncode = $_POST['dataEncode'];

  switch($dataEncode) {
    case 'base64':
    $rawData = base64decode($data);
    break;

    default:
    $continue = false;
    break;
  }

  if ($md5hash) {
    if (md5($rawData) != $md5hash) {
      $continue = false;
    }
  }

  if ($size) {
    if (strlen(md5($rawData)) != $size) {
      $continue = false;
    }
  }

  break;
}

if ($continue) {

  if ($encryptUploads) {
    list($contentsEncrypted,$iv,$saltNum) = fim_encrypt($contents);
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
    $errorMessage .= $phrases['uploadErrorFileContents'];
  }
  else {
    $prefile = sqlArr("SELECT v.id, v.fileId FROM {$sqlPrefix}fileVersions AS v, {$sqlPrefix}files AS f WHERE v.md5hash = '$md5hash' AND v.fileId = f.id AND f.userId = $user[userId]");

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
      mysqlQuery("INSERT INTO {$sqlPrefix}files (userId, name, size, mime) VALUES ($user[userId], '$name', '$size', '$mime')");
      $fileId = mysql_insert_id();

      mysqlQuery("INSERT INTO {$sqlPrefix}fileVersions (fileId, md5hash, salt, iv, contents) VALUES ($fileId, '$md5hash', '$saltNum', '$iv', '$contentsEncrypted')");

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
?>