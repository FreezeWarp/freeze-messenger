<?php
if ($user['settings'] & 16) { // Check that the user is an admin.
  echo '<div style="width: 20%; float: left;">
<table class="page">
  <thead>
    <tr class="hrow">
      <td>Actions</td>
    </tr>
  </thead>
  <tbody>
    <tr>
      <td style="border-bottom: 1px solid;"><a href="/index.php?action=moderate">Home</a></td>
    </tr>
    <tr>
      <td><a href="/index.php?action=moderate&do=showimages">Moderate Images</a></td>
    </tr>
    <tr>
      <td><a href="/index.php?action=moderate&do=listusers">Moderate Users</a></td>
    </tr>
    <tr>
      <td><span style="padding-left: 15px;"><a href="/index.php?action=moderate&do=banuser">Ban a User</a></span></td>
    </tr>
    <tr>
      <td><span style="padding-left: 15px;"><a href="/index.php?action=moderate&do=unbanuser">Unban a User</a></span></td>
    </tr>
    <tr>
      <td><a href="/index.php?action=moderate&do=maintence">Maintence</a></td>
    </tr>
  </tbody>
</table>
</div>
<div style="width: 80%; float: right;">';
  switch ($_GET['do']) {
    case 'showimages':
    $userid = intval($_GET['userid']);
    if ($userid) {
      $images = array_filter(scandir('./userdata/uploads/' . $userid),function($var) { if (!in_array($var,array('.','..'))) return $var; }); // This rather long function does the following (in order): scans the userdata/uploads directory, filters out "." and "..", and creates a CSV list of the results.

      foreach ($images as $image) {
        $tableCode .= "<tr><td><a href=\"/userdata/uploads/$userid/$image\"><img src=\"/userdata/uploads/$userid/$image\" style=\"max-width: 200px; max-height: 200px;\" /></a></td><td>$image</td><td><a href=\"/index.php?action=moderate&do=deleteimage&img=$userid/$image\"><img src=\"images/edit-delete.png\" /></a></td></tr>";
      }

      echo container('Moderate and Delete Images','<table class="page rowHover">
  <thead>
    <tr class="hrow">
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
      $users = implode(',',array_filter(scandir('./userdata/uploads/'),function($var) { if (!in_array($var,array('.','..'))) return $var; })); // This rather long function does the following (in order): scans the userdata/uploads directory, filters out "." and "..", and creates a CSV list of the results.
      $users = mysqlReadThrough(mysqlQuery("SELECT userid, username FROM user WHERE userid IN ($users)"),'<tr><td>$userid</td><td><a href="/index.php?action=moderate&do=showimages&userid=$userid">$username</a></td></tr>'); // This process a basic MySQL query, and returns the results as a set of table rows.

      echo container('Select a User','<table class="page rowHover">
  <thead>
    <tr class="hrow">
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
      $contents = mysqlEscape(base64_encode(file_get_contents("./userdata/uploads/$img")));

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
    $userTable = mysqlReadThrough(mysqlQuery("SELECT u.userid, u.username, u2.settings FROM user AS u, {$sqlPrefix}users AS u2 WHERE u2.userid = u.userid AND (u2.settings & 1 = false)"),'<tr><td>$userid</td><td>$username</td><td><a href="/index.php?action=moderate&do=banuser2&userid=$userid">Ban</a></td></tr>');

    echo '<table class="page rowHover">
  <thead>
    <tr class="hrow">
      <td>User ID</td>
      <td>Username</td>
      <td>Actions</td>
    </tr>
  </thead>
  <tbody>' . $userTable . '
  </tbody>
</table>';
    break;

    case 'banuser2':
    $userid = intval($_GET['userid']);
    mysqlQuery("UPDATE {$sqlPrefix}users SET settings = IF(settings & 1 = false,settings + 1,settings) WHERE userid = $userid");

    echo container('User Banned','The user has been banned.');
    break;

    case 'unbanuser':
    $userTable = mysqlReadThrough(mysqlQuery("SELECT u.userid, u.username, u2.settings FROM user AS u, {$sqlPrefix}users AS u2 WHERE u2.userid = u.userid AND u2.settings & 1"),'<tr><td>$userid</td><td>$username</td><td><a href="/index.php?action=moderate&do=unbanuser2&userid=$userid">Unban</a></td></tr>');

    echo '<table class="page rowHover">
  <thead>
    <tr class="hrow">
      <td>User ID</td>
      <td>Username</td>
      <td>Actions</td>
    </tr>
  </thead>
  <tbody>' . $userTable . '
  </tbody>
</table>';
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
        echo container('Stop/Start VRIM','VRIM is currently stopped. Would you like to start it?:<br /><br />' . button('Enable','/index.php?action=moderate&do=maintence&do2=disable2-enable'));
      }

      else {
        echo container('Stop/Start VRIM','VRIM is currently running. Would you like to stop it?:<br /><br />' . button('Disable','/index.php?action=moderate&do=maintence&do2=disable2-disable'));
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
    <tr class="hrow">
      <td>System Maintence</td>
    </tr>
  </thead>
  <tbody>
    <tr>
      <td>
        <ul>
          <li><a href="/index.php?action=moderate&do=maintence&do2=postcache">Regenerate Post Cache</a></li>
          <li><a href="/index.php?action=moderate&do=maintence&do2=privateroomcache">Regenerate Private Room Cache</a></li>
          <li><a href="/index.php?action=moderate&do=maintence&do2=disable">Disable/Enable VRIM</a></li>
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
    echo container('Welcome','<script type="text/javascript">$(document).ready(function() { $(\'#moderateRight\').animate({\'height\' : \'300px\'},1750); });</script><div style="height: 0px; overflow: hidden;" id="moderateRight"><div style="text-align: center; font-size: 40px; font-weight: bold;">Welcome~~</div><br /><br />

Welcome to the Victory Road Instant Messenger control panel. Here you, as one of our grandé and spectacular administrative staff, can perform every task needed to you during any given day.<br /><br />

Server Time: ' . date('h:ia') . '<br />
Your Time: ' . vbdate('h:ia') . '<br />
Active Users in Last Minute: ' . count($activeUsers) . '<br />
Banned Users: <a href="/index.php?action=moderate&do=unbanuser">' . count($bannedUsers) . '</a><br />
Status: <a href="/index.php?action=moderate&do=maintence&do2=disable">' . $status . '</a><br /><br /><br />


Freezie is Bored.</div>');
    break;
  }
  echo '</div>';
}
else {
  trigger_error('You do not have permission to access this page.',E_USER_ERROR);
}
?>