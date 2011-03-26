<!DOCTYPE HTML>
<!-- Original Source Code Copyright © 2011 Joseph T. Parsons. -->
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
  <title><?php echo ($brandingTitle ?: 'IceIM') . ($title ? " - $title" : ''); ?></title>
  <meta name="robots" content="index, follow" />
  <meta name="author" content="Joseph T. Parsons" />
  <meta name="keywords" content="instant messenger, im, instant message<?php echo ($brandingKeywords ? ", $brandingKeywords" : '') . ($keywords ? ', ' . implode(', ',$keywords) : ''); ?>" />
  <meta name="description" content="The VictoryRoad Instant Messenger is a powerful online instant messenger that supports secure one-on-one messenging, chat rooms, advance archiving, and more. It works easily on a variety of platforms, including mobile." />
  <link rel="icon" id="favicon" type="image/gif" href="/images/favicon.gif" />

  <!-- START Scripts -->
  <script src="client/js/jquery-1.5.1.min.js" type="text/javascript"></script>
  <script src="client/js/jquery-ui-1.8.11.custom.min.js" type="text/javascript"></script>
  <script src="client/js/jquery.cookie.js" type="text/javascript"></script>
  <script src="client/js/contextMenu.js" type="text/javascript"></script>
  <script src="client/js/contextMenuParse.js" type="text/javascript"></script>
  <script src="client/js/beeper.min.js" type="text/javascript"></script>
  <script src="client/js/youtube.min.js" type="text/javascript"></script>
  <script src="client/js/textEntry.min.js" type="text/javascript"></script>
  <script src="client/js/previewFile.js" type="text/javascript"></script>
  <script src="client/js/encrypt.min.js" type="text/javascript"></script>
  <script src="client/js/strReplace.min.js" type="text/javascript"></script>
  <script src="client/js/tooltip.js" type="text/javascript"></script>
  <script src="client/js/staticFunctions.js" type="text/javascript"></script>
  <script src="client/js/fim-nav.js"></script>
  <script src="client/js/jgrowl.js"></script>
  <!-- END Scripts -->

  <!-- START Styles -->
  <link rel="stylesheet" type="text/css" href="client/css/stylesv2.css" media="screen" />
  <link rel="stylesheet" type="text/css" href="client/css/ui-darkness/jquery-ui-1.8.11.custom.css" media="screen" />
  <!-- END Styles -->
</head>

<body>
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
        <li style="border-bottom: 1px solid;"><a href="/index.php?action=archive">Message Archive</a></li>
        <li><a href="/index.php?action=viewRooms">Room List</a></li>
        ' . ($user['userid'] && $allowRoomCreation ? '<li><a href="#" id="createRoom">Create a Room</a></li>
        <li><a href="#" id="editRoom">Edit This Room</a></li>
        <li style="border-bottom: 1px solid;"><a href="/index.php?action=privateRoom">Enter Private IM</a></li>' : '') . '
        <li><a href="#" id="online">Who\'s Online?</a></li>
        <li><a href="/index.php?action=stats">View Stats</a></li>
        ' . ($user['userid'] ? '<li><a href="#" id="manageKick">Manage Kicked Users</a></li>' : '') . '
        ' . ($user['userid'] ? '<li><a href="/index.php?action=kick">Kick a User</a></li>' : '') . '
      </ul>
      <h3><a href="#">Me</a></h3>
      <ul>
        ' . ($user['settings'] & 16 ? '<li><a href="/index.php?action=moderate">AdminCP</a></li>' : '') . '
        ' . ($user['userid'] ? '
        <li style="border-bottom: 1px solid;"><a href="#" id="changeSettings">Change Settings</a></li>' : '') . '
        ' . ($user['userid'] ? '<li><a href="/index.php?action=logout">Logout</a></li>' : '<li><a href="/index.php">Login</a></li>') . '
      </ul>
      <h3><a href="#">Rooms</a></h3>
      <div id="rooms">
        <ul id="roomList">
  ' . $roomHtml . '
        <li><a href="javascript:void(0);" onclick="showAllRooms();">Show All</a></li>
        </ul>
      </div>
      <h3><a href="#">Active Users</a></h3>
      <div id="activeUsers">Loading...</div>
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
