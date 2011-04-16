<!DOCTYPE HTML>
<!-- Original Source Code Copyright © 2011 Joseph T. Parsons. -->
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
  <title><?php echo ($branding['title'] ?: 'FreezeMessenger') . ($title ? " - $title" : ''); ?></title>
  <meta name="robots" content="index, follow" />
  <meta name="author" content="Joseph T. Parsons" />
  <meta name="keywords" content="instant messenger, im, instant message<?php echo ($branding['keywords'] ? ", $branding[keywords]" : '') . ($keywords ? ', ' . implode(', ',$keywords) : ''); ?>" />
  <meta name="description" content="<?php echo $branding['description']; ?>" />
  <link rel="icon" id="favicon" type="image/gif" href="<?php echo ($branding['favicon'] ? $branding['favicon'] : '/images/favicon.gif'); ?>" />
  <!--[if lte IE 9]>
  <link rel="shortcut icon" id="faviconfallback" href="<?php echo ($branding['faviconIE'] ? $branding['faviconIE'] : ($branding['favicon'] ? $branding['favicon'] : '/images/favicon1632.ico')); ?>" />
  <![endif]-->

  <!-- START Styles -->
  <link rel="stylesheet" type="text/css" href="client/css/ui-darkness/jquery-ui-1.8.11.custom.css" media="screen" />
  <link rel="stylesheet" type="text/css" href="client/css/stylesv2.css" media="screen" />
  <link rel="stylesheet" type="text/css" href="client/css/jgrowl.css" media="screen,handheld" />
  <!-- END Styles -->

  <!-- START Scripts
    -- We should minimize these latter; I mean... *Will Smith Voice* DAMN!!
    -- Also, its worth noting that, while not fully "used" yet, functions generally use a prefix naming convention if they are directly written by me for one of my products (fim or jparsons), or if a jquery extension. -->
  <script src="client/js/jquery-1.5.1.min.js" type="text/javascript"></script>
  <script src="client/js/jquery-ui-1.8.11.custom.min.js" type="text/javascript"></script>
  <script src="client/js/jquery.cookie.js" type="text/javascript"></script>
  <script src="client/js/jquery.contextMenu.min.js" type="text/javascript"></script>

  <script src="client/js/phpjs-base64.min.js" type="text/javascript"></script>
  <script src="client/js/phpjs-strReplace.min.js" type="text/javascript"></script>

  <script src="client/js/fim-nav.js" type="text/javascript"></script>
  <script src="client/js/fim-contextMenuParse.js" type="text/javascript"></script>

  <script src="client/js/jparsons-textEntry.min.js" type="text/javascript"></script>
  <script src="client/js/jparsons-previewFile.js" type="text/javascript"></script>

  <script src="client/js/errorLogging.js" type="text/javascript"></script>
  <script src="client/js/beeper.min.js" type="text/javascript"></script>
  <script src="client/js/youtube.min.js" type="text/javascript"></script>
  <script src="client/js/tooltip.js" type="text/javascript"></script>
  <script src="client/js/jgrowl.js" type="text/javascript"></script>
  <!-- END Scripts -->

  <!-- IE9 Stuff is Back! w00t! -->
<?php

    if ($user['favRooms']) {
      $favRooms = sqlArr("SELECT * FROM {$sqlPrefix}rooms WHERE options & 4 = FALSE AND id IN ($user[favRooms])",'id');

      foreach ($favRooms AS $id => $room2) {
        if (!hasPermission($room2,$user,'post')) {
          $currentRooms = explode(',',$user['favRooms']);
          foreach ($currentRooms as $room3) if ($room3 != $room2['id'] && $room3 != '') $currentRooms2[] = $room3; // Rebuild the array without the room ID.
          $newRoomString = mysqlEscape(implode(',',$currentRooms2));

          mysqlQuery("UPDATE {$sqlPrefix}users SET favRooms = '$newRoomString' WHERE userid = $user[userid]");
          continue;
        }

        $room2['name'] = addslashes($room2['name']);

        $roomMs .= "    window.external.msSiteModeAddJumpListItem('$room[name]','{$installUrl}chat.php?room=$room2[id]','{$installUrl}images/favicon.ico');
";
        $roomHtml .= "          <li><a href=\"./chat.php?room=$room2[id]\" class=\"room\" data-roomid=\"$room2[id]\">$room2[name]</a></li>
";
      }
    }

echo '  <meta name="application-name" content="' . ($brandingTitle ?: 'FreezeMessenger') . '" /> 
  <meta name="msapplication-tooltip" content="Launch ' . ($brandingTitle ?: 'FreezeMessenger') . ' Web Interace" /> 
  <meta name="msapplication-navbutton-color" content="black" />
  <meta name="msapplication-task" content="name=Archive;action-uri=' . $installUrl . 'archive.php;icon-uri=http://vrim.victoryroad.net/images/favicon.ico" />
  <meta name="msapplication-task" content="name=Room List;action-uri=' . $installUrl . 'viewRooms.php;icon-uri=http://vrim.victoryroad.net/images/favicon.ico" />
  <meta name="msapplication-task" content="name=View Stats;action-uri=' . $installUrl . 'stats.php;icon-uri=http://vrim.victoryroad.net/images/favicon.ico" />

  <script type="text/javascript">
  try {
    window.external.msSiteModeCreateJumplist(\'Favourite Rooms\');
' . $roomMs . '  }
  catch (ex) {
    // Do nothing.
  }
  </script>
';
?>
</head>

<body<?php echo $bodyHook; ?>>
<div id="tooltext" class="tooltip-content"></div>
<div id="page">
  <div id="header">
    <?php

    echo '<!-- START links -->
    <div id="menu">
      <h3><a href="#">Community</a></h3>
      <ul>
        <li><a href="http://victoryroad.net/">VictoryRoad Forums</a></li>
        <li><a href="http://victoryroad.net/arcade.php">VictoryRoad Arcade</a></li>
        <li><a href="http://dex.victoryroad.net/">VictoryRoad PokéDex</a></li>
        <li><a href="http://battles.victoryroad.net/">VictoryRoad VictoryBattles</a></li>
        <li><a href="http://www.floatzel.net/">Floatzel.net</a></li>
      </ul>
      <h3><a href="#">Quick Links</a></h3>
      <ul>
        <li style="border-bottom: 1px solid;"><a href="./archive.php">Message Archive</a></li>
        <li><a href="./viewRooms.php">Room List</a></li>
        ' . ($user['userid'] && $allowRoomCreation ? '<li><a href="#" id="createRoom">Create a Room</a></li>
        <li><a href="#" id="editRoom">Edit This Room</a></li>
        <li style="border-bottom: 1px solid;"><a href="#" id="priateRoom">Enter Private IM</a></li>' : '') . '
        <li><a href="#" id="online">Who\'s Online?</a></li>
        <li><a href="./stats.php">View Stats</a></li>
        ' . ($user['userid'] ? '<li><a href="#" id="manageKick">Manage Kicked Users</a></li>' : '') . '
        ' . ($user['userid'] ? '<li><a href="#" id="kick">Kick a User</a></li>' : '') . '
      </ul>
      <h3><a href="#">Me</a></h3>
      <ul>
        ' . ($user['settings'] & 16 ? '<li><a href="./moderate.php">AdminCP</a></li>
        <ul><li><a href="./moderate.php?do=showimages">Moderate Images</a></li>
        <li><a href="./moderate.php?do=listusers">Moderate Users</a></li>
        <ul><li><a href="./moderate.php?do=banuser">Ban a User</a></li>
        <li><a href="./moderate.php?do=unbanuser">Unban a User</a></li></ul>
        <li><a href="./moderate.php?do=censor">Modify Censor</a></li>
        <li><a href="./moderate.php?do=maintence">Maintence</a></li></ul>' : '') . '
        ' . ($user['userid'] ? '
        <li style="border-bottom: 1px solid;"><a href="#" id="changeSettings">Change Settings</a></li>' : '') . '
        ' . ($user['userid'] ? '<li><a href="./logout.php">Logout</a></li>' : '<li><a href="./login.php">Login</a></li>') . '
      </ul>
      <h3><a href="#">Rooms</a></h3>
      <div id="rooms">
        <ul id="roomList">
' . $roomHtml . '          <li><a href="javascript:void(0);" onclick="showAllRooms();">Show All</a></li>
        </ul>
      </div>' . ($inRoom ? '
      <h3><a href="#">Active Users</a></h3>
      <div id="activeUsers">Loading...</div>' : '') . '
      <h3><a href="#">Copyright</a></h3>
      <div>
        <ul>
          <li>FIM © 2010-2011 Joseph Parsons<br /></li>
          <li>jQuery Plugins © Their Respective Owners.</li>
          <li><a href="#" id="copyrightLink">See All Copyrights.</a></li>
        </ul>
      </div>
    </div>
    <!-- END links -->';
  ?>
  </div>
  <div id="content">
  <!-- START content -->
