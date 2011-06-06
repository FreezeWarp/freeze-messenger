CREATE TABLE IF NOT EXISTS `{prefix}templates` (
  `id` int(10) NOT NULL AUTO_INCREMENT,
  `name` varchar(50) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL,
  `vars` varchar(1000) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL,
  `data` longtext CHARACTER SET utf8 COLLATE utf8_bin NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE={engine} DEFAULT CHARSET=utf8;

-- DIVIDE

INSERT INTO `{prefix}templates` (`id`, `name`, `vars`, `data`) VALUES
(1, 'templateStart', 'allowRoomCreation,inRoom,allowPrivateRooms,bodyHook,mode,styleHook,layout,style', '<?xml version="1.0" encoding="UTF-8"?>$phrases[doctype]\r\n<!-- Original Source Code Copyright © 2011 Joseph T. Parsons. -->\r\n<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en">\r\n<head>\r\n  <title>$phrases[brandingTitle] - $title</title><meta http-equiv="Content-type" value="text/html; charset=utf-8" />\r\n  <meta name="robots" content="index, follow" />\r\n  <meta name="author" content="Joseph T. Parsons" />\r\n  <meta name="keywords" content="instant messenger, im, instant message$keyWordString" />\r\n  <meta name="description" content="$phrases[brandingDescription]" />\r\n  <link rel="icon" id="favicon" type="image/png" href="$phrases[brandingFavicon]" />\r\n  <!--[if lte IE 9]>\r\n  <link rel="shortcut icon" id="faviconfallback" href="$phrases[brandingFaviconIE]" />\r\n  <![endif]-->\r\n  \r\n  <!-- START Styles -->\r\n  <link rel="stylesheet" type="text/css" href="client/css/$style/jquery-ui-1.8.11.custom.css" media="screen" />\r\n  <link rel="stylesheet" type="text/css" href="client/css/$style/fim3.0.css" media="screen" />\r\n  <link rel="stylesheet" type="text/css" href="client/css/stylesv2.css" media="screen" />\r\n  {$styleHook}\r\n  <!-- END Styles -->\r\n\r\n  <!-- START Scripts -->\r\n  <script src="client/js/jquery-1.6.1.min.js" type="text/javascript"></script>\r\n\r\n  <script src="client/js/jquery-ui-1.8.11.custom.min.js" type="text/javascript"></script>\r\n  <script src="client/js/jquery.plugins.05182011.min.js" type="text/javascript"></script>\r\n\r\n  <script src="client/js/fim-all.js" type="text/javascript"></script>\r\n\r\n<!-- END Scripts -->\r\n\r\n  <!-- IE9 Stuffz -->\r\n  <meta name="application-name" content="$phrases[brandingTitle]" /> \r\n  <meta name="msapplication-tooltip" content="Launch $phrases[brandingTitle] Web Interace" /> \r\n  <meta name="msapplication-navbutton-color" content="$phrases[brandingIE9Color]" />\r\n  <meta name="msapplication-task" content="name=$phrases[templateArchive];action-uri={$installUrl}archive.php;icon-uri=$phrases[brandingFaviconIE]" />\r\n  <meta name="msapplication-task" content="name=$phrases[templateRoomList];action-uri={$installUrl}viewRooms.php;icon-uri=$phrases[brandingFaviconIE]" />\r\n  <meta name="msapplication-task" content="name=$phrases[templateStats];action-uri={$installUrl}stats.php;icon-uri=$phrases[brandingFaviconIE]" />\r\n\r\n  <script type="text/javascript">\r\n  try {\r\n    window.external.msSiteModeCreateJumplist(''Favourite Rooms'');\r\n$template[roomMs]\r\n}\r\n  catch (ex) {\r\n    // Do nothing.\r\n  }\r\n  </script>\r\n  {$phrases[hookHeadFull]}\r\n  {$phrases[hookHeadAll]}\r\n</head>\r\n\r\n<body{$bodyHook} data-mode="$mode" data-layout="$layout" data-userId="$user[userId]">\r\n<div id="tooltext" class="tooltip-content"></div>\r\n<div id="page" data-role="page">\r\n  $phrases[hookPageStartAll]\r\n  $phrases[hookPageStartFull]\r\n  <!-- START links -->\r\n  <div id="menu" data-role="header">\r\n    {{if="$phrases[''brandingCommunityLinks'']"}{\r\n      <h3><a href="#">$phrases[templateCommunityLinksCat]</a></h3>\r\n      $phrases[brandingCommunityLinks]\r\n    }}\r\n    <h3><a href="#">$phrases[templateQuickCat]</a></h3>\r\n    <ul> \r\n      <li style="border-bottom: 1px solid;"><a href="#" id="messageArchive">Message Archive</a></li> \r\n      <li><a href="#" id="roomList">Room List</a></li> \r\n      <li><a href="#" id="createRoom">Create a Room</a></li> \r\n      <li><a href="#" id="editRoom">Edit Room</a></li> \r\n      <li style="border-bottom: 1px solid;"><a href="#" id="privateRoom">Enter Private IM</a></li> \r\n      <li><a href="#" id="online">Who''s Online</a></li> \r\n      <li><a href="#" id="viewStats">View Stats</a></li> \r\n      <li><a href="#" id="manageKick"></a></li> \r\n      <li><a href="#" id="kick">Kick a User</a></li> \r\n    </ul>\r\n    <h3><a href="#">$phrases[templateUserCat]</a></h3>\r\n    <ul>\r\n      {{if="$user[''settings''] & 16"}{<li><a href="./moderate.php">$phrases[templateAdmin]</a></li>\r\n      <ul><li><a href="./moderate.php?do=showimages">$phrases[templateAdminImages]</a></li>\r\n      <li><a href="./moderate.php?do=listusers">$phrases[templateAdminUsers]</a></li>\r\n      <ul><li><a href="./moderate.php?do=banuser">$phrases[templateAdminBanUser]</a></li>\r\n      <li><a href="./moderate.php?do=unbanuser">$phrases[templateAdminUnbanUser]</a></li></ul>\r\n      <li><a href="./moderate.php?do=censor">$phrases[templateAdminCensor]</a></li>\r\n      <li><a href="./moderate.php?do=phrases">$phrases[templateAdminPhrases]</a></li>\r\n      <li><a href="./moderate.php?do=hooks">$phrases[templateAdminHooks]</a></li>\r\n        <li><a href="./moderate.php?do=templates">$phrases[templateAdminTemplates]</a></li>\r\n      <li><a href="./moderate.php?do=maintenance">$phrases[templateAdminMaintenance]</a></li></ul>}}\r\n      {{if="$user[''userId'']"}{\r\n      <li style="border-bottom: 1px solid;"><a href="#" id="changeSettings">$phrases[templateChangeSettings]</a></li>}}\r\n      {{if="$user[''userId'']"}{<li><a href="./logout.php">$phrases[templateLogout]</a></li>}{\r\n      <li><a href="./login.php">$phrases[templateLogin]</a></li>}}\r\n      {{if="$_GET[''experimental''] || $_COOKIE[''jquery'']"}{<li id="switcher"></li>}}\r\n    </ul>\r\n    <h3><a href="#">$phrases[templateRoomListCat]</a></h3>\r\n    <div id="rooms">\r\n      <ul id="roomList">\r\n$template[roomHtml]<li><a href="javascript:void(0);" onclick="showAllRooms();">$phrases[templateShowAllRooms]</a></li>\r\n      </ul>\r\n    </div>\r\n    {{if="$inRoom"}{\r\n    <h3><a href="#">$phrases[templateActiveUsersCat]</a></h3>\r\n    <div id="activeUsers">$phrases[templateLoading]</div>}{    }}\r\n    <h3><a href="#">$phrases[templateCopyrightCat]</a></h3>\r\n    <div>\r\n      <ul>\r\n        <li>FIM © 2010-2011 Joseph Parsons<br /></li>\r\n        <li>jQuery Plugins © Their Respective Owners.</li>\r\n        <li><a href="#" id="copyrightLink">$phrases[templateAllCopyrights]</a></li>\r\n      </ul>\r\n    </div>\r\n\r\n  </div>\r\n  <!-- END links -->\r\n\r\n  <div id="content" data-role="content">\r\n  <!-- START content -->\r\n    $phrases[hookContentStartAll]\r\n    $phrases[hookContentStartFull]'),
(2, 'templateEnd', '', '\r\n    $phrases[hookContentEndAll]\r\n    $phrases[hookContentEndFull]\r\n    <!-- END content -->\r\n  </div>\r\n\r\n$phrases[hookBodyEndAll]\r\n$phrases[hookBodyEndFull]\r\n</body>\r\n</html>'),
(3, 'templateRoomHtml', 'room2', '          <li><a href="./chat.php?room=$room2[id]" class="room" data-roomId="$room2[id]">$room2[name]</a></li>'),
(4, 'templateRoomMs', 'room2', '    window.external.msSiteModeAddJumpListItem(''$room2[name]'',''{$installUrl}chat.php?room=$room2[id]'',''{$installUrl}images/favicon.ico'');'),
(5, 'login', '', '<div id="normalLogin">\r\n  <br />\r\n\r\n    <form action="login.php" method="post" style="text-align: center; display: block;">\r\n    <label for="userName">$phrases[loginUsername]</label><br />\r\n    <input type="text" name="userName" /><br /><br />\r\n\r\n      <label for="password">$phrases[loginPassword]</label><br />\r\n    <input type="password" name="password" /><br /><br />\r\n\r\n      <label for="rememberme">$phrases[loginRemember]</label>\r\n    <input type="checkbox" name="rememberme" id="rememberme" /><br /><br />\r\n\r\n      <button type="submit">$phrases[loginSubmit]</button><button type="reset">$phrases[loginReset]</button></form>\r\n</div>'),
(6, 'guestLinks', '', '<ul>\r\n  <li><a href="index.php?action=online">Who''s Online</a></li>\r\n  <li><a href="viewRooms.php">Room List</a></li>\r\n  <li><a href="archive.php">Archives</a></li>\r\n  <ul>\r\n    <li><a href="index.php?action=archive&roomId=1">Main</a></li>\r\n  </ul>\r\n</ul>'),
(7, 'container', 'title,content,class', '<table class="$class ui-widget">\r\n  <thead>\r\n    <tr class="hrow ui-widget-header ui-corner-top">\r\n      <td>$title</td>\r\n    </tr>\r\n  </thead>\r\n  <tbody class="ui-widget-content ui-corner-bottom">\r\n    <tr>\r\n      <td>\r\n        <div>$content</div>\r\n      </td>\r\n    </tr>\r\n  </tbody>\r\n</table>'),
(8, 'archiveChooseSettings', 'roomSelect', '<form action="archive.php" method="get">\r\n<table class="leftright center">\r\n  <tr>\r\n    <td colspan="2">\r\n      <select name="roomId" id="roomId">\r\n        $roomSelect\r\n      </select>\r\n    </td>\r\n  </tr>\r\n  <tr>\r\n    <td>\r\n      <label for="numresults">$phrases[archiveNumResultsLabel]</label>\r\n    </td>\r\n    <td>\r\n      <select name="numresults" id="numresults">\r\n        <option value="10">10</option><option value="20">20</option>\r\n        <option value="50" selected="selected">50</option>\r\n        <option value="100">100</option>\r\n        <option value="500">500</option>\r\n      </select>\r\n    </td>\r\n  </tr>\r\n  <tr>\r\n    <td>\r\n      <label for="oldfirst">$phrases[archiveReversePostOrderLabel]</label>\r\n    </td>\r\n    <td>\r\n      <input type="checkbox" name="oldfirst" id="oldfirst" value="true" />\r\n    </td>\r\n  </tr>\r\n  <tr>\r\n    <td>\r\n      <label for="userIds">$phrases[archiveUserIdsLabel]</label>\r\n    </td>\r\n    <td>\r\n      <input type="text" name="userIds" id="userIds"a />\r\n    </td>\r\n  </tr>\r\n  <tr>\r\n    <td colspan="2">\r\n      <button type="submit">$phrases[archiveSubmit]</button>\r\n    </td>\r\n  </tr>\r\n</table>\r\n\r\n</form>'),
(9, 'archiveChooseSettingsRoomOption', 'room2', '<option value="$room2[id]">$room2[name]</option>');

-- DIVIDE

INSERT INTO `{prefix}templates` (`id`, `name`, `vars`, `data`) VALUES
(10, 'statsView', 'tableHeader,tableContents', '<script style="text/javascript">\r\nfunction resize () {\r\n  $(''#stats'').css(''width'',((window.innerWidth - 10) * .7));\r\n}\r\n\r\n$(window).resize(resize);\r\n</script>\r\n\r\n<div style="overflow: auto;">\r\n<table class="page ui-widget rowHover" id="stats">\r\n  <thead class="ui-widget-header">\r\n  <tr class="hrow">\r\n    <td>$phrases[statsPlace] </td>$tableHeader   </tr>\r\n  </thead>\r\n  <tbody class="ui-widget-content">$tableContents \r\n  </tbody>\r\n</table>\r\n</div>'),
(11, 'statsChooseSettings', '', '\r\n<form action="stats.php" method="GET">\r\n  <label for="roomList">$phrases[statsRoomList]</label>\r\n  <input type="text" id="roomList" name="roomList" value="{$roomList}" /><br />\r\n\r\n  <label for="number">$phrases[statsNumResults]</label>\r\n  <select name="number" id="number">\r\n    <option value="10">10</option>\r\n    <option value="25">25</option>\r\n    <option value="50">50</option>$phrases[statsNumResultsHook]\r\n  </select><br /><br />\r\n\r\n  <button type="submit">$phrases[statsChooseSettingsSubmit]</button><button type="reset">$phrases[statsChooseSettingsReset]</button>\r\n</form>'),
(12, 'chatStopMessage', 'stopMessage', '<div id="stopMessage">\r\n    {{container}{Warning}{$stopMessage<br /><br />\r\n\r\n<form action="#" method="post">\r\n  <input type="button" onclick="$(''#stopMessage'').slideUp(); $(''#chatContainer'').slideDown();" value="Continue." />\r\n  <input type="button" onclick="window.history.back()" value="Go Back" />\r\n</form>}}\r\n    </div>'),
(13, 'chatTemplate', 'textboxStyle,stopMessage', '\r\n\r\n  <div id="chatContainer"{{if="$stopMessage"}{style="display: none;"}}>\r\n  {{container}{<div id="title">\r\n    <span id="status" class="leftPart">\r\n      {{if="$room[''options''] & 1"}{<img src="images/bookmarks.png" class="standard" title="This is an Official Room" alt="Official" />}}<br />\r\n      <span id="refreshStatus" onclick="alert(''Failed '' + totalFails + '' times. Current refreshing every '' + (timeout / 1000 + .1) + '' seconds.'');"></span>\r\n    </span>\r\n\r\n    <div id="rightTitle" class="rightPart">\r\n      <form action="#" onsubmit="return false;" class="rightPart">\r\n        <button type="button" class="standard" id="icon_settings"></button>\r\n\r\n        <button type="button" class="standard" id="icon_note"></button>\r\n\r\n        <button type="button" class="standard" id="icon_help"></button>\r\n      </form>\r\n    </div>\r\n    $room[name]<br />\r\n    <em id="topic$room[id]">$room[title]</em>\r\n  </div>}{\r\n  <div id="messageListContainer">\r\n    <div id="messageList">\r\n    </div>\r\n  </div>}}\r\n  <div id="textentryBoxMessage">\r\n    <form onsubmit="var message = $(''textarea#messageInput'').val(); if (message.length == 0) { alert(''Please enter your message.''); } else { sendMessage(message); $(''textarea#messageInput'').val(''''); } return false;" id="sendform">{{container}{<div class="leftPart">Enter a Message</div>\r\n      <div class="rightPart">\r\n        <button type="submit" class="standard" id="icon_submit"></button>\r\n        <button type="reset" class="standard" id="icon_reset"></button>\r\n      </div>}{\r\n      <div id="messageInputContainer" class="middle">\r\n        {{if="!$light"}{<div id="buttonMenuLeft">\r\n          {{if="$room[''bbcode''] <= 13"}{<button type="button" onclick="$(''#textentryBoxUrl'').dialog({width : ''600px'', title : ''Insert a Linked Document''});" class="standard" id="icon_url"></button><br />}}\r\n          {{if="$room[''bbcode''] <= 5"}{<button type="button" onclick="$(''#textentryBoxUpload'').dialog({width : ''600px'', title : ''Insert an Image''});" class="standard" id="icon_upload"></button><br />}}\r\n          {{if="$room[''bbcode''] <= 2"}{<button type="button" onclick="$(''#textentryBoxYoutube'').dialog({width : ''600px'', title : ''Insert a Youtube Video''});" class="standard" id="icon_video"></button>}}\r\n        </div>}}\r\n        <textarea onkeypress="if (event.keyCode == 13 && !event.shiftKey) { $(''#sendform'').submit(); return false; }" id="messageInput" autofocus="autofocus" placeholder="Enter your text." style="$textboxStyle"></textarea>\r\n      </div>}}\r\n    </form>\r\n</div>'),
(14, 'online', '', '<script src="client/js/fim-online.js"></script>\r\n<table class="page">\r\n  <thead>\r\n    <tr class="hrow">\r\n      <th>$phrases[onlineUsername]</th>\r\n      <th>$phrases[onlineRoom]</th>\r\n    </tr>\r\n  </thead>\r\n\r\n  <tbody id="onlineUsers">\r\n    <tr>\r\n      <td colspan="2">$phrases[onlineLoading]</td>\r\n    </tr>\r\n  </tbody>\r\n</table>'),
(16, 'createRoomSuccess', 'installUrl,insertId', '$phrases[createRoomCreatedAt]<br /><br />\r\n\r\n<form action="{$installUrl}index.php?room={$insertId}" method="post">\r\n  <input type="text" style="width: 300px;" value="{$installUrl}chat.php?room={$insertId}" name="url" />\r\n  <input type="submit" value="$phrases[editRoomCreatedGo]" />\r\n</form>'),
(17, 'editRoomForm', 'censorLists', '<form action="#" method="post" id="editRoomForm">\r\n  <label for="name">$phrases[editRoomNameLabel]</label>: <input type="text" name="name" id="name" value="$room[name]" /><br />\r\n  <small><span style="margin-left: 10px;">$phrases[editRoomNameBlurb]</span></small><br /><br />\r\n\r\n  <label for="allowedUsers">$phrases[editRoomAllowedUsersLabel]</label>: <input type="text" name="allowedUsers" id="allowedUsers" value="$room[allowedUsers]" /><br />\r\n  <small><span style="margin-left: 10px;">$phrases[editRoomAllowedUsersBlurb]</span></small><br /><br />\r\n\r\n  <label for="allowedGroups">$phrases[editRoomAllowedGroupsLabel]</label>: <input type="text" name="allowedGroups" id="allowedGroups" value="$room[allowedGroups]" /><br />\r\n  <small><span style="margin-left: 10px;">$phrases[editRoomAllowedGroupsBlurb]</span></small><br /><br />\r\n\r\n  <label for="moderators">$phrases[editRoomModeratorsLabel]</label>: <input type="text" name="moderators" id="moderators" value="$room[moderators]" /><br />\r\n  <small><span style="margin-left: 10px;">$phrases[editRoomModeratorsBlurb]</span></small><br /><br />\r\n\r\n  <label for="mature">$phrases[editRoomMatureLabel]</label>: <input type="checkbox" name="mature" id="mature" /><br />\r\n  <small><span style="margin-left: 10px;">$phrases[editRoomMatureBlurb]</strong></small><br /><br />\r\n\r\n  <label for="bbcode">$phrases[editRoomBBCode]</label>: <select name="bbcode">\r\n    <option value="1" selected="selected">$phrases[editRoomBBCodeAll]</option>\r\n    <option value="5">$phrases[editRoomBBCodeMulti]</option>\r\n    <option value="9">$phrases[editRoomBBCodeImg]</option>\r\n    <option value="13">$phrases[editRoomBBCodeLink]</option>\r\n    <option value="16">$phrases[editRoomBBCodeBasic]</option>\r\n    <option>$phrases[editRoomBBCodeNothing]</option>\r\n  </select><br />\r\n\r\n  <small style="margin-left: 10px;">$phrases[editRoomBBCodeBlurb]</small><br /><br />\r\n\r\n  <label>$phrases[editRoomCensorLabel]</label>:<br /><div style="margin-left: 10px;">{$censorLists}</div><br />\r\n\r\n  <button type="submit">$phrases[editRoomSubmit]</button><button type="reset">$phrases[editRoomReset]</button>\r\n</form>'),

(18, 'editRoomSuccess', 'room', 'Your group was successfully edited.<br /><br />\r\n\r\n<button onclick="window.location=''index.php?room=$room[id]">Go To It</button>'),
(19, 'kickForm', 'roomSelect,userSelect', '<script type="text/javascript">\r\n$(document).ready(function(){\r\n  $("#kickUserForm").submit(function(){\r\n    data = $("#kickUserForm").serialize(); // Serialize the form data for AJAX.\r\n    $.post("content/kick.php?phase=2",data,function(html) {\r\n      quickDialogue(html,'''',''kickUserResultDialogue'');\r\n    }); // Send the form data via AJAX.\r\n\r\n    $("#kickUserDialogue").dialog(''close'');\r\n\r\n    return false; // Don''t submit the form.\r\n  });\r\n});\r\n</script>\r\n\r\n<form action="#" id="kickUserForm" method="post">\r\n  <label for="userId">User</label>: <select name="userId">$userSelect</select><br />\r\n  <label for="roomId">Room</label>: <select name="roomId">$roomSelect</select><br />\r\n  <label for="time">Time</label>: <input type="text" name="time" id="time" style="width: 50px;" />\r\n  <select name="interval">\r\n    <option value="1">Seconds</option>\r\n    <option value="60">Minutes</option>\r\n    <option value="3600">Hours</option>\r\n    <option value="86400">Days</option>\r\n    <option value="604800">Weeks</option>\r\n  </select><br /><br />\r\n\r\n  <button type="submit">Kick User</button><button type="reset">Reset</button>\r\n</form>');

-- DIVIDE

INSERT INTO `{prefix}templates` (`id`, `name`, `vars`, `data`) VALUES
(20, 'manageKickForm', 'roomSelect', '<form action="index.php" method="GET">\r\n  <label for="roomId">Room: </label>\r\n  <select name="roomId" id="roomId">\r\n    $roomSelect\r\n  </select><br /><br />\r\n\r\n  <input type="submit" value="Go" />\r\n  <input type="hidden" name="action" value="manageKick" />\r\n</form>'),
(21, 'manageKickTable', '', '<script type="text/javascript">\r\n$(document).ready(function() {\r\n  $("form[data-formid=unkick]").submit(function(){\r\n    data = $(this).serialize(); // Serialize the form data for AJAX.\r\n    $.post("content/unkick.php?phase=2",data,function(html) {\r\n      quickDialogue(html,'''',''unkickDialogue'');\r\n    }); // Send the form data via AJAX.\r\n\r\n    $("#manageKickDialogue").dialog(''destroy'');\r\n\r\n    return false; // Don\\''t submit the form.\r\n  });\r\n});\r\n</script>\r\n\r\n<table class="page">\r\n  <thead>\r\n    <tr class="hrow"><td>User</td><td>Kicked By</td><td>Kicked On</td><td>Expires On</td><td>Actions</td></tr>\r\n  </thead>\r\n  <tbody>\r\n    $userRow\r\n  </tbody>\r\n</table>'),
(22, 'manageKickTableRow', 'kickedUser,room', '<tr>\r\n  <td>$kickedUser[userName]</td>\r\n  <td>$kickedUser[kickername]</td>\r\n  <td>$kickedUser[kickedOn]</td>\r\n  <td>$kickedUser[expiresOn]</td>\r\n  <td>\r\n    <form action="#" method="post" data-formid="unkick">\r\n      <input type="submit" value="Unkick" />\r\n      <input type="hidden" name="userId" value="$kickedUser[userId]" />\r\n      <input type="hidden" name="roomId" value="$room[id]" />\r\n    </form>\r\n  </td>\r\n</tr>'),
(23, 'userSettingsForm', 'roomData3,roomData4,watchList,enableDF', '<link rel="stylesheet" media="screen" type="text/css" href="client/colorpicker/css/colorpicker.css" />\r\n<script type="text/javascript" src="client/colorpicker/js/colorpicker.js"></script>\r\n<script type="text/javascript" src="client/js/fim-options.js"></script>\r\n\r\n<ul class="tabList">\r\n  <li><a href="#settings1">Chat Display</a></li>\r\n  {{if="$enableDF"}{\r\n  <li><a href="#settings2">Message Formatting</span></a></li>}}\r\n  <li><a href="#settings3">General</a></li>\r\n</ul>\r\n\r\n<form action="index.php?action=options&phase=2" method="post" id="changeSettingsForm">\r\n  <div id="settings1">\r\n  <label for="theme">Theme:</label> <select name="theme">\r\n    <option value="1">jQueryUI Darkness</option>\r\n    <option value="2">jQueryUI Lightness</option>\r\n    <option value="3">Redmond (High Contrast)</option>\r\n    <option value="4">Cupertino</option>\r\n    <option value="5">--</option>\r\n  </select><br />\r\n  <small><span style="margin-left: 10px;">Change the theming of the messenger to your liking. "Cupertino" and "jQueryUI" are good choices.</span></small><br /><br />\r\n\r\n  <label for="avatars">Show Avatars</label> <input type="checkbox" name="avatars" id="avatars" value="true"{{if="$user[''settingsOfficialAjax''] & 2048"}{ checked="checked"}} /><br />\r\n  <small><span style="margin-left: 10px;">Showing avatars will reduce the overall amount of text by hiding post times and userNames with a small avatar to represent users. Times can be viewed by moving the mouse over each message.</span></small><br /><br />\r\n\r\n  <label for="reverse">$phrases[settingsReversePostOrderLabel]</label> <input type="checkbox" name="reverse" id="reverse" value="true"{{if="$user[''settingsOfficialAjax''] & 1024"}{ checked="checked"}} /><br />\r\n  <small><span style="margin-left: 10px;">$phrases[settingsReversePostOrderBlurb]</span></small><br /><br />\r\n\r\n  <label for="disableding">$phrases[settingsDisableDingLabel]</label> <input type="checkbox" name="disableding" id="disableding" value="true"{{if="$user[''settingsOfficialAjax''] & 2048"}{ checked="checked"}} /><br />\r\n  <small><span style="margin-left: 10px;">$phrases[settingsDisableDingBlurb]</span></small><br /><br />\r\n\r\n  <label for="disableFormatting">$phrases[settingsDisableFormattingLabel]</label> <input type="checkbox" name="disableFormatting" id="disableFormatting" value="true"{{if="$user[''settingsOfficialAjax''] & 16"}{ checked="checked"}} /><br />\r\n  <small><span style="margin-left: 10px;">$phrases[settingsDisableFormattingBlurb]</span></small><br /><br />\r\n\r\n  <label for="disableVideo">$phrases[settingsDisableVideoLabel]</label> <input type="checkbox" name="disableVideo" id="disableVideo" value="true"{{if="$user[''settingsOfficialAjax''] & 32"}{ checked="checked"}} /><br />\r\n  <small><span style="margin-left: 10px;">$phrases[settingsDisableVideoBlurb]</span></small><br /><br />\r\n\r\n  <label for="disableImage">$phrases[settingsDisableImageLabel]</label> <input type="checkbox" name="disableImage" id="disableImage" value="true"{{if="$user[''settingsOfficialAjax''] & 64"}{ checked="checked"}} /><br />\r\n  <small><span style="margin-left: 10px;">$phrases[settingsDisableImageBlurb]</span></small><br /><br />\r\n  </div>\r\n\r\n  <div id="settings3">\r\n  <label for="mature">$phrases[settingsParentalControlsLabel]</label> <input type="checkbox" name="mature" id="mature" value="true"{{if="$user[''settings''] & 64"}{ checked="checked"}} /><br />\r\n  <small><span style="margin-left: 10px;">$phrases[settingsParentalControlsBlurb]</span></small><br /><br />\r\n\r\n  <label for="defaultRoom">$phrases[settingsDefaultRoomLabel]</label> <input type="text" name="defaultRoom" id="defaultRoom" /><br />\r\n  <small><span style="margin-left: 10px;">$phrases[settingsDefaultRoomBlurb]</span></small><br /><br />\r\n\r\n  <label for="watchRooms">$phrases[settingsWatchRoomsLabel]</label> <input type="text" name="watchRoomBridge" id="watchRoomBridge" /><input type="button" value="Add" onclick="addRoom();" /><br />\r\n  <small><span style="margin-left: 10px;">$phrases[settingsWatchRoomsBlurb]</span></small><br />\r\n  <small><span style="margin-left: 10px;">$phrases[settingsWatchRoomsCurrentRooms]<span id="watchRoomsList">$watchList</span></span></small><br /><br />\r\n\r\n  <input type="hidden" name="watchRooms" id="watchRooms" value="$user[watchRooms]" />\r\n  </div>\r\n\r\n  <div id="settings2">\r\n  {{if="$enableDF"}{\r\n  Default Formatting:<br />\r\n\r\n  {{if="$enableDF[''font'']"}{\r\n  <select name="defaultFace" id="defaultFace" onchange="var fontFace = $(''#defaultFace option:selected'').attr(''data-font''); $(''#fontPreview'').css(''font-family'',fontFace);" style="margin-left: 10px;">$fontBox</select>}}\r\n\r\n  {{if="$enableDF[''colour'']"}{<input style="width: 40px;" id="defaultColour" name="defaultColour" />}}\r\n\r\n  {{if="$enableDF[''highlight'']"}{<input style="width: 40px;" id="defaultHighlight" name="defaultHighlight" />}}\r\n\r\n  {{if="$enableDF[''general'']"}{\r\n  <label for="defaultBold">Bold</label><input type="checkbox" name="defaultBold" id="defaultBold" onchange="if ($(this).is('':checked'')) { $(''#fontPreview'').css(''font-weight'',''bold''); } else { $(''#fontPreview'').css(''font-weight'',''normal''); }" value="true"{{if="$user[''defaultFormatting''] & 256"}{ checked="checked"}} />\r\n\r\n  <label for="defaultItalics">Italics</label><input type="checkbox" name="defaultItalics" id="defaultItalics" value="true"{{if="$user[''defaultFormatting''] & 512"}{ checked="checked"}} onchange="if ($(this).is('':checked'')) { $(''#fontPreview'').css(''font-style'',''italic''); } else { $(''#fontPreview'').css(''font-style'',''normal''); }" /><br />}}\r\n\r\n  <small><span style="margin-left: 10px;" id="fontPreview">$phrases[settingsDefaultFormattingPreview]</span></small><br /><br />}}\r\n  </div>\r\n\r\n  <button type="submit">Save Settings</button><button type="reset">Reset</button>\r\n</form>'),



(24, 'privateRoomForm', '', '<script type="text/javascript" src="client/js/fim-private.js"></script>\r\n\r\n<form action="index.php?action=privateRoom&phase=2" method="post" id="privateRoomForm">\r\n\r\n  <label for="userName">Username</label>: <input type="text" name="userName" id="userName" /><br />\r\n  <small><span style="margin-left: 10px;">The other user you would like to talk to.</span></small><br /><br />\r\n\r\n  <input type="submit" value="Go" />\r\n\r\n</form>'),
(25, 'unkickForm', '', '<form action="./index.php?action=unkick&phase=2" method="post">\r\n  <label for="userId">User ID</label>: <input type="text" name="userId" id="userId" value="$userId" style="width: 50px;" /><br />\r\n  <label for="roomId">Room ID</label>: <input type="text" name="roomId" id="roomId" value="$roomId" style="width: 50px;" /><br />\r\n\r\n  <input type="submit" value="Unkick User" /><input type="reset" value="Reset" />\r\n</form>'),
(26, 'chatMatureWarning', '', '{{container}{$phrases[chatMatureTitle]}{$phrases[chatMatureMessage]}}'),
(27, 'chatInnerTemplate', 'parseFlags,canModerate,chatTemplate', '<script src="client/js/fim-chat.js" type="text/javascript"></script>\r\n\r\n<div id="roomTemplateContainer">\r\n$chatTemplate\r\n</div>\r\n\r\n<div id="dialogues">\r\n  <div id="textentryBox">\r\n    <div id="textentryBoxUpload">\r\n      <form action="uploadFile.php?room=$room[id]" method="post" enctype="multipart/form-data" target="upload_target" id="uploadFileForm" onsubmit="$(''#textentryBoxUpload'').dialog(''close'');">\r\n        <fieldset>\r\n          <legend>Upload from Computer</legend>\r\n          <label for="fileUpload">File: </label>\r\n          <input name="fileUpload" id="fileUpload" type="file" onChange="upFiles()" /><br /><br />\r\n        </fieldset>\r\n        <fieldset>\r\n          <legend>Embed from Internet</legend>\r\n          <label for="urlUpload">URL: </label>\r\n          <input name="urlUpload" id="urlUpload" type="url" value="http://" onchange="previewUrl()" /></span><br />\r\n        </fieldset>\r\n        <fieldset>\r\n          <legend>Preview & Submit</legend>\r\n          <div id="preview"></div><br /><br />\r\n\r\n          <button onclick="$(''#textentryBoxUpload'').dialog(''close'');" type="button">Cancel</button>\r\n          <button type="submit" id="imageUploadSubmitButton">Upload</button>\r\n        </fieldset>\r\n        <iframe id="upload_target" name="upload_target" class="nodisplay"></iframe>\r\n        <input type="hidden" name="method" value="image" />\r\n      </form>\r\n    </div>\r\n\r\n    <div id="textentryBoxUrl">\r\n      <form action="/uploadFile.php?room=$room[id]" method="post" target="upload_target3" id="linkForm" onsubmit="$(''#textentryBoxUrl'').dialog(''close'');">\r\n        <fieldset>\r\n          <legend>Normal Link</legend>\r\n          <label for="linkUrl">URL: </label>\r\n          <input name="linkUrl" id="linkUrl" type="url" /><br /><br />\r\n          {{if="$parseFlags"}{\r\n          <label for="linkText">Text: </label>\r\n          <input name="linkText" id="linkText" type="text" /><br /><br />}}\r\n        </fieldset>\r\n\r\n        <fieldset>\r\n          <legend>eMail Link</legend>\r\n          <label for="linkEmail">eMail: </label>\r\n          <input name="linkEmail" id="linkEmail" type="email" /></span><br />\r\n        </fieldset>\r\n        <fieldset>\r\n          <legend>Preview & Submit</legend>\r\n\r\n          <button onclick="$(''#textentryBoxUrl'').dialog(''close'');" type="button">Cancel</button>\r\n          <button type="submit" id="linkSubmitButton">Link</button>\r\n        </fieldset>\r\n\r\n        <iframe id="upload_target3" name="upload_target3" class="nodisplay"></iframe>\r\n        <input type="hidden" name="method" value="url" />\r\n      </form>\r\n    </div>\r\n\r\n    <div id="textentryBoxYoutube">\r\n      <fieldset>\r\n        <legend>Direct Link</legend>\r\n        <form action="/uploadFile.php?room=$room[id]" method="post" enctype="multipart/form-data" target="upload_target2" id="uploadYoutubeForm" onsubmit="$(''#textentryBoxYoutube'').dialog(''close'');">\r\n          <label for="youtubeUpload">URL: </label>\r\n          <input name="youtubeUpload" id="youtubeUpload" type="url" value="http://" /><br />\r\n          <button onclick="$(''#textentryBoxYoutube'').dialog(''close'');" type="button">Cancel</button>\r\n          <button type="submit">Upload</button>\r\n          <iframe id="upload_target2" name="upload_target2" class="nodisplay"></iframe>\r\n          <input type="hidden" name="method" value="youtube" />\r\n        </form>\r\n      </fieldset>\r\n      <fieldset>\r\n        <legend>Search for Videos</legend>\r\n        <form action="#" onsubmit="return false;">\r\n          <input type="text" onkeyup="updateVids(this.value);" />\r\n          <div id="youtubeResultsContainer">\r\n            <table id="youtubeResults">\r\n              <tr>\r\n                <td>Results will appear here...</td>\r\n              </tr>\r\n            </table>\r\n          </div>\r\n        </form>\r\n      </fieldset>\r\n    </div>\r\n\r\n    <ul id="userMenu" class="contextMenu">\r\n      <li><a href="javascript:void(0);" data-action="private_im">Private IM</a></li>\r\n      <li><a href="javascript:void(0);" data-action="profile">View Profile</a></li>\r\n      {{if="$canModerate"}{<li><a href="javascript:void(0);" data-action="kick">Kick</a></li>}}\r\n      {{if="$user[''settings''] & 16"}{<li><a href="javascript:void(0);" data-action="ban">Ban</a></li>}}\r\n    </ul>\r\n\r\n    <ul id="messageMenu" class="contextMenu">\r\n      <li><a href="javascript:void(0);" data-action="link">Link To</a></li>\r\n      <li><a href="javascript:void(0);" data-action="delete">Delete</a></li>\r\n    </ul>\r\n\r\n    <ul id="messageMenuImage" class="contextMenu">\r\n      <li><a href="javascript:void(0);" data-action="url">Get URL</a></li>\r\n      <li><a href="javascript:void(0);" data-action="link">Link To</a></li>\r\n      <li><a href="javascript:void(0);" data-action="delete">Delete</a></li>\r\n    </ul>\r\n\r\n    <ul id="roomMenu" class="contextMenu">\r\n      <li><a href="javascript:void(0);" data-action="edit">Edit</a></li>\r\n      <li><a href="javascript:void(0);" data-action="delete">Delete</a></li>\r\n    </ul>\r\n\r\n    <div style="display: none;" id="kick">\r\n    <form action="/content/kick.php&phase=2" method="post" id="kickForm">\r\n      <label for="time">Time</label>: <input type="text" name="time" id="time" style="width: 50px;" />\r\n      <select name="interval">\r\n        <option value="1">Seconds</option>\r\n        <option value="60">Minutes</option>\r\n        <option value="3600">Hours</option>\r\n        <option value="86400">Days</option>\r\n        <option value="604800">Weeks</option>\r\n      </select><br /><br />\r\n\r\n      <button type="submit">Kick User</button><button type="reset">Reset</button><input type="hidden" name="room" value="$room[id]" />\r\n    </form>\r\n    </div>\r\n  </div>\r\n</div>');

-- DIVIDE

INSERT INTO `{prefix}templates` (`id`, `name`, `vars`, `data`) VALUES
(28, 'copyright', '', '<ul class="tabList">\r\n  <li><a href="#copyright1">Copyright</a></li>\r\n  <li><a href="#copyright2">Thanks</a></li>\r\n</ul>\r\n\r\n<div style="text-align: center;" id="copyright1">\r\nFreezeMessenger &copy; 2010-2011 Joseph T. Parsons. Some Rights Reserved.<br /><br />\r\n\r\njQuery, jQueryUI, and all jQueryUI Themeroller Themes &copy; The jQuery Project.<br /><br />\r\n\r\njGrowl &copy; 2009 Stan Lemon.<br />\r\njQuery Cookie Plugin &copy; 2006 Klaus Hartl<br />\r\nEZPZ Tooltip &copy; 2009 Mike Enriquez<br />\r\nBeeper &copy; 2009 Patrick Mueller<br />\r\nError Logger Utility &copy; Ben Alman<br />\r\nContext Menu &copy; 2008 Cory S.N. LaViska<br />\r\njQTubeUtil &copy; 2010 Nirvana Tikku\r\n</div>\r\n\r\n<div style="text-align: center;" id="copyright2">\r\nFor continual bugtesting and related help, thanks to:<br /><br />\r\n\r\nJC747<br />\r\nA''Bom<br />\r\nShade<br />\r\nNingamer<br />\r\nKingOfKYA<br />\r\nCat333Pokémon\r\n</div>'),
(29, 'help', 'bbcode,parseFlags,loginMethod', '<ul>\r\n<li><a href="#intro">Intro</a></li>\r\n<li><a href="#rules">Rules</a></li>\r\n<li><a href="#bbcode">BBCode</a></li>\r\n<li><a href="#age">Age</a></li>\r\n<li><a href="#browser">Reqs</a></li>\r\n<li><a href="#faqs">FAQs</a></li>\r\n<li><a href="#bugs">Bugs</a></li>\r\n</ul>\r\n\r\n<div id="help" style="height: 400px;">\r\n<div id="intro">\r\nFreezeMessenger 2.0 (FIM or FIM2) is an advanced AJAX-based online webmessenger and Instant Messenger substitute created to allow anybody to easily communicate with anybody else all across the web. It is highly sophisticated, supporting all modern browsers and utilizing various cutting-edge features in each. It was written from scratch by Joseph T. Parsons ("FreezeWarp") with PHP, MySQL, and other tricks along the way.\r\n</div>\r\n\r\n<div id="rules">In no part of the chat, whether it be in a public, private, official, or nonofficial room, are you allowed to:\r\n<ul>\r\n<li>Promote or inflict hatespeech.</li>\r\n<li>Post, link to, or encourage illegal material.</li>\r\n<li>Encourage or enable another member to do any of the above.</li>\r\n</ul></div>\r\n\r\n<div id="bbcode">The following tags are enabled for formatting:\r\n\r\n<ul>\r\n<li><em>+[text]+</em> - Bold a message.</li>\r\n<li><em>_[text]_</em> - Underline a message.</li>\r\n<li><em> /[text]/ </em> - Italicize a message.</li>\r\n<li><em>=[text]=</em> - Strikethrough a message.</li>\r\n<li><em>[b][text][/b]</em> - Bold a message.</li>\r\n<li><em>[u][text][/u]</em> - Underline a message.</li>\r\n<li><em>[i][text][/i]</em> - Italicize a message.</li>\r\n<li><em>[s][text][/s]</em> - Strikethrough a message.</li>\r\n<li><em>[url]http://example.com/[/url]</em> - Link a URL.</li>\r\n<li><em>[url=http://example.com/]Example[/url]</em> - Link a URL</li>\r\n<li><em>[img]http://example.com/image.png[/img]</em> - Link an Image.</li>\r\n<li><em>[img=":P"]http://example.com/image.png[/img]</em> - Link an image with alt text.</li>\r\n<li><em>[youtube]{youtubeCode}[/youtube]</em> - Include a Youtube video.</li>\r\n</ul></div>\r\n\r\n<div id="age">We take no responsibility for any harrassment, hate speach, or other issues users may encounter, however we will do our best to stop them if they are reported to proper administrative staff. Users will not be allowed to see mature rooms unless they have specified the "Disable Parental Controls" option in their user settings, and are encouraged to only talk to people privately whom they know.<br /><br />\r\n\r\nKeep in mind all content is heavily encrytped for privacy. Private conversations may only be viewed by server administration when neccessary, but can not be accessed by chat staff.</div>\r\n\r\n<div id="browser">FIM''s WebInterface will work with any of the following browsers:\r\n<ul>\r\n  <li><a href="http://www.google.com/chrome" target="_BLANK">Google Chrome / Chromium</a></li>\r\n  <li><a href="http://windows.microsoft.com/ie9" target="_BLANK">Internet Explorer 8+</a></li>\r\n  <li><a href="http://www.mozilla.com/en-US/firefox/" target="_BLANK">Firefox 3.6+</a></li>\r\n  <li><a href="http://www.opera.com/download/" target="_BLANK">Opera 11+</a></li>\r\n  <li><a href="http://www.apple.com/safari/" target="_BLANK">Safari 5+</a></li>\r\n</ul><br /><br /></div>\r\n\r\n<div id="faqs">\r\n<ul>\r\n  <li><b>Can I Change the Style?</b> - Not normally, no, though in the full mode users can use an experimental and incredibly buggy feature by visiting <a href="../?experimental=true">this location</a>. There, under the "Me" category you can use one of many different, but untested, themes. Be warned: these are not complete. Once a theme is set, this style switcher will always appear. To remove it and the theme, clear your browser''s cookies.</li>\r\n  <li><b>Where Do I Report Bugs?</b> - If possible, please PM FreezeWarp.</li>\r\n  <li><b>Can I Donate to this Awesome Project?</b> - <a href="javascript:alert(''Donations not yet set up. But, please, if you want to, they will be shortly.'');">Please do. It really helps keep development going.</a></li>\r\n</ul></div>\r\n\r\n<div id="bugs">Below is basic information useful for submitting bug reports:<br /><br />\r\n<em>User Agent</em>: $_SERVER[HTTP_USER_AGENT]<br />\r\n<em>Parse Flags</em>: {{if="$parseFlags"}{On}{Off}}<br />\r\n<em>Login Method</em>: $loginMethod<br />\r\n</div>');