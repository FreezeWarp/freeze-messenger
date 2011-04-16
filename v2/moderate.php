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

require_once('global.php');
require_once('functions/container.php');
require_once('templateStart.php');

if ($user['settings'] & 16) { // Check that the user is an admin.
  switch ($_GET['do']) {
    case 'censor':
    switch($_GET['do2']) {
      case false:
      case 'viewLists':
      $lists = sqlArr("SELECT * FROM {$sqlPrefix}censorLists WHERE options & 1",'id');

      foreach ($lists AS $list) {
        $options = array();

        if ($list['options'] & 2) $options[] = "Dissabable";
        if ($list['options'] & 4) $options[] = "Disabled in Private";
        if ($list['options'] & 8) $options[] = "Mature";

        $rows .= '    <tr><td>' . $list['name'] . '</td><td align="center">' . ($list['type'] == 'white' ? '<div style="border-radius: 1em; background-color: white; border: 1px solid black; width: 20px; height: 20px;"></div>' : '<div style="border-radius: 1em; background-color: black; border: 1px solid white; width: 20px; height: 20px;"></div>') . '</td><td>' . implode(', ',$options) . '</td><td><a href="/moderate.php?do=censor&do2=deleteList&listid=' . $list['id'] . '"><img src="images/edit-delete.png" /></a><a href="/moderate.php?do=censor&do2=editList&listid=' . $list['id'] . '"><img src="images/document-edit.png" /></a><a href="/moderate.php?do=censor&do2=viewWords&listid=' . $list['id'] . '"><img src="images/view-list-text.png" alt="View Words" /></a></td></tr>
  ';
      }

      echo container('Current Lists<a href="/moderate.php?do=censor&do2=addList"><img src="images/document-new.png" style="float: right;" /></a>','<table class="page rowHover" border="1">
  <thead>
    <tr class="hrow ui-widget-header">
      <td>List Name</td>
      <td>Type</td>
      <td>Notes</td>
      <td>Actions</td>
    </tr>
  </thead>
  <tbody>
' . $rows . '
  </tbody>
</table>');
      break;

      case 'addList':
      echo container('Create New Censor List','<form action="/moderate.php?do=censor&do2=addList2" method="post">
  <table>
    <tr>
      <td>Name:</td>
      <td><input type="text" name="name" /><td>
    </tr>
    <tr>
      <td>Type:</td>
      <td>
        <select name="type">
          <option value="white">whitelist</option>
          <option value="black">blacklist</option>
        </select>
      </td>
    </tr>
    <tr>
      <td>Can be Dissabled:</td>
      <td><input type="checkbox" name="candis" value="true" /></td>
    </tr>
    <tr>
      <td>Dissabled in Private Rooms:</td>
      <td><input type="checkbox" name="privdis" value="true" /></td>
    </tr>
    <tr>
      <td>Mature:</td>
      <td><input type="checkbox" name="mature" value="true" /><td>
    </tr>
  </table>

  <button type="submit">Submit</button><button type="reset">Reset</button>
</form>');
      break;

      case 'addList2':
      $options = array('white','black');

      $listname = mysqlEscape($_POST['name']);
      $listtype = (in_array($_POST['name'],$options) ? $_POST['name'] : 'white');
      $listoptions = 1 + ($_POST['candis'] ? 2 : 0) + ($_POST['privdis'] ? 4 : 0) + ($_POST['mature'] ? 8 : 0);

      mysqlQuery("INSERT INTO {$sqlPrefix}censorLists (name, type, options) VALUES ('$listname', '$listtype', '$listoptions')");

      echo container('List Added','The list has been added.<br /><br />' . button('Return to Viewing Lists','/moderate.php?do=censor&do2=viewLists'));
      break;

      case 'editList':
      $listid = intval($_GET['listid']);
      $list = sqlArr("SELECT * FROM {$sqlPrefix}censorLists WHERE id = $listid");

      echo container('Edit Censor List','<form action="/moderate.php?do=censor&do2=addList2" method="post">
  <table>
    <tr>
      <td>Name:</td>
      <td><input type="text" name="name" /><td>
    </tr>
    <tr>
      <td>Type:</td>
      <td>
        <select name="type">
          <option value="white" ' . ($list['type'] == 'white' ? ' selected="selected"' : '') . '>whitelist</option>
          <option value="black" ' . ($list['type'] == 'black' ? ' selected="selected"' : '') . '>blacklist</option>
        </select>
      </td>
    </tr>
    <tr>
      <td>Can be Dissabled:</td>
      <td><input type="checkbox" name="candis" value="true" ' . ($list['options'] & 2 ? ' checked="checked"' : '') . ' /></td>
    </tr>
    <tr>
      <td>Dissabled in Private Rooms:</td>
      <td><input type="checkbox" name="privdis" value="true" ' . ($list['options'] & 4 ? ' checked="checked"' : '') . ' /></td>
    </tr>
    <tr>
      <td>Mature:</td>
      <td><input type="checkbox" name="mature" value="true" ' . ($list['options'] & 8 ? ' checked="checked"' : '') . ' /><td>
    </tr>
  </table>

  <button type="submit">Submit</button><button type="reset">Reset</button>
</form>');
      break;

      case 'editList2':
      $options = array('white','black');

      $listname = mysqlEscape($_POST['name']);
      $listtype = (in_array($_POST['name'],$options) ? $_POST['name'] : 'white');
      $listoptions = 1 + ($_POST['candis'] ? 2 : 0) + ($_POST['privdis'] ? 4 : 0) + ($_POST['mature'] ? 8 : 0);

      mysqlQuery("INSERT INTO {$sqlPrefix}censorLists (name, type, options) VALUES ('$listname', '$listtype', '$listoptions')");

      echo container('List Added','The list has been added.<br /><br />' . button('Return to Viewing Lists','/moderate.php?do=censor&do2=viewLists'));
      break;

      case 'deleteList':

      break;

      case 'viewWords':
      $listid = intval($_GET['listid']);
      $words = sqlArr("SELECT * FROM {$sqlPrefix}censorWords WHERE listid = $listid",'id');
      if ($words) {
        foreach ($words AS $word) {
          $rows .= '    <tr><td>' . $word['word'] . '</td><td>' . $word['severity'] . '</td><td>' . $word['param'] . '</td><td><a href="/moderate.php?do=censor&do2=deleteWord&wordid=' . $word['id'] . '"><img src="images/edit-delete.png" /></a><a href="/moderate.php?do=censor&do2=editWord&wordid=' . $word['id'] . '"><img src="images/document-edit.png" /></a></td></tr>
  ';
        }
      }
      else {
        $rows = '<tr><td colspan="4">No words have been added.</td></tr>';
      }

      echo container('Current Words<a href="/moderate.php?do=censor&do2=addWord&listid=' . $listid . '"><img src="images/document-new.png" style="float: right;" /></a>','<table class="page rowHover" border="1">
  <thead>
    <tr class="hrow ui-widget-header">
      <td>Word</td>
      <td>Type</td>
      <td>Param</td>
      <td>Actions</td>
    </tr>
  </thead>
  <tbody>
' . $rows . '
  </tbody>
</table>');
      break;

      case 'addWord':
      $listid = intval($_GET['listid']);

      echo container('Add New Word','<form action="/moderate.php?do=censor&do2=addWord2" method="post">
<table>
  <tr>
    <td>Text</td>
    <td><input type="text" name="text" /></td>
  </tr>
    <td>Severity:
    <td>
      <select name="severity">
        <option value="replace">replace</option>
        <option value="warn">warn</option>
        <option value="confirm">confirm</option>
        <option value="block">block</option>
      </select>
    </td>
  </tr>
  <tr>
    <td>Param:</td>
    <td><input type="text" name="param" /></td>
  </tr>
</table><br />

  <input type="hidden" name="listid" value="' . $listid . '" />

  <button type="submit">Submit</button><button type="reset">Reset</button>
</form>');
      break;

      case 'addWord2':
      $options = array('replace','warn','confirm','block');

      $wordtext = mysqlEscape($_POST['text']);
      $wordsev = (in_array($_POST['severity'],$options) ? $_POST['severity'] : 'replace');
      $wordparam = mysqlEscape($_POST['param']);
      $listid = intval($_POST['listid']);

      mysqlQuery("INSERT INTO {$sqlPrefix}censorWords (listid, word, severity, param) VALUES ($listid, '$wordtext', '$wordsev', '$wordparam')");

      echo container('Word Added','The word has been added.<br /><br />' . button('Return to Viewing Words','/moderate.php?do=censor&do2=viewWords&listid=' . $listid));
      break;

      case 'editWord':
      $wordid = intval($_GET['wordid']);
      $word = sqlArr("SELECT * FROM {$sqlPrefix}censorWords WHERE id = $wordid");

      echo container('Edit Word "' . $word['word'] . '"','<form action="/moderate.php?do=censor&do2=editWord2" method="post">
<table>
  <tr>
    <td>Text</td>
    <td><input type="text" name="text" value="' . $word['word'] . '" /></td>
  </tr>
    <td>Severity:
    <td>
      <select name="severity">
        <option value="replace" ' . ($word['severity'] == 'replace' ? ' selected="selected"' : '') . '>replace</option>
        <option value="warn" ' . ($word['severity'] == 'warn' ? ' selected="selected"' : '') . '>warn</option>
        <option value="confirm" ' . ($word['severity'] == 'confirm' ? ' selected="selected"' : '') . '>confirm</option>
        <option value="block" ' . ($word['severity'] == 'block' ? ' selected="selected"' : '') . '>block</option>
      </select>
    </td>
  </tr>
  <tr>
    <td>Param:</td>
    <td><input type="text" name="param" value="' . $word['param'] . '"  /></td>
  </tr>
</table><br />

  <input type="hidden" name="wordid" value="' . $word['id'] . '" />

  <button type="submit">Submit</button><button type="reset">Reset</button>
</form>');
      break;

      case 'editWord2':
      $options = array('replace','warn','confirm','block');

      $wordid = intval($_POST['wordid']);
      $word = sqlArr("SELECT * FROM {$sqlPrefix}censorWords WHERE id = $wordid");

      $wordtext = mysqlEscape($_POST['text']);
      $wordsev = (in_array($_POST['severity'],$options) ? $_POST['severity'] : 'replace');
      $wordparam = mysqlEscape($_POST['param']);

      mysqlQuery("UPDATE {$sqlPrefix}censorWords SET word = '$wordtext', severity = '$wordsev', param = '$wordparam' WHERE id = $wordid");

      echo container('Word Changed','The word has been changed.<br /><br />' . button('Return to Viewing Words','/moderate.php?do=censor&do2=viewWords&listid=' . $word['listid']));
      break;

      case 'deleteWord':
      $wordid = intval($_GET['wordid']);

      mysqlQuery("DELETE FROM {$sqlPrefix}censorWords WHERE id = $wordid");

      echo container('Word Deleted','The word has been removed.<br /><br /><button onclick="window.history.back();" type="button">Go Back</button>');
      break;
    }
    break;

    case 'showimages':
    $userid = intval($_GET['userid']);
    if ($userid) { echo $installLoc . '/userdata/uploads/';
      $images = array_filter(scandir($installLoc . '/userdata/uploads/' . $userid),function($var) { if (!in_array($var,array('.','..'))) return $var; }); // This rather long function does the following (in order): scans the userdata/uploads directory, filters out "." and "..", and creates a CSV list of the results.

      foreach ($images as $image) {
        $tableCode .= "<tr><td><a href=\"/userdata/uploads/$userid/$image\"><img src=\"/userdata/uploads/$userid/$image\" style=\"max-width: 200px; max-height: 200px;\" /></a></td><td>$image</td><td><a href=\"/moderate.php?do=deleteimage&img=$userid/$image\"><img src=\"images/edit-delete.png\" /></a></td></tr>";
      }

      echo container('Moderate and Delete Images','<table class="page rowHover">
  <thead>
    <tr class="hrow ui-widget-header">
      <td>Preview</td>
      <td>Name</td>
      <td>Actions</td>
    </tr>
  </thead>
  <tbody>' . $tableCode . '
  </tbody>
</table>');
    }
    else {
      $users = implode(',',array_filter(scandir($installLoc . 'userdata/uploads/'),function($var) { if (!in_array($var,array('.','..'))) return $var; })); // This rather long function does the following (in order): scans the userdata/uploads directory, filters out "." and "..", and creates a CSV list of the results.
      $users = mysqlReadThrough(mysqlQuery("SELECT userid, username FROM user WHERE userid IN ($users)"),'<tr><td>$userid</td><td><a href="/moderate.php?do=showimages&userid=$userid">$username</a></td></tr>'); // This process a basic MySQL query, and returns the results as a set of table rows.

      echo container('Select a User','<table class="page rowHover">
  <thead>
    <tr class="hrow ui-widget-header">
      <td>User ID</td><td>Username</td>
    </tr>
  </thead>
  <tbody>
    ' . $users . '
  </tbody>
</table>');
    }
    break;

    case 'deleteimage':
    $img = $_GET['img'];
    if (file_exists("./userdata/uploads/$img")) {
      $file = mysqlEscape("userdata/uploads/$img");
      $userid = intval(preg_replace("/^userdata\/uploads\/(.+?)\/.+$/","$1",$file));
      $contents = mysqlEscape(base64_encode(file_get_contents("{$installLoc}/userdata/uploads/$img")));

      if ($user && $contents) {
        if (mysqlQuery("INSERT INTO {$sqlPrefix}trashFiles (name, type, userid, deletedBy, contents) VALUES ('$file', 'image_upload', $userid, $user[userid], '$contents')")) {
          if (unlink("./userdata/uploads/$img")) {
            echo container('Image Deleted','The image was successfully deleted. It is now located in the MySQL trash.');
          }
          else {
            echo container('Error','The image could not be deleted.<br /><br />Note: MySQL has already been committed. In version 2, this won\'t be a problem when we migrate over to MySQLi, though...');
          }
        }
        else {
          echo container('Error','The file could not be saved to the MySQL database. Operation aborted.');
        }
      }
      else {
        echo container('Error','The owner of the file could not be determined; or, the file was empty.');
      }
    }
    else {
      echo container('Error','The image does not exist');
    }
    break;

    case 'undeleteimage':
    echo container('Error','This isn\'t coded yet... Come back in Version 2!');
    break;

    case 'banuser':
    $userTable = mysqlReadThrough(mysqlQuery("SELECT u.userid, u.username, u2.settings FROM user AS u, {$sqlPrefix}users AS u2 WHERE u2.userid = u.userid AND (u2.settings & 1 = false)"),'<tr><td>$userid</td><td>$username</td><td><a href="/moderate.php?do=banuser2&userid=$userid">Ban</a></td></tr>');

    echo container('Ban a User','<table class="page rowHover">
  <thead>
    <tr class="hrow ui-widget-header">
      <td>User ID</td>
      <td>Username</td>
      <td>Actions</td>
    </tr>
  </thead>
  <tbody>' . $userTable . '
  </tbody>
</table>');
    break;

    case 'banuser2':
    $userid = intval($_GET['userid']);
    mysqlQuery("UPDATE {$sqlPrefix}users SET settings = IF(settings & 1 = false,settings + 1,settings) WHERE userid = $userid");

    echo container('User Banned','The user has been banned.');
    break;

    case 'unbanuser':
    $userTable = mysqlReadThrough(mysqlQuery("SELECT u.userid, u.username, u2.settings FROM user AS u, {$sqlPrefix}users AS u2 WHERE u2.userid = u.userid AND u2.settings & 1"),'<tr><td>$userid</td><td>$username</td><td><a href="/moderate.php?do=unbanuser2&userid=$userid">Unban</a></td></tr>');

    echo container('Unban a User','<table class="page rowHover">
  <thead>
    <tr class="hrow ui-widget-header">
      <td>User ID</td>
      <td>Username</td>
      <td>Actions</td>
    </tr>
  </thead>
  <tbody>' . $userTable . '
  </tbody>
</table>');
    break;

    case 'unbanuser2':
    $userid = intval($_GET['userid']);
    mysqlQuery("UPDATE {$sqlPrefix}users SET settings = IF(settings & 1,settings - 1,settings) WHERE userid = $userid");

    echo container('User Unbanned','The user has been unbanned.');
    break;

    case 'maintence':
    switch ($_GET['do2']) {
      case 'disable':
      if (file_exists('.tempStop')) {
        echo container('Stop/Start VRIM','VRIM is currently stopped. Would you like to start it?:<br /><br />' . button('Enable','/moderate.php?do=maintence&do2=disable2-enable'));
      }

      else {
        echo container('Stop/Start VRIM','VRIM is currently running. Would you like to stop it?:<br /><br />' . button('Disable','/moderate.php?do=maintence&do2=disable2-disable'));
      }
      break;

      case 'disable2-disable':
      if (file_exists('.tempStop')) {
        echo container('Error','VRIM has already been stopped.');
      }
      else {
        touch('.tempStop');
        echo container('','VRIM has been stopped.');
      }
      break;

      case 'disable2-enable':
      if (file_exists('.tempStop')) {
        unlink('.tempStop');
        echo container('','VRIM has been re-enabled.');
      }
      else {
        echo container('Error','VRIM is already running.');
      }
      break;

      case 'postcache':
      echo container('Error','Not yet coded');
      break;

      case 'privateroomcache':
      $rooms = mysqlQuery("SELECT * FROM {$sqlPrefix}rooms WHERE options & 16");
      while (false !== ($room = (mysqlArray($rooms)))) {
        list($user1id,$user2id) = explode(',',$room['allowedUsers']);

        if (!$user1id || !$user2id) {
          $results .= "Failed to Process Room ID $room[id]; Bad allowedUsers definition '$room[allowedUsers]'.<br />";
        }
        else {
          $user1 = sqlArr("SELECT * FROM user WHERE userid = $user1id");
          $user2 = sqlArr("SELECT * FROM user WHERE userid = $user2id");

          if (!$user1['username']) {
            $results .= "Failed to Process Room ID $room[id]; User ID $user1id no longer exists.<br />";
          }
          elseif (!$user2['username']) {
            $results .= "Failed to Process Room ID $room[id]; User ID $user2id no longer exists.<br />";
          }
          else {
            $name = mysqlEscape("Private IM ($user1[username] and $user2[username])");
            if (mysqlQuery("UPDATE {$sqlPrefix}rooms SET name = '$name', bbcode = 1, options = 48 WHERE id = $room[id]")) {
              $results .= "Processed Room ID $room[id]; Users $user1[username], $user2[username]<br />";
            }
            else {
              $results .= "Failed to Process Room ID $room[id]; MySQL Query Failed";
            }
          }
        }
      }

      echo container('Updating Private Room Cache',"$results");
      break;

      default:
      echo '<table class="page">
  <thead>
    <tr class="hrow ui-widget-header">
      <td>System Maintence</td>
    </tr>
  </thead>
  <tbody>
    <tr>
      <td>
        <ul>
          <li><a href="/moderate.php?do=maintence&do2=postcache">Regenerate Post Cache</a></li>
          <li><a href="/moderate.php?do=maintence&do2=privateroomcache">Regenerate Private Room Cache</a></li>
          <li><a href="/moderate.php?do=maintence&do2=disable">Disable/Enable VRIM</a></li>
        </ul>
      </td>
    </tr>
  </tbody>
</table>';
      break;
    }
    break;


    default:
    $activeUsers = sqlArr("SELECT COUNT(userid) AS count, userid FROM {$sqlPrefix}ping WHERE UNIX_TIMESTAMP(time) > UNIX_TIMESTAMP(NOW()) - 60 GROUP BY userid",'userid');
    $bannedUsers = sqlArr("SELECT userid FROM {$sqlPrefix}users WHERE settings & 1",'userid');
    $status = (file_exists('.tempStop')) ? 'Stopped' : 'Running';
    echo container('Welcome','<script type="text/javascript">$(document).ready(function() { $(\'#moderateRight\').animate({\'height\' : window.innerHeight - 50},1750); });</script><div style="height: 0px; overflow: hidden;" id="moderateRight"><div style="text-align: center; font-size: 40px; font-weight: bold;">Welcome~~</div><br /><br />

Welcome to the FreezeMessenger control panel. Here you, as one of our grandé and spectacular administrative staff, can perform every task needed to you during any given day.<br /><br />

Server Time: ' . date('h:ia') . '<br />
Your Time: ' . vbdate('h:ia') . '<br />
Active Users in Last Minute: ' . count($activeUsers) . '<br />
Banned Users: <a href="/moderate.php?do=unbanuser">' . count($bannedUsers) . '</a><br />
Status: <a href="/moderate.php?do=maintence&do2=disable">' . $status . '</a><br /><br /><br />


<img src="http://www.victoryroad.net/image.php?u=179&type=thumb" style="float: left;" />Freezie is Energetic.</div>');
    break;
  }
  echo '</div>';
}
else {
  trigger_error('You do not have permission to access this page.',E_USER_ERROR);
}

require_once('templateEnd.php');
?>