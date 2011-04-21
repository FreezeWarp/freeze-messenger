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



/*** Major Phrase Defaults ***/

if (!$phrases['doctype']) {
  $phrases['doctype'] = '<!DOCTYPE HTML>';
}
if (!$phrases['brandingTitle']) {
  $phrases['brandingTitle'] = 'FreezeMessenger';
}
if (!$phrases['brandingFaviconIE'] && $phrase['brandingFavicon']) {
  $phrases['brandingFaviconIE'] = $phrase['brandingFavicon'];
}


/*** Keyword Generation ***/

if ($phrases['keywords']) {
  $keyWordString .= ", $phrases[keywords]";
}
if ($keywords) {
  $keyWordString .= ", $keywords";
}



/*** Start ***/

eval(hook('templateStart'));

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
  <link rel=\"stylesheet\" type=\"text/css\" href=\"client/css/ui-darkness/jquery-ui-1.8.11.custom.css\" media=\"screen\" />
  <link rel=\"stylesheet\" type=\"text/css\" href=\"client/css/stylesv2.css\" media=\"screen\" />
  <link rel=\"stylesheet\" type=\"text/css\" href=\"client/css/jgrowl.css\" media=\"screen,handheld\" />
  <!-- END Styles -->

  <!-- START Scripts
    -- We should minimize these latter; I mean... *Will Smith Voice* DAMN!!
    -- Also, its worth noting that, while not fully \"used\" yet, functions generally use a prefix naming convention if they are directly written by me for one of my products (fim or jparsons), or if a jquery extension. -->
  <script src=\"client/js/jquery-1.5.1.min.js\" type=\"text/javascript\"></script>
  <script src=\"client/js/jquery-ui-1.8.11.custom.min.js\" type=\"text/javascript\"></script>
  <script src=\"client/js/jquery.cookie.js\" type=\"text/javascript\"></script>
  <script src=\"client/js/jquery.contextMenu.min.js\" type=\"text/javascript\"></script>

  <script src=\"client/js/phpjs-base64.min.js\" type=\"text/javascript\"></script>
  <script src=\"client/js/phpjs-strReplace.min.js\" type=\"text/javascript\"></script>

  <script src=\"client/js/fim-nav.js\" type=\"text/javascript\"></script>
  <script src=\"client/js/fim-contextMenuParse.js\" type=\"text/javascript\"></script>

  <script src=\"client/js/jparsons-previewFile.js\" type=\"text/javascript\"></script>

  <script src=\"client/js/errorLogging.js\" type=\"text/javascript\"></script>
  <script src=\"client/js/beeper.min.js\" type=\"text/javascript\"></script>
  <script src=\"client/js/youtube.min.js\" type=\"text/javascript\"></script>
  <script src=\"client/js/tooltip.js\" type=\"text/javascript\"></script>
  <script src=\"client/js/jgrowl.js\" type=\"text/javascript\"></script>
  <script src=\"http://jqueryui.com/themeroller/themeswitchertool/\" type=\"text/javascript\"></script><script>
  $(document).ready(function(){
    $('#switcher').themeswitcher();
  });
  </script>
  <!-- END Scripts -->

  <!-- IE9 Stuffz -->";


    /*** Process Favourite Rooms
       * Used for IE9 Coolness ***/

    if ($user['favRooms']) {
      $favRooms = sqlArr("SELECT * FROM {$sqlPrefix}rooms WHERE options & 4 = FALSE AND id IN ($user[favRooms])",'id');

      eval(hook('templateFavRoomsStart'));

      foreach ($favRooms AS $id => $room2) {
        eval(hook('templateFavRoomsEachStart'));

        if (!hasPermission($room2,$user,'post') && !$stop) {
          $currentRooms = explode(',',$user['favRooms']);
          foreach ($currentRooms as $room3) if ($room3 != $room2['id'] && $room3 != '') $currentRooms2[] = $room3; // Rebuild the array without the room ID.
          $newRoomString = mysqlEscape(implode(',',$currentRooms2));

          mysqlQuery("UPDATE {$sqlPrefix}users SET favRooms = '$newRoomString' WHERE userid = $user[userid]");
          continue;
        }

        $room2['name'] = addslashes($room2['name']);

        $roomMs .= "    window.external.msSiteModeAddJumpListItem('$room2[name]','{$installUrl}chat.php?room=$room2[id]','{$installUrl}images/favicon.ico');
";
        $roomHtml .= "          <li><a href=\"./chat.php?room=$room2[id]\" class=\"room\" data-roomid=\"$room2[id]\">$room2[name]</a></li>
";

        eval(hook('templateFavRoomsEachEnd'));
      }

      eval(hook('templateFavRoomsEnd'));
    }


echo "  <meta name=\"application-name\" content=\"$phrases[brandingTitle]\" /> 
  <meta name=\"msapplication-tooltip\" content=\"Launch $phrases[brandingTitle] Web Interace\" /> 
  <meta name=\"msapplication-navbutton-color\" content=\"$phrases[brandingIE9Color]\" />
  <meta name=\"msapplication-task\" content=\"name=$phrases[templateArchive];action-uri={$installUrl}archive.php;icon-uri=$phrases[brandingFaviconIE]\" />
  <meta name=\"msapplication-task\" content=\"name=$phrases[templateRoomList];action-uri={$installUrl}viewRooms.php;icon-uri=$phrases[brandingFaviconIE]\" />
  <meta name=\"msapplication-task\" content=\"name=$phrases[templateStats];action-uri={$installUrl}stats.php;icon-uri=$phrases[brandingFaviconIE]\" />

  <script type=\"text/javascript\">
  try {
    window.external.msSiteModeCreateJumplist('Favourite Rooms');
{$roomMs}
}
  catch (ex) {
    // Do nothing.
  }
  </script>
  {$phrases[hookHeadFull]}
  {$phrases[hookHeadAll]}
</head>

<body{$bodyHook}>" . '
<div id="tooltext" class="tooltip-content"></div>
<div id="page">' . "
  $phrases[hookPageStartAll]
  $phrases[hookPageStartFull]
  " . '
  <!-- START links -->
  <div id="menu">
    ' . ($phrases['brandingCommunityLinks'] ? "<h3><a href=\"#\">$phrases[templateCommunityLinksCat]</a></h3>$phrases[brandingCommunityLinks]" : '') . "
    <h3><a href=\"#\">$phrases[templateQuickCat]</a></h3>
    <ul>
      <li style=\"border-bottom: 1px solid;\"><a href=\"./archive.php\">$phrases[templateArchive]</a></li>
      <li><a href=\"./viewRooms.php\">$phrases[templateRoomList]</a></li>
      " . ($user['userid'] && $allowRoomCreation ? "<li><a href=\"#\" id=\"createRoom\">$phrases[templateCreateRoom]</a></li>" : '') . "
      " . ($user['userid'] && $inRoom ? "<li><a href=\"#\" id=\"editRoom\">$phrases[templateEditRoom]</a></li>" : '') . "
      " . ($user['userid'] && $allowPrivateRooms ? "<li style=\"border-bottom: 1px solid;\"><a href=\"#\" id=\"privateRoom\">$phrases[templatePrivateIM]</a></li>" : '') . "
      <li><a href=\"#\" id=\"online\">$phrases[templateActiveUsers]</a></li>
      <li><a href=\"./stats.php\">$phrases[templateStats]</a></li>
      " . ($user['userid'] && $inRoom ? "<li><a href=\"#\" id=\"manageKick\">$phrases[manageKickedUser]</a></li>" : '') . "
      " . ($user['userid'] && $inRoom ? "<li><a href=\"#\" id=\"kick\">$phrases[templateKickUser]</a></li>" : '') . "
    </ul>
    <h3><a href=\"#\">$phrases[templateUserCat]</a></h3>
    <ul>
      " . ($user['settings'] & 16 ? "<li><a href=\"./moderate.php\">$phrases[templateAdmin]</a></li>
      <ul><li><a href=\"./moderate.php?do=showimages\">$phrases[templateAdminImages]</a></li>
      <li><a href=\"./moderate.php?do=listusers\">$phrases[templateAdminUsers]</a></li>
      <ul><li><a href=\"./moderate.php?do=banuser\">$phrases[templateAdminBanUser]</a></li>
      <li><a href=\"./moderate.php?do=unbanuser\">$phrases[templateAdminUnbanUser]</a></li></ul>
      <li><a href=\"./moderate.php?do=censor\">$phrases[templateAdminCensor]</a></li>
      <li><a href=\"./moderate.php?do=phrases\">$phrases[templateAdminPhrases]</a></li>
      <li><a href=\"./moderate.php?do=hooks\">$phrases[templateAdminHooks]</a></li>
      <li><a href=\"./moderate.php?do=maintenance\">$phrases[templateAdminMaintenance]</a></li></ul>" : '') . '
      ' . ($user['userid'] ? '
      <li style="border-bottom: 1px solid;"><a href="#" id="changeSettings">' . $phrases['templateChangeSettings'] . '</a></li>' : '') . '
      ' . ($user['userid'] ? '<li><a href="./logout.php">' . $phrases['templateLogout'] . '</a></li>' : '<li><a href="./login.php">' . $phrases['templateLogin'] . '</a></li>') . '
      ' . ($_GET['experimental'] || $_COOKIE['jquery-ui-theme'] ? '<li id="switcher"></li>' : '') . "
    </ul>
    <h3><a href=\"#\">$phrases[templateRoomListCat]</a></h3>
    <div id=\"rooms\">
      <ul id=\"roomList\">
{$roomHtml}<li><a href=\"javascript:void(0);\" onclick=\"showAllRooms();\">$phrases[templateShowAllRooms]</a></li>
      </ul>
    </div>" . ($inRoom ? "
    <h3><a href=\"#\">$phrases[templateActiveUsersCat]</a></h3>
    <div id=\"activeUsers\">$phrases[templateLoading]</div>" : '') . "
    <h3><a href=\"#\">$phrases[templateCopyrightCat]</a></h3>
    <div>
      <ul>
        <li>FIM © 2010-2011 Joseph Parsons<br /></li>
        <li>jQuery Plugins © Their Respective Owners.</li>
        <li><a href=\"#\" id=\"copyrightLink\">$phrases[templateAllCopyrights]</a></li>
      </ul>
    </div>
  </div>
  <!-- END links -->

  <div id=\"content\">
  <!-- START content -->
    $phrases[hookContentStartAll]
    $phrases[hookContentStartFull]";
?>