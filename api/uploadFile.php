<?php
  eval(hook('uploadProcessStart'));

  if (!$room && !$enableGeneralUploads) {
    die();
  }

  switch ($_POST['method']) {
    case 'url':
    eval(hook('uploadProcessUrlStart'));

    if ($_POST['linkEmail']) {
      $flag = 'email';

      eval(hook('uploadProcessUrlEmailStart'));

      if ($parseFlags) {
        $message = $_POST['linkEmail'];
      }
      else {
        $linkUrl = $_POST['linkEmail'];
        $linkText = $_POST['linkEmail'];

        $message = "[email]{$linkText}[/email]";
      }

      eval(hook('uploadProcessUrlEmailEnd'));
    }
    elseif ($_POST['linkUrl']) {
      $flag = 'link';

      eval(hook('uploadProcessUrlLinkStart'));

      if ($parseFlags) {
        $message = $_POST['linkUrl'];
      }
      else {
        $linkUrl = $_POST['linkUrl'];

        if ($_POST['linkText']) $linkText = $_POST['linkText'];
        else $linkText = $_POST['linkUrl'];

        $message = "[url={$linkUrl}]{$linkText}[/url]";
      }

      eval(hook('uploadProcessUrlLinkEnd'));
    }
    else {
      $errorMessage = $phrases['uploadErrorNoUrl'];
    }

    eval(hook('uploadProcessUrlEnd'));
    break;

    case 'youtube':
    eval(hook('uploadProcessYoutubeStart'));

    if ($_POST['urlLink']) {
      $message = '[url]' . $_POST['urlLink'] . '[/url]';
    }
    elseif ($_POST['youtubeUpload'] && $_POST['youtubeUpload'] != 'http://') {
      if (preg_match('/^(http:\/\/|)(www\.|)youtube.com\/(.*)?v=([a-zA-Z0-9\-\_]+)(&|)(.*)$/i',$_POST['youtubeUpload'])) { // A youtube video
        $flag = 'video';

        if ($parseFlags) {
//          $message = preg_replace('/^(.+)?v=([a-zA-Z0-9\-\_]+?)(&|)(.*)$/i','http://www.youtube.com/?v=$2',$_POST['youtubeUpload']);
            $message = $_POST['youtubeUpload'];
        }
        else {
          $vPart = preg_replace('/^(.+)?v=([a-zA-Z0-9\-\_]+?)(&|)(.*)$/i','$2',$_POST['youtubeUpload']);
          $message = '[youtube]' . $vPart . '[/youtube]';
        }
      }
      else {
        $errorMessage = $phrases['uploadErrorNoYoutube'];
      }
    }

    eval(hook('uploadProcessYoutubeEnd'));
    break;

    case 'image':
    eval(hook('uploadProcessImageStart'));

    if ($_POST['urlUpload'] && $_POST['urlUpload'] != 'http://') {
      eval(hook('uploadProcessImageUrlStart'));

      $validTypes = array('image/gif','image/jpeg','image/png','image/pjpeg');
      $urlUpload = $_POST['urlUpload'];
      if (function_exists('curl_init')) {
        $ch = curl_init($urlUpload);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_exec($ch);
        $mime = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
        $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        if ($status != 200) {
          $errorMessage = $phrases['uploadErrorNoExist'];
        }
        elseif (!in_array($mime,$validTypes)) {
          $errorMessage = $phrases['uploadErrorBadType'];
        }
        else {
          if ($parseFlags) {
            $message = $urlUpload;
          }
          else {
            $message = '[img]' . $urlUpload . '[/img]';
          }
        }

        eval(hook('uploadProcessImageUrlEnd'));
      }
      else {
        $errorMessage = 'Server Not Spported';
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

      $fileLocation = "{$installLoc}userdata/uploads/{$user[userId]}/" . preg_replace('/[^a-zA-Z0-9_\.]/','',$_FILES['fileUpload']['name']);
      $webLocation = "{$installUrl}userdata/uploads/{$user[userId]}/" . preg_replace('/[^a-zA-Z0-9_\.]/','',$_FILES['fileUpload']['name']);

      eval(hook('uploadProcessImageStoreStart'));


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
        if ($uploadMethod == 'database') {
          eval(hook('uploadProcessImageStoreDatabaseStart'));

          $contents = file_get_contents($_FILES['fileUpload']['tmp_name']);
          $md5hash = md5($contents);

          $name = mysqlEscape($_FILES['fileUpload']['name']);
          $size = intval(strlen($contents));
          $mime = mysqlEscape($_FILES['fileUpload']['type']);

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

          eval(hook('uploadProcessImageStoreDatabaseEnd'));
        }
        else {
          $errorMessage = $phrases['uploadErrorMethod'];
        }
      }
    }

    eval(hook('uploadProcessImageEnd'));
    break;
  }

  if (!$errorMessage) {
    if ($generalUpload) {
      echo '<script type="text/javascript">window.top.window.location.reload();</script>';
    }
    else {
      require_once('functions/parserFunctions.php');
      fim_sendMessage($message,$user,$room,$flag);
    }
  }
  else {
    echo '<script type="text/javascript">window.top.window.alert(\'' . $errorMessage . '\');</script>';
  }