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
  <link rel="shortcut icon" id="faviconfallback" href="<?php echo ($branding['faviconIE'] ? $branding['faviconIE'] : ($branding['favicon'] ? $branding['favicon'] : '/images/favicon1632.ico')); ?>"/>
  <![endif]-->

  <!-- START Scripts -->
  <script src="../client/js/jquery-1.5.1.min.js" type="text/javascript"></script>
  <script src="../client/js/jquery.cookie.js" type="text/javascript"></script>
  <script src="../client/js/jquery.contextMenu.min.js" type="text/javascript"></script>

  <script src="../client/js/fim-navLite.js" type="text/javascript"></script>
  <script src="../client/js/errorLogging.js" type="text/javascript"></script>
  <!-- END Scripts -->

  <!-- START Styles -->
  <link rel="stylesheet" type="text/css" href="../client/css/stylesLite<?php switch($mode) { case 'simple': echo 'Simple'; break; case 'mobile': echo 'Mobile'; break; } ?>.css" media="screen,handheld" />
  <link rel="stylesheet" type="text/css" href="http://www.victorybattles.net/index.php?page=ajax&amp;go=style&amp;styleid=<?php echo $user['styleid']; ?>" media="screen,handheld" />
  <link rel="stylesheet" type="text/css" href="../client/css/jgrowl.css" media="screen,handheld" />
  <?php if ($_GET['popup']) echo '<link rel="stylesheet" type="text/css" href="../client/css/stylesLitePopup.css" media="screen,handheld" />'; ?>
  <!-- END Styles -->
<?php
if ($includeIE9 == true && $mode == 'normal') {
  echo '
  <!-- IE9 Madness (Yes, I\'ll even do things that only work on it) -->
  <meta name="application-name" content="Victory Road Instant Messenger" />
  <meta name="msapplication-tooltip" content="Launch VRIM Web Interace" />
  <meta name="msapplication-navbutton-color" content="black" />
  <meta name="msapplication-task" content="name=Archive;action-uri=http://vrim.victoryroad.net./index.php?action=archive;icon-uri=http://vrim.victoryroad.net/images/favicon.ico" />
  <meta name="msapplication-task" content="name=Room List;action-uri=http://vrim.victoryroad.net./index.php?action=viewRooms;icon-uri=http://vrim.victoryroad.net/images/favicon.ico" />
  <meta name="msapplication-task" content="name=Edit a Room;action-uri=http://vrim.victoryroad.net./index.php?action=editRoom;icon-uri=http://vrim.victoryroad.net/images/favicon.ico" />
  <meta name="msapplication-task" content="name=Who\'s Online;action-uri=http://vrim.victoryroad.net./index.php?action=online;icon-uri=http://vrim.victoryroad.net/images/favicon.ico" />
  <meta name="msapplication-task" content="name=View Stats;action-uri=http://vrim.victoryroad.net./index.php?action=stats;icon-uri=http://vrim.victoryroad.net/images/favicon.ico" />
  <meta name="msapplication-task" content="name=Manage Kicked Users;action-uri=http://vrim.victoryroad.net./index.php?action=manageKick;icon-uri=http://vrim.victoryroad.net/images/favicon.ico" />
  <script type="text/javascript">
  try {
    window.external.msSiteModeCreateJumplist(\'Favourite Rooms\');
';
  foreach ($rooms AS $room4) {
    //echo '  <meta name="msapplication-task" content="name=' . $room4['name'] . ';action-uri=http://vrim.victoryroad.net./index.php?room=' . $room4['id'] . '" />';
    echo "    window.external.msSiteModeAddJumpListItem('" . addslashes($room4['name']) . "','http://vrim.victoryroad.net./index.php?room=$room4[id]','http://vrim.victoryroad.net/images/favicon.ico');
";
  }

echo '
  }
  catch (ex) {
    // Do nothing.
  }
  </script>';
}
?>
</head>


<!--[if lte IE 7]>
<body class="quirks">
<![endif]-->
<body<?php echo $bodyHook; ?>>
<div id="page">
  <div id="header">
    <?php
    if ($_GET['popup'] == false) { echo '<div id="banner"><a href="./index.php"><img src="../client/vrim.png" alt="Return Home" /></a></div>

';
        echo '    <!-- START links -->
    <div id="menubar">
      <ul id="menu" class="cssdropdown">
        <li class="headlink">Community ▼
          <ul>
            <li><a href="http://victoryroad.net/">VictoryRoad Forums</a></li>
            <li><a href="http://victoryroad.net/arcade.php">VictoryRoad Arcade</a></li>
            <li><a href="http://dex.victoryroad.net/">VictoryRoad PokéDex</a></li>
            <li><a href="http://battles.victoryroad.net/">VictoryRoad VictoryBattles</a></li>
            <li><a href="http://www.floatzel.net/">Floatzel.net</a></li>
          </ul>
        </li>
        <li class="headlink">Quick Links ▼
          <ul>
            <li style="border-bottom: 1px solid;"><a href="./index.php?action=archive">Message Archive</a></li>
            <li><a href="./index.php?action=viewRooms">Room List</a></li>
            ' . ($user['userid'] && $allowRoomCreation ? '<li><a href="./index.php?action=createRoom">Create a Room</a></li>
            <li><a href="./index.php?action=editRoom">Edit a Room</a></li>
            <li style="border-bottom: 1px solid;"><a href="./index.php?action=privateRoom">Enter Private IM</a></li>' : '') . '
            <li><a href="./index.php?action=online">Who\'s Online?</a></li>
            <li><a href="./index.php?action=stats">View Stats</a></li>
            ' . ($user['userid'] ? '<li><a href="./index.php?action=manageKick">Manage Kicked Users</a></li>' : '') . '
            ' . ($user['userid'] ? '<li><a href="./index.php?action=kick">Kick a User</a></li>' : '') . '
          </ul>
        </li>
        <li class="headlink">Logged in as ' . ($user['username'] ?: 'Guest') . ' ▼
          <ul>
            <li><a href="javascript:void(0);" onclick="createCookie(\'vrim10-mode\',\'normal\',7 * 24 * 3600); location.reload(true);">Switch to Normal Layout</a></li>
            <li><a href="javascript:void(0);" onclick="createCookie(\'vrim10-mode\',\'simple\',7 * 24 * 3600); location.reload(true);">Switch to Light Layout</a></li>
            <li style="border-bottom: 1px solid;"><a href="javascript:void(0);" onclick="createCookie(\'vrim10-mode\',\'mobile\',7 * 24 * 3600); location.reload(true);">Switch to Mobile Layout</a></li>
            ' . ($user['settings'] & 16 ? '<li><a href="/index.php?action=moderate">AdminCP</a></li>' : '') . '
            ' . ($user['userid'] ? '
            <li style="border-bottom: 1px solid;"><a href="./index.php?action=options">Change Settings</a></li>' : '') . '
            ' . ($user['userid'] ? '<li><a href="./index.php?action=logout">Logout</a></li>' : '<li><a href="./index.php">Login</a></li>') . '
          </ul>
      </ul>
    </div>
    <!-- END links -->';
    } ?>
  </div>
  <div id="content">

    <!-- START content -->
