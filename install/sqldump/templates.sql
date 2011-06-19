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

CREATE TABLE IF NOT EXISTS `{prefix}templates` (
  `id` int(10) NOT NULL AUTO_INCREMENT,
  `name` varchar(50) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL,
  `vars` varchar(1000) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL,
  `data` longtext CHARACTER SET utf8 COLLATE utf8_bin NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE={engine} DEFAULT CHARSET=utf8;

-- DIVIDE

INSERT INTO `{prefix}templates` (`id`, `name`, `vars`, `data`) VALUES
(1, 'templateStart', 'allowRoomCreation,inRoom,allowPrivateRooms,bodyHook,layout,style,template', '<?xml version="1.0" encoding="UTF-8"?>
{{if="$phrases[''doctype'']"}{$phrases[doctype]}{<!DOCTYPE HTML>}}

<!-- Original Source Code Copyright © 2011 Joseph T. Parsons. -->
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en">
<head>
  <title>{{if="$phrases[''brandingTitle'']"}{$phrases[brandingTitle]}{FreezeMessenger}} - $title</title>

  <meta http-equiv="Content-type" value="text/html; charset=utf-8" />
  <meta name="robots" content="index, follow" />
  <meta name="author" content="Joseph T. Parsons" />
  <meta name="keywords" content="{{if="$phrases[brandingKeywords]"}{$phrases[brandingKeywords]}{instant messenger, im, instant message}}" />
  <meta name="description" content="{{if="$phrases[brandingDescription]"}{$phrases[brandingDescription]}{FreezeMessenger-powered chat program.}}" />
  {{if="$phrases[brandingFavicon]"}{<link rel="icon" id="favicon" href="$phrases[brandingFavicon]" />}}



  <!-- START Local Variables -->

  <meta name="fim_userId" content="$user[userId]" />
  <meta name="fim_roomId" content="$room[roomId]" />
  <meta name="fim_audioDing" content="{{if="$user[''optionDefs''][''audioDing'']"}{1}{0}}" />
  <meta name="fim_reversePostOrder" content="{{if="$user[''optionDefs''][''reversePostOrder'']"}{1}{0}}" />
  <meta name="fim_showAvatars" content="{{if="$user[''optionDefs''][''showAvatars'']"}{1}{0}}" />
  <meta name="fim_longPolling" content="{{if="$user[''optionDefs''][''longPolling'']"}{1}{0}}" />
  <meta name="fim_layout" content="$layout" />

  <!-- END Local Variables -->



  <!-- START Styles -->

  <link rel="stylesheet" type="text/css" href="client/css/$style/jquery-ui-1.8.13.custom.css" media="screen" />
  <link rel="stylesheet" type="text/css" href="client/css/$style/fim.css" media="screen" />
  <link rel="stylesheet" type="text/css" href="client/css/styles060911.min.css" media="screen" />

  {{if="$layout == ''alt''"}{<style>
    #menu { display: none; width: 0px; }
    #messageListContainer { float: right; width: 50%; }
    #textentryBoxMessage { float: left; width: 50%; }
    #content { width: 100%; }</style>}}

  <!-- END Styles -->



  <!-- START Scripts -->

  <script src="client/js/jquery-1.6.1.min.js" type="text/javascript"></script>
  <script src="client/js/jquery-ui-1.8.13.custom.min.js" type="text/javascript"></script>
  <script src="client/js/jquery.plugins.js" type="text/javascript"></script>
  <script src="client/js/fim-all.js" type="text/javascript"></script>

  <!-- END Scripts -->


  {$phrases[hookHead]}
</head>

<body>
<div id="tooltext" class="tooltip-content"></div>
<div id="page" data-role="page">
  $phrases[hookPageStartAll]
  $phrases[hookPageStartFull]
  <!-- START links -->
  <div id="menu" data-role="header">
    <h3><a href="#">$phrases[templateQuickCat]</a></h3>
    <div>
    <ul>
      <li style="border-bottom: 1px solid;"><a href="#" id="messageArchive">$phrases[templateArchive]</a></li>
      <li><a href="#" id="roomList">$phrases[templateRoomList]</a></li>
      <li><a href="#" id="createRoom">$phrases[templateCreateRoom]</a></li>
      <li style="border-bottom: 1px solid;"><a href="#" id="privateRoom">$phrases[templatePrivateIM]</a></li>

      <li><a href="#" id="online">$phrases[templateActiveUsers]</a></li>
      <li style="border-bottom: 1px solid;"><a href="#" id="viewStats">$phrases[templateStats]</a></li>

      <li><a href="#" id="changeSettings">$phrases[templateChangeSettings]</a></li>
      <li><a href="#" id="logout">$phrases[templateLogout]</a></li>
      <li><a href="#" id="login">$phrases[templateLogin]</a></li>
    </ul>
    </div>

    <h3><a href="#">$phrases[templateModerateCat]</a></h3>
    <div>
    <ul>
      <li><a href="#" id="editRoom">$phrases[templateEditRoom]</a></li>
      <li><a href="#" id="manageKick">$phrases[templateManageKickedUsers]</a></li>
      <li><a href="#" id="kick">$phrases[templateKickUser]</a></li>

      <li><a href="./moderate.php">$phrases[templateAdmin]</a></li>
      <ul>
        <li><a href="./moderate.php?do=showimages">$phrases[templateAdminImages]</a></li>
        <li><a href="./moderate.php?do=listusers">$phrases[templateAdminUsers]</a></li>
        <ul>
          <li><a href="./moderate.php?do=banuser">$phrases[templateAdminBanUser]</a></li>
          <li><a href="./moderate.php?do=unbanuser">$phrases[templateAdminUnbanUser]</a></li>
        </ul>
        <li><a href="./moderate.php?do=censor">$phrases[templateAdminCensor]</a></li>
        <li><a href="./moderate.php?do=phrases">$phrases[templateAdminPhrases]</a></li>
        <li><a href="./moderate.php?do=hooks">$phrases[templateAdminHooks]</a></li>
        <li><a href="./moderate.php?do=templates">$phrases[templateAdminTemplates]</a></li>
        <li><a href="./moderate.php?do=maintenance">$phrases[templateAdminMaintenance]</a></li>
      </ul>
    </ul>
    </div>

    <h3><a href="#">$phrases[templateRoomListCat]</a></h3>
    <div>
      <div id="roomListShort">
        <ul>
          <li><a href="javascript:void(0);" id="showMoreRooms">$phrases[templateShowMoreRooms]</a></li>
        </ul>
      </div>
      <div id="roomListLong" style="display: none;">
        <ul>
          <li><a href="javascript:void(0);" id="showFewerRooms">$phrases[templateShowLessRooms]</a></li>
        </ul>
      </div>
    </div>

    <h3><a href="#">$phrases[templateActiveUsersCat]</a></h3>
    <div id="activeUsers">$phrases[templateLoading]</div>

    <h3><a href="#">$phrases[templateCopyrightCat]</a></h3>
    <div>
      <ul>
        <li>FIM © 2010-2011 Joseph Parsons<br /></li>
        <li>jQuery Plugins © Their Respective Owners.</li>
        <li><a href="#" id="copyrightLink">$phrases[templateAllCopyrights]</a></li>
      </ul>
    </div>

  </div>
  <!-- END links -->

  <div id="content" data-role="content">
  <!-- START content -->
    $phrases[hookContentStartAll]
    $phrases[hookContentStartFull]'),
(2, 'templateEnd', '', '
    $phrases[hookContentEndAll]
    $phrases[hookContentEndFull]
    <!-- END content -->
  </div>

$phrases[hookBodyEndAll]
$phrases[hookBodyEndFull]
</body>
</html>'),
(5, 'login', '', '<div id="normalLogin">
  <br />

    <form action="#" method="post" id="loginForm" name="loginForm" style="text-align: center; display: block;">
    <label for="userName">$phrases[loginUsername]</label><br />
    <input type="text" name="userName" id="userName" /><br /><br />

      <label for="password">$phrases[loginPassword]</label><br />
    <input type="password" name="password" id="password" /><br /><br />

      <label for="rememberme">$phrases[loginRemember]</label>
    <input type="checkbox" name="rememberme" id="rememberme" /><br /><br />

      <button type="submit">$phrases[loginSubmit]</button><button type="reset">$phrases[loginReset]</button></form>
</div>'),
(7, 'container', 'title,content,class', '<table class="$class ui-widget">
  <thead>
    <tr class="hrow ui-widget-header ui-corner-top">
      <td>$title</td>
    </tr>
  </thead>
  <tbody class="ui-widget-content ui-corner-bottom">
    <tr>
      <td>
        <div>$content</div>
      </td>
    </tr>
  </tbody>
</table>');

-- DIVIDE

INSERT INTO `{prefix}templates` (`id`, `name`, `vars`, `data`) VALUES
(12, 'chatStopMessage', 'stopMessage', '<div id="stopMessage">
    {{container}{Warning}{$stopMessage<br /><br />

<form action="#" method="post">
  <input type="button" onclick="$(''#stopMessage'').slideUp(); $(''#chatContainer'').slideDown();" value="Continue." />
  <input type="button" onclick="window.history.back()" value="Go Back" />
</form>}}
    </div>'),
(16, 'createRoomSuccess', 'installUrl,roomId', '$phrases[createRoomCreatedAt]<br /><br />

<form action="{$installUrl}index.php?room={$insertId}" method="post">
  <input type="text" style="width: 300px;" value="{$installUrl}index.php?room={$insertId}" name="url" />
  <input type="submit" value="$phrases[editRoomCreatedGo]" />
</form>'),
(17, 'editRoomForm', 'censorLists', '<ul class="tabList">
  <li><a href="#editRoom1">General</a></li>
  <li><a href="#editRoom2">Permissions</a></li>
</ul><form action="#" method="post" id="editRoomForm"><div id="editRoom1">
  <label for="name">$phrases[editRoomNameLabel]</label>: <input type="text" name="name" id="name" value="$room[name]" /><br />
  <small><span style="margin-left: 10px;">$phrases[editRoomNameBlurb]</span></small><br /><br />

  <label for="mature">$phrases[editRoomMatureLabel]</label>: <input type="checkbox" name="mature" id="mature" /><br />
  <small><span style="margin-left: 10px;">$phrases[editRoomMatureBlurb]</strong></small><br /><br />

  <label for="bbcode">$phrases[editRoomBBCode]</label>: <select name="bbcode">
    <option value="1" selected="selected">$phrases[editRoomBBCodeAll]</option>
    <option value="5">$phrases[editRoomBBCodeNoMulti]</option>
    <option value="9">$phrases[editRoomBBCodeNoImg]</option>
    <option value="13">$phrases[editRoomBBCodeLink]</option>
    <option value="16">$phrases[editRoomBBCodeBasic]</option>
    <option>$phrases[editRoomBBCodeNothing]</option>
  </select><br />

  <small style="margin-left: 10px;">$phrases[editRoomBBCodeBlurb]</small><br /><br />

  <label>$phrases[editRoomCensorLabel]</label>:<br /><div style="margin-left: 10px;">{$censorLists}</div><br />

  <button type="submit">{{if="$_GET[''action''] == ''create''"}{$phrases[createRoomSubmit]}{$phrases[editRoomSubmit]}}</button><button type="reset">$phrases[editRoomReset]</button></div><div id="editRoom2"><label for="allowedUsers">$phrases[editRoomAllowedUsersLabel]</label> <input type="text" name="allowedUsersBridge" id="allowedUsersBridge" /><input type="button" value="Add" onclick="addEntry(''allowedUsers'');" /><input type="hidden" name="allowedUsers" id="allowedUsers" /><br />
  <small><span style="margin-left: 10px;">$phrases[editRoomAllowedUsersCurrent]<span id="allowedUsersList"></span></span></small><br /><br />

  <label for="allowedGroups">$phrases[editRoomAllowedGroupsLabel]</label> <input type="text" name="allowedGroupsBridge" id="allowedGroupsBridge" /><input type="button" value="Add" onclick="addEntry(''allowedGroups'');" /><input type="hidden" name="allowedGroups" id="allowedGroups" /><br />
  <small><span style="margin-left: 10px;">$phrases[editRoomAllowedGroupsCurrent]<span id="allowedGroupsList"></span></span></small><br /><br />

  <label for="moderators">$phrases[editRoomModeratorsLabel]</label> <input type="text" name="moderatorsBridge" id="moderatorsBridge" /><input type="button" value="Add" onclick="addEntry(''moderators'');" /><input type="hidden" name="moderators" id="moderators" /><br />
  <small><span style="margin-left: 10px;">$phrases[editRoomModeratorsCurrent]<span id="moderatorsList"></span></span></small><br /><br />

  <button type="submit">{{if="$_GET[''action''] == ''create''"}{$phrases[createRoomSubmit]}{$phrases[editRoomSubmit]}}</button><button type="reset">$phrases[editRoomReset]</button></div>
</form>'),
(18, 'editRoomSuccess', 'roomId', 'Your group was successfully edited.<br /><br />

<button onclick="window.location=''index.php?room=$roomId">Go To It</button>');

-- DIVIDE

INSERT INTO `{prefix}templates` (`id`, `name`, `vars`, `data`) VALUES
(23, 'userSettingsForm', 'enableDF', '<link rel="stylesheet" media="screen" type="text/css" href="client/colorpicker/css/colorpicker.css" />
<script type="text/javascript" src="client/colorpicker/js/colorpicker.js"></script>

<ul class="tabList">
  <li><a href="#settings1">Chat Display</a></li>
  {{if="$enableDF"}{
  <li><a href="#settings2">Message Formatting</span></a></li>}}
  <li><a href="#settings3">General</a></li>
</ul>

<form action="#" method="post" id="changeSettingsForm">
  <div id="settings1">
  <label for="theme">Theme:</label> <select name="settingsOfficialAjax_theme">
    <option value="1">jQueryUI Darkness</option>
    <option value="2">jQueryUI Lightness</option>
    <option value="3">High Contrast Blue</option>
    <option value="4">Cupertino</option>
    <option value="5">Darkhive</option>
    <option value="6">Start</option>
    <option value="7">Vader</option>
    <option value="8">Trontastic</option>
    <option value="9">Humanity</option>
  </select><br />
  <small><span style="margin-left: 10px;">Change the theming of the messenger to your liking. "Cupertino" and "jQueryUI" are good choices.</span></small><br /><br />

  <label for="avatars">Show Avatars</label> <input type="checkbox" name="settingsOfficialAjax_showAvatars" id="showAvatars" value="true"{{if="$user[''optionDefs''][''showAvatars'']"}{ checked="checked"}} /><br />
  <small><span style="margin-left: 10px;">Showing avatars will reduce the overall amount of text by hiding post times and userNames with a small avatar to represent users. Times can be viewed by moving the mouse over each message.</span></small><br /><br />

  <label for="reverse">$phrases[settingsReversePostOrderLabel]</label> <input type="checkbox" name="settingsOfficialAjax_reversePostOrder" id="reversePostOrder" value="true"{{if="$user[''optionDefs''][''reversePostOrder'']"}{ checked="checked"}} /><br />
  <small><span style="margin-left: 10px;">$phrases[settingsReversePostOrderBlurb]</span></small><br /><br />

  <label for="settingsOfficialAjax_audioDing">$phrases[settingsAudioDingLabel]</label> <input type="checkbox" name="settingsOfficialAjax_audioDing" id="audioDing" value="true"{{if="$user[''optionDefs''][''audioDing'']"}{ checked="checked"}} /><br />
  <small><span style="margin-left: 10px;">$phrases[settingsDisableDingBlurb]</span></small><br /><br />

  <label for="disableFormatting">$phrases[settingsDisableFormattingLabel]</label> <input type="checkbox" name="settingsOfficialAjax_disableFormatting" id="disableFormatting" value="true"{{if="$user[''optionDefs''][''disableFormatting'']"}{ checked="checked"}} /><br />
  <small><span style="margin-left: 10px;">$phrases[settingsDisableFormattingBlurb]</span></small><br /><br />

  <label for="disableVideo">$phrases[settingsDisableVideoLabel]</label> <input type="checkbox" name="settingsOfficialAjax_disableVideo" id="disableVideo" value="true"{{if="$user[''optionDefs''][''disableVideo'']"}{ checked="checked"}} /><br />
  <small><span style="margin-left: 10px;">$phrases[settingsDisableVideoBlurb]</span></small><br /><br />

  <label for="disableImage">$phrases[settingsDisableImageLabel]</label> <input type="checkbox" name="settingsOfficialAjax_disableImage" id="disableImage" value="true"{{if="$user[''optionDefs''][''disableImages'']"}{ checked="checked"}} /><br />
  <small><span style="margin-left: 10px;">$phrases[settingsDisableImageBlurb]</span></small><br /><br />
  </div>

  <div id="settings3">
  <label for="mature">$phrases[settingsParentalControlsLabel]</label> <input type="checkbox" name="mature" id="mature" value="true"{{if="$user[''settings''] & 64"}{ checked="checked"}} /><br />
  <small><span style="margin-left: 10px;">$phrases[settingsParentalControlsBlurb]</span></small><br /><br />

  <label for="defaultRoom">$phrases[settingsDefaultRoomLabel]</label> <input type="text" name="defaultRoom" id="defaultRoom" /><br />
  <small><span style="margin-left: 10px;">$phrases[settingsDefaultRoomBlurb]</span></small><br /><br />

  <label for="watchRooms">$phrases[settingsWatchRoomsLabel]</label> <input type="text" name="watchRoomsBridge" id="watchRoomsBridge" /><input type="button" value="Add" onclick="addEntry(''watchRooms'');" /><br />
  <small><span style="margin-left: 10px;">$phrases[settingsWatchRoomsBlurb]</span></small><br />
  <small><span style="margin-left: 10px;">$phrases[settingsWatchRoomsCurrentRooms]<span id="watchRoomsList"></span></span></small><br /><br />

  <input type="hidden" name="watchRooms" id="watchRooms" value="$user[watchRooms]" />
  </div>

  <div id="settings2">
  {{if="$enableDF"}{
  Default Formatting:<br />

  {{if="$enableDF[''font'']"}{
  <select name="defaultFace" id="defaultFace" onchange="var fontFace = $(''#defaultFace option:selected'').attr(''data-font''); $(''#fontPreview'').css(''font-family'',fontFace);" style="margin-left: 10px;">$fontBox</select>}}

  {{if="$enableDF[''colour'']"}{<input style="width: 40px;" id="defaultColour" name="defaultColour" />}}

  {{if="$enableDF[''highlight'']"}{<input style="width: 40px;" id="defaultHighlight" name="defaultHighlight" />}}

  {{if="$enableDF[''general'']"}{
  <label for="defaultBold">Bold</label><input type="checkbox" name="defaultBold" id="defaultBold" onchange="if ($(this).is('':checked'')) { $(''#fontPreview'').css(''font-weight'',''bold''); } else { $(''#fontPreview'').css(*{-webkit-box-sizing:border-box;-moz-box-sizing:border-box;-ms-box-sizing:border-box;-o-box-sizing:border-box;box-sizing:border-box;} body{overflow-x:hidden;font-family:Ubuntu, Calibria, Arial;margin:3px 5px 20px;padding:0;} @font-face{font-family:Ubuntu;font-style:normal;font-weight:400;src:local(Ubuntu), url(http://themes.googleusercontent.com/font?kit=_xyN3apAT_yRRDeqB3sPRg) format(woff);} #menu{float:left;width:25%;} #menu div,#menu ul{width:100%;} #rooms{max-height:300px;overflow:auto;} #content{float:right;width:75%;} #buttonMenuLeft{float:left;width:36px;display:block;} #messageInput{display:block;height:100px;float:right;padding:3px;} #messageInputContainer{white-space:nowrap;padding-left:3px;} #youtubeResultsContainer{height:320px;overflow:auto;} #textentryBoxUpload,#textentryBoxYoutube,#textentryBoxUrl{display:none;text-align:center;} #fileUpload,#urlUpload{width:300px;} #activeUsers{min-height:20px;max-height:250px;overflow-x:hidden;overflow-y:auto;} #rightTitle{width:30%;min-width:140px;} #messageList{height:440px;overflow:auto;} .leftPart{float:left;text-align:left;} .rightPart{float:right;text-align:right;} .userName{font-weight:700;} .userNameTable{width:150px;overflow:hidden;display:inline-block;word-wrap:normal;white-space:nowrap;} .inline{display:inline;} .inlineBlock{display:inline-block;} .hidden{display:none;} .embedImage{max-width:300px;max-height:300px;border:none;} a img:hover{opacity:.5;} img.standard{height:16px;width:16px;} button.standard{height:32px;width:32px;padding:7px!important;} select.standard{height:28px;} iframe.nodisplay{width:0;height:0;border:0;display:none;} table.rowHover tr:hover{opacity:.8;} table.page{border-spacing:0;width:100%;padding:5px;} .messageLine{display:block;margin:4px 0;} h2,h3,h4{margin:0;} .ui-button-icon-only .ui-icon,.ui-button-text-icon-primary .ui-icon,.ui-button-text-icon-secondary .ui-icon,.ui-button-text-icons .ui-icon,.ui-button-icons-only .ui-icon{top:0!important;left:0!important;position:relative;margin:0 auto!important;} table.leftright tr td:last-child{text-align:right;} table.leftright tr td[colspan=2]{text-align:center;} table.center{margin-left:auto;margin-right:auto;display:table;} .contextMenu{position:absolute;width:120px;z-index:99999;display:none;margin:0;padding:0;} .contextMenu li{list-style:none;margin:0;padding:0 0 3px;} .contextMenu a{text-decoration:none;display:block;line-height:20px;height:20px;border:none;padding-left:10px;} .contextMenu li.disabled a{cursor:default;} .frameBody{font-family:sans-serif;font-size:12px;width:100%;height:100%;margin:0;} .frameBody p{border:1px #bbb solid;padding:2px;} .rte-zone{width:500px;border:1px #999 solid;clear:both;height:200px;display:block;margin:0;padding:0;} div.jGrowl{z-index:9999;color:#fff;font-size:12px;position:absolute;padding:10px;} body > div.jGrowl{position:fixed;} div.jGrowl.top-left{left:0;top:0;} div.jGrowl.top-right{right:0;top:0;} div.jGrowl.bottom-left{left:0;bottom:0;} div.jGrowl.bottom-right{right:0;bottom:0;} div.jGrowl.center{top:0;width:50%;left:25%;} div.center div.jGrowl-notification,div.center div.jGrowl-closer{margin-left:auto;margin-right:auto;} div.jGrowl div.jGrowl-notification,div.jGrowl div.jGrowl-closer{background-color:#000;opacity:.85;-ms-filter:progid:DXImageTransform.Microsoft.Alpha(Opacity=85);filter:progid:DXImageTransform.Microsoft.Alpha(Opacity=85);zoom:1;width:235px;margin-top:5px;margin-bottom:5px;font-family:Tahoma, Arial, Helvetica, sans-serif;font-size:1em;text-align:left;display:none;-moz-border-radius:5px;-webkit-border-radius:5px;padding:10px;} div.jGrowl div.jGrowl-notification{min-height:40px;} div.jGrowl div.jGrowl-notification div.jGrowl-header{font-weight:700;font-size:.85em;} div.jGrowl div.jGrowl-notification div.jGrowl-close{z-index:99;float:right;font-weight:700;font-size:1em;cursor:pointer;} div.jGrowl div.jGrowl-closer{padding-top:4px;padding-bottom:4px;cursor:pointer;font-size:.9em;font-weight:700;text-align:center;} .tooltip-content{display:none;position:absolute;z-index:3000;padding:10px;} #tiptip_holder{display:none;position:absolute;top:0;left:0;z-index:99999;} #tiptip_holder.tip_top{padding-bottom:5px;} #tiptip_holder.tip_bottom{padding-top:5px;} #tiptip_holder.tip_right{padding-left:5px;} #tiptip_holder.tip_left{padding-right:5px;} #tiptip_arrow,#tiptip_arrow_inner{position:absolute;height:0;width:0;border-color:transparent;border-style:solid;border-width:6px;} #tiptip_holder.tip_top #tiptip_arrow_inner{margin-top:-7px;margin-left:-6px;} #tiptip_holder.tip_bottom #tiptip_arrow_inner{margin-top:-5px;margin-left:-6px;} #tiptip_holder.tip_right #tiptip_arrow_inner{margin-top:-6px;margin-left:-5px;} #tiptip_holder.tip_left #tiptip_arrow_inner{margin-top:-6px;margin-left:-7px;} #title,table.leftright tr td:first-child{text-align:left;} @media print { div.jGrowl{display:none;} }''font-weight'',''normal''); }" value="true"{{if="$user[''defaultFormatting''] & 256"}{ checked="checked"}} />

  <label for="defaultItalics">Italics</label><input type="checkbox" name="defaultItalics" id="defaultItalics" value="true"{{if="$user[''defaultFormatting''] & 512"}{ checked="checked"}} onchange="if ($(this).is('':checked'')) { $(''#fontPreview'').css(''font-style'',''italic''); } else { $(''#fontPreview'').css(''font-style'',''normal''); }" /><br />}}

  <small><span style="margin-left: 10px;" id="fontPreview">$phrases[settingsDefaultFormattingPreview]</span></small><br /><br />}}
  </div>

  <button type="submit">Save Settings</button><button type="reset">Reset</button>
<input type="hidden" name="settingsOfficialAjax" value="true" /></form>'),
(25, 'unkickForm', '', '<form action="#" method="post">
  <label for="userId">User ID</label>: <input type="text" name="userId" id="userId" value="$userId" style="width: 50px;" /><br />
  <label for="roomId">Room ID</label>: <input type="text" name="roomId" id="roomId" value="$roomId" style="width: 50px;" /><br />

  <input type="submit" value="Unkick User" /><input type="reset" value="Reset" />
</form>'),
(26, 'chatMatureWarning', '', '{{container}{$phrases[chatMatureTitle]}{$phrases[chatMatureMessage]}}'),
(27, 'chatTemplate', 'parseFlags,canModerate,chatTemplate,textboxStyle,stopMessage', '<div id="roomTemplateContainer">
  <div id="chatContainer"{{if="$stopMessage"}{style="display: none;"}}>
    {{container}{<div id="title">
      <span id="status" class="leftPart">
        {{if="$room[''options''] & 1"}{<img src="images/bookmarks.png" class="standard" title="This is an Official Room" alt="Official" />}}<br />
        <span id="refreshStatus" onclick="alert(''Failed '' + totalFails + '' times. Current refreshing every '' + (timeout / 1000 + .1) + '' seconds.'');"></span>
      </span>

      <div id="rightTitle" class="rightPart">
        <form action="#" onsubmit="return false;" class="rightPart">
          <button type="button" class="standard" id="icon_settings"></button>

          <button type="button" class="standard" id="icon_note"></button>

          <button type="button" class="standard" id="icon_help"></button>
        </form>
      </div>

      <span id="roomName">

      </span><br />

      <em id="topic"></em>
    </div>}{
    <div id="messageListContainer">
      <div id="messageList">
      </div>
    </div>}}
    <div id="textentryBoxMessage">
      <form id="sendForm" action="#" method="post">{{container}{<div class="leftPart">Enter a Message</div>
        <div class="rightPart">
          <button type="submit" class="standard" id="icon_submit"></button>
          <button type="reset" class="standard" id="icon_reset"></button>
        </div>}{
        <div id="messageInputContainer" class="middle">
          {{if="!$light"}{<div id="buttonMenuLeft">
            {{if="$room[''bbcode''] <= 13"}{<button type="button" onclick="$(''#textentryBoxUrl'').dialog({width : ''600px'', title : ''Insert a Linked Document''});" class="standard" id="icon_url"></button><br />}}
            {{if="$room[''bbcode''] <= 5"}{<button type="button" onclick="$(''#textentryBoxUpload'').dialog({width : ''600px'', title : ''Insert an Image''});" class="standard" id="icon_upload"></button><br />}}
            {{if="$room[''bbcode''] <= 2"}{<button type="button" onclick="$(''#textentryBoxYoutube'').dialog({width : ''600px'', title : ''Insert a Youtube Video''});" class="standard" id="icon_video"></button>}}
          </div>}}
          <textarea onkeypress="if (event.keyCode == 13 && !event.shiftKey) { $(''#sendForm'').trigger(''submit''); return false; }" id="messageInput" autofocus="autofocus" placeholder="Enter your text." style="$textboxStyle"></textarea>
        </div>}}
      </form>
  </div>
</div>

<div id="dialogues">
  <div id="textentryBox">
    <div id="textentryBoxUpload">
      <form method="post" enctype="multipart/form-data" target="upload_target" id="uploadFileForm" onsubmit="$(''#textentryBoxUpload'').dialog(''close'');">
        <fieldset>
          <legend>Upload from Computer</legend>
          <label for="fileUpload">File: </label>
          <input name="fileUpload" id="fileUpload" type="file" onChange="upFiles()" /><br /><br />
        </fieldset>
        <fieldset>
          <legend>Embed from Internet</legend>
          <label for="urlUpload">URL: </label>
          <input name="urlUpload" id="urlUpload" type="url" value="http://" onchange="previewUrl()" /></span><br />
        </fieldset>
        <fieldset>
          <legend>Preview & Submit</legend>
          <div id="preview"></div><br /><br />

          <button onclick="$(''#textentryBoxUpload'').dialog(''close'');" type="button">Cancel</button>
          <button type="submit" id="imageUploadSubmitButton">Upload</button>
        </fieldset>
        <iframe id="upload_target" name="upload_target" class="nodisplay"></iframe>
        <input type="hidden" name="method" value="image" />
      </form>
    </div>

    <div id="textentryBoxUrl">
      <form action="#" method="post" target="upload_target3" id="linkForm" onsubmit="$(''#textentryBoxUrl'').dialog(''close'');">
        <fieldset>
          <legend>Normal Link</legend>
          <label for="linkUrl">URL: </label>
          <input name="linkUrl" id="linkUrl" type="url" /><br /><br />
          {{if="$parseFlags"}{
          <label for="linkText">Text: </label>
          <input name="linkText" id="linkText" type="text" /><br /><br />}}
        </fieldset>

        <fieldset>
          <legend>eMail Link</legend>
          <label for="linkEmail">eMail: </label>
          <input name="linkEmail" id="linkEmail" type="email" /></span><br />
        </fieldset>
        <fieldset>
          <legend>Preview & Submit</legend>

          <button onclick="$(''#textentryBoxUrl'').dialog(''close'');" type="button">Cancel</button>
          <button type="submit" id="linkSubmitButton">Link</button>
        </fieldset>

        <iframe id="upload_target3" name="upload_target3" class="nodisplay"></iframe>
        <input type="hidden" name="method" value="url" />
      </form>
    </div>

    <div id="textentryBoxYoutube">
      <fieldset>
        <legend>Direct Link</legend>
        <form action="#" method="post" enctype="multipart/form-data" target="upload_target2" id="uploadYoutubeForm" onsubmit="$(''#textentryBoxYoutube'').dialog(''close'');">
          <label for="youtubeUpload">URL: </label>
          <input name="youtubeUpload" id="youtubeUpload" type="url" value="http://" /><br />
          <button onclick="$(''#textentryBoxYoutube'').dialog(''close'');" type="button">Cancel</button>
          <button type="submit">Upload</button>
          <iframe id="upload_target2" name="upload_target2" class="nodisplay"></iframe>
          <input type="hidden" name="method" value="youtube" />
        </form>
      </fieldset>
      <fieldset>
        <legend>Search for Videos</legend>
        <form action="#" onsubmit="return false;">
          <input type="text" onkeyup="updateVids(this.value);" />
          <div id="youtubeResultsContainer">
            <table id="youtubeResults">
              <tr>
                <td>Results will appear here...</td>
              </tr>
            </table>
          </div>
        </form>
      </fieldset>
    </div>
  </div>
</div>');

-- DIVIDE

INSERT INTO `{prefix}templates` (`id`, `name`, `vars`, `data`) VALUES
(28, 'copyright', '', '<ul class="tabList">
  <li><a href="#copyright1">Copyright</a></li>
  <li><a href="#copyright3">License</a></li>
  <li><a href="#copyright2">Thanks</a></li></ul>

<div style="text-align: center;" id="copyright1">
FreezeMessenger &copy; 2010-2011 Joseph T. Parsons. Some Rights Reserved.<br /><br />

jQuery, jQueryUI, and all jQueryUI Themeroller Themes &copy; The jQuery Project.<br /><br />

jGrowl &copy; 2009 Stan Lemon.<br />
jQuery Cookie Plugin &copy; 2006 Klaus Hartl<br />
EZPZ Tooltip &copy; 2009 Mike Enriquez<br />
Beeper &copy; 2009 Patrick Mueller<br />
Error Logger Utility &copy; Ben Alman<br />
Context Menu &copy; 2008 Cory S.N. LaViska<br />
jQTubeUtil &copy; 2010 Nirvana Tikku
</div>

<div style="text-align: center;" id="copyright2">
For continual bugtesting and related help, thanks to:<br /><br />

JC747<br />
A''Bom<br />
Shade<br />
Ningamer<br />
KingOfKYA<br />
Cat333Pokémon
</div><div id="copyright3">FreezeMessenger is free software: you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.<br /><br />This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU General Public License for more details.<br /><br />You should have received a copy of the GNU General Public License along with this program.  If not, see &lt;http://www.gnu.org/licenses/&gt;.</div>'),
(29, 'help', 'bbcode,parseFlags,loginMethod', '<ul>
<li><a href="#intro">Intro</a></li>
<li><a href="#rules">Rules</a></li>
<li><a href="#bbcode">BBCode</a></li>
<li><a href="#age">Age</a></li>
<li><a href="#browser">Reqs</a></li>
<li><a href="#faqs">FAQs</a></li>
<li><a href="#bugs">Bugs</a></li>
</ul>

<div id="help" style="height: 400px;">
<div id="intro">
FreezeMessenger 2.0 (FIM or FIM2) is an advanced AJAX-based online webmessenger and Instant Messenger substitute created to allow anybody to easily communicate with anybody else all across the web. It is highly sophisticated, supporting all modern browsers and utilizing various cutting-edge features in each. It was written from scratch by Joseph T. Parsons ("FreezeWarp") with PHP, MySQL, and other tricks along the way.
</div>

<div id="rules">In no part of the chat, whether it be in a public, private, official, or nonofficial room, are you allowed to:
<ul>
<li>Promote or inflict hatespeech.</li>
<li>Post, link to, or encourage illegal material.</li>
<li>Encourage or enable another member to do any of the above.</li>
</ul></div>

<div id="bbcode">The following tags are enabled for formatting:

<ul>
<li><em>+[text]+</em> - Bold a message.</li>
<li><em>_[text]_</em> - Underline a message.</li>
<li><em> /[text]/ </em> - Italicize a message.</li>
<li><em>=[text]=</em> - Strikethrough a message.</li>
<li><em>[b][text][/b]</em> - Bold a message.</li>
<li><em>[u][text][/u]</em> - Underline a message.</li>
<li><em>[i][text][/i]</em> - Italicize a message.</li>
<li><em>[s][text][/s]</em> - Strikethrough a message.</li>
<li><em>[url]http://example.com/[/url]</em> - Link a URL.</li>
<li><em>[url=http://example.com/]Example[/url]</em> - Link a URL</li>
<li><em>[img]http://example.com/image.png[/img]</em> - Link an Image.</li>
<li><em>[img=":P"]http://example.com/image.png[/img]</em> - Link an image with alt text.</li>
<li><em>[youtube]{youtubeCode}[/youtube]</em> - Include a Youtube video.</li>
</ul></div>

<div id="age">We take no responsibility for any harrassment, hate speach, or other issues users may encounter, however we will do our best to stop them if they are reported to proper administrative staff. Users will not be allowed to see mature rooms unless they have specified the "Disable Parental Controls" option in their user settings, and are encouraged to only talk to people privately whom they know.<br /><br />

Keep in mind all content is heavily encrytped for privacy. Private conversations may only be viewed by server administration when neccessary, but can not be accessed by chat staff.</div>

<div id="browser">FIM''s WebInterface will work with any of the following browsers:
<ul>
  <li><a href="http://www.google.com/chrome" target="_BLANK">Google Chrome / Chromium</a></li>
  <li><a href="http://windows.microsoft.com/ie9" target="_BLANK">Internet Explorer 8+</a></li>
  <li><a href="http://www.mozilla.com/en-US/firefox/" target="_BLANK">Firefox 3.6+</a></li>
  <li><a href="http://www.opera.com/download/" target="_BLANK">Opera 11+</a></li>
  <li><a href="http://www.apple.com/safari/" target="_BLANK">Safari 5+</a></li>
</ul><br /><br /></div>

<div id="faqs">
<ul>

  <li><b>Where Do I Report Bugs?</b> - If possible, please PM FreezeWarp.</li>
  <li><b>Can I Donate to this Awesome Project?</b> - <a href="javascript:alert(''Donations not yet set up. But, please, if you want to, they will be shortly.'');">Please do. It really helps keep development going.</a></li>
</ul></div>

<div id="bugs">Below is basic information useful for submitting bug reports:<br /><br />
<em>User Agent</em>: $_SERVER[HTTP_USER_AGENT]<br />
<em>Parse Flags</em>: {{if="$parseFlags"}{On}{Off}}<br />
<em>Login Method</em>: $loginMethod<br />
</div>'),

(30, 'contextMenu', '', '<ul id="userMenu" class="contextMenu">
    <li><a href="javascript:void(0);" data-action="private_im">Private IM</a></li>
  <li><a href="javascript:void(0);" data-action="profile">View Profile</a></li>
  {{if="fim_hasPermission($room, $user, ''moderate'')"}{<li><a href="javascript:void(0);" data-action="kick">Kick</a></li>}}
  {{if="$user[''adminPrivs''][''modUsers'']"}{<li><a href="javascript:void(0);" data-action="ban">Ban</a></li>}}
</ul>

<ul id="messageMenu" class="contextMenu">
  <li><a href="javascript:void(0);" data-action="link">Link To</a></li>
  <li><a href="javascript:void(0);" data-action="delete">Delete</a></li>
</ul>

<ul id="messageMenuImage" class="contextMenu">
  <li><a href="javascript:void(0);" data-action="url">Get URL</a></li>
  <li><a href="javascript:void(0);" data-action="link">Link To</a></li>
  <li><a href="javascript:void(0);" data-action="delete">Delete</a></li>
</ul>

<ul id="roomMenu" class="contextMenu">
  <li><a href="javascript:void(0);" data-action="enter">Enter</a></li>
  <li><a href="javascript:void(0);" data-action="archive">View Archive</a></li>
  <li><a href="javascript:void(0);" data-action="edit">Edit</a></li>
  <li><a href="javascript:void(0);" data-action="delete">Delete</a></li>
</ul>');

-- DIVIDE

INSERT INTO `{prefix}templates` (`id`, `name`, `vars`, `data`) VALUES
(31, 'register', '', '');