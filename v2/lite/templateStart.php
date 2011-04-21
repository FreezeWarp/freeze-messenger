<?php
if ($phrases['keywords']) {
  $keyWordString .= ", $phrases[keywords]";
}
if ($keywords) {
  $keyWordString .= ", $keywords";
}

echo "$phrases[doctype]
<!-- Original Source Code Copyright © 2011 Joseph T. Parsons. -->
<html xmlns=\"http://www.w3.org/1999/xhtml\">
<head>
  <title>$phrases[brandingTitle] - $title</title>
  <meta name=\"robots\" content=\"index, follow\" />
  <meta name=\"author\" content=\"Joseph T. Parsons\" />
  <meta name=\"keywords\" content=\"instant messenger, im, instant message$keyWordString\" />
  <meta name=\"description\" content=\"$phrases[brandingDescription]\" />
  <link rel=\"icon\" id=\"favicon\" type=\"image/gif\" href=\"$phrases[brandingFavicon]\" />
  <!--[if lte IE 9]>
  <link rel=\"shortcut icon\" id=\"faviconfallback\" href=\"$phrases[brandingFaviconIE]\" />
  <![endif]-->

  <!-- START Styles -->
  <link rel=\"stylesheet\" type=\"text/css\" href=\"../client/css/stylesLite";

  switch($mode) {
    case 'simple': echo 'Simple'; break;
    case 'mobile': echo 'Mobile'; break;
  }

  echo ".css\" media=\"screen,handheld\" />

  <link rel=\"stylesheet\" type=\"text/css\" href=\"../client/css/jgrowl.css\" media=\"screen,handheld\" />
  " . ($_GET['popup'] ? '<link rel="stylesheet" type="text/css" href="../client/css/stylesLitePopup.css" media="screen,handheld" />' : '') . "
  <!-- END Styles -->

  <!-- START Scripts -->
  <script src=\"../client/js/jquery-1.5.1.min.js\" type=\"text/javascript\"></script>
  <script src=\"../client/js/jquery.cookie.js\" type=\"text/javascript\"></script>
  <script src=\"../client/js/jquery.contextMenu.min.js\" type=\"text/javascript\"></script>

  <script src=\"../client/js/fim-navLite.js\" type=\"text/javascript\"></script>
  <script src=\"../client/js/errorLogging.js\" type=\"text/javascript\"></script>
  <!-- END Scripts -->
  {$phrases[hookHeadLite]}
  {$phrases[hookHeadAll]}
</head>


<!--[if lte IE 7]>
<body class=\"quirks\"{$bodyHook}>
<![endif]-->
<body{$bodyHook}>" . '
<div id="page">' . "
  $phrases[hookPageStartAll]
  $phrases[hookPageStartLite]
  " . '
  <!-- START links -->
  <div id="menubar">
    <ul id="menu" class="cssdropdown">

    ' . ($phrases['brandingCommunityLinks'] ? '
      <li class="headlink">Community ▼' . $phrases['brandingCommunityLinks'] . '</li>' : '') . '
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
  <!-- END links -->

  <div id="content">
    <!-- START content -->
' . "
    $phrases[hookContentStartAll]
    $phrases[hookContentStartLite]";
?>