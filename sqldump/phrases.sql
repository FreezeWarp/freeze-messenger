CREATE TABLE IF NOT EXISTS `{prefix}phrases` (
  `id` int(10) NOT NULL AUTO_INCREMENT,
  `name` varchar(50) NOT NULL,
  `text_en` longtext CHARACTER SET utf8 COLLATE utf8_bin NOT NULL,
  `text_jp` longtext CHARACTER SET utf8 COLLATE utf8_bin NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE={engine} DEFAULT CHARSET=utf8;

INSERT INTO `{prefix}phrases` (`id`, `name`, `text_en`, `text_jp`) VALUES
(1, 'brandingTitle', 'VRIM2 Beta', ''),
(2, 'brandingFavicon', 'http://vrim.victoryroad.net/images/favicon.gif', ''),
(3, 'brandingFaviconIE', 'http://vrim.victoryroad.net/images/favicon1632.ico', ''),
(4, 'doctype', '<!DOCTYPE HTML>', ''),
(5, 'brandingDescription', 'The Victory Road Instant Messenger, or VRIM for short, is an advanced online instant messenger with support for user-created rooms.', ''),
(6, 'brandingCommunityLinks', '      <ul>\r\n        <li><a href="http://victoryroad.net/">VictoryRoad Forums</a></li>\r\n        <li><a href="http://victoryroad.net/arcade.php">VictoryRoad Arcade</a></li>\r\n        <li><a href="http://dex.victoryroad.net/">VictoryRoad PokéDex</a></li>\r\n        <li><a href="http://battles.victoryroad.net/">VictoryRoad VictoryBattles</a></li>\r\n        <li><a href="http://www.floatzel.net/">Floatzel.net</a></li>\r\n        <li><a href="http://bugs.vrim.victoryroad.net/">Report Bugs</a></li>\r\n      </ul>', ''),
(7, 'brandingIE9Color', 'black', ''),
(9, 'hookHeadAll', '', ''),
(10, 'hookHeadLite', '<style>\r\nbody { background: #333333 url(''https://www.victoryroad.net/images/zoroark/bg-lines.png'') repeat top left; font: 10pt verdana, geneva, lucida, ''lucida grande'', arial, helvetica, sans-serif; color: #FFFFFF; }\r\n\r\n#page, #content { background: #666666; }\r\n#menubar, #oldmenubar { background: #AA0011; }\r\n.oldmenu a:link, .oldmenu a:active, .oldmenu a:visited, .oldmenu a:hover { color: #FFFFFF }\r\n#footer { background: #BBBBBB; }\r\n#rightFooter { background: #660011; }\r\n#rightFooter span.pseudolink { color: #FFFFFF; }\r\n\r\ntable.page, table.main { border: 1px solid #770008; border-width: 3px; }\r\ntable.page td, table.page th, table.main td, table.main th { background: #CCCCCC; color: #000000; }\r\ntable, tr, td { border: 1px solid #770008; border-width: 0px; }\r\ntr.hrow td, td.hrow, thead { background: #660011 !important; color: #FFFFFF !important; }\r\n\r\na:link { color: #990011; }\r\na:active { color: #FFFFFF; }\r\na:visited { color: #990011; }\r\na:hover { color: #FFFFFF; }\r\n\r\ninput, input:hover, button, button:hover, select, select:hover, textarea, textarea:hover { background: #CCCCCC; color: #000000; border: 1px solid #770008; }\r\nbutton, input[type=submit], input[type=reset], input[type=button] { border: 3px outset #990011; border-width: 3px; }\r\nbutton:hover, input[type=submit]:hover, input[type=reset]:hover, input[type=button]:hover { background: #AA0011; border-width: 3px; }\r\nbutton:active, input[type=submit]:active, input[type=reset]:active, input[type=button]:active { background: #666666; border-width: 3px; }\r\nbutton[disabled], input[disabled] { background: #CCCCCC; border: 3px outset #990011 ; border-width: 3px; opacity: .6; }\r\n\r\n.cssdropdown ul li, .contextMenu { background: #666666; color: #000000; }\r\n.cssdropdown ul li:hover, .contextMenu li.hover a { background: #333333; color: #FFFFFF; }\r\n</style>', ''),
(11, 'hookHeadFull', '', ''),
(12, 'hookHeadPopup', '', ''),
(13, 'hookHeadMobile', '', ''),
(14, 'hookPageStartLite', '<div id="banner"><a href="./index.php"><img src="http://vrim.victoryroad.net/client/media/vrim.png" alt="Return Home" /></a></div>', ''),
(15, 'hookPageStartFull', '', ''),
(16, 'hookPageStartAll', '', ''),
(17, 'hookContentStartLite', '', ''),
(18, 'hookContentStartFull', '', ''),
(19, 'hookContentStartAll', '', ''),
(20, 'hookContentEndLite', '', ''),
(21, 'hookContentEndFull', '', ''),
(22, 'hookContentEndAll', '', ''),
(23, 'hookBodyEndLite', '', ''),
(24, 'hookBodyEndFull', '', ''),
(25, 'hookBodyEndAll', '<script type="text/javascript">\r\n\r\n  var _gaq = _gaq || [];\r\n  _gaq.push([''_setAccount'', ''UA-20772732-1'']);\r\n  _gaq.push([''_trackPageview'']);\r\n\r\n  (function() {\r\n    var ga = document.createElement(''script''); ga.type = ''text/javascript''; ga.async = true;\r\n    ga.src = (''https:'' == document.location.protocol ? ''https://ssl'' : ''http://www'') + ''.google-analytics.com/ga.js'';\r\n    var s = document.getElementsByTagName(''script'')[0]; s.parentNode.insertBefore(ga, s);\r\n  })();\r\n\r\n</script>', ''),
(26, 'hookFooterLite', 'Victory Road ©2006-2011 Scott Cheney/Cat333Pokémon/猫３３３ポケモン<br />\r\nTheme by A''bom and Cat333Pokémon<br /><br />', ''),
(27, 'chatAccessDenied', 'You see... our incredibly high standards of admittance, or perhaps just our unjust bias, has resulted in us unfairly denying you access to this probably-not-worth-your time room. We do apologize for being such snobs... but yet, we still are. So, we must now ask you to leave.', ''),
(28, 'chatPrivateRoom', 'This room is a private room between you and another individual, and is not accessible to any other user or administrator. If you would like to ignore this person right click their username and choose &quot;Ignore&quot;. If you are being harrassed, please contact either Cat333Pokémon or FreezeWarp.', ''),
(29, 'chatNotModerated', 'This room is not an official room, and as such is not actively moderated. Please excercise caution when talking to people you do not know, and do not reveal personal information.', ''),
(30, 'chatAdminAccess', 'You are not a part of this group, rather you are only granted access because you are an administrator. Please respect user privacy: do not post in this group unwanted, and moreover do not spy without due reason.', ''),
(31, 'settingsParentalControlsLabel', 'Disable Parental Controls:', ''),
(32, 'settingsParentalControlsBlurb', 'By default parental controls are enabled that help keep younger users safe. Check this to disable these features, however we take no responsibility for any reprecussions.', ''),
(33, 'settingsDisableDingLabel', 'Disable Ding:', ''),
(34, 'settingsDisableDingBlurb', 'If checked, the ding will be completely disabled in the chat.', ''),
(35, 'settingsDisableFormattingLabel', 'Disable Formatting:', ''),
(36, 'settingsDisableFormattingBlurb', 'This will disable default formatting some users use on their messages.', ''),
(37, 'settingsDisableVideoLabel', 'Disable Video Embeds:', ''),
(38, 'settingsDisableVideoBlurb', 'This will disable video embeds in rooms that allow them, replaced with a "click to activate" link.', ''),
(39, 'settingsDisableImageLabel', 'Disable Images:', ''),
(40, 'settingsDisableImageBlurb', 'This will disable image embeds in rooms that allow them, replaced with a link or alternate text.', ''),
(41, 'settingsReversePostOrderLabel', 'Show Old Posts First:', ''),
(42, 'settingsReversePostOrderBlurb', 'This will show newer posts at the bottom instead of the top, as is common with many instant messenging programs.', ''),
(43, 'settingsDefaultRoomLabel', 'Default Room:', ''),
(44, 'settingsDefaultRoomBlurb', 'This changes what room defaults when you first visit VRIM.', ''),
(45, 'settingsWatchRoomsLabel', 'Watch Rooms:', ''),
(46, 'settingsWatchRoomsBlurb', 'These rooms will be monitored for new posts, similar to Private IMs.', ''),
(47, 'settingsWatchRoomsCurrentRooms', 'Current Rooms: ', ''),
(48, 'settingsDefaultFormattingPreview', 'Here''s a Preview!', ''),
(49, 'chatBannedMessage', 'We''re sorry, but for the time being you have been banned from the chat. You may contact a Victory Road administrator for more information.', ''),
(50, 'archiveNumResultsLabel', 'Number of Results Per Page:', ''),
(51, 'archiveRoomLabel', 'Room:', ''),
(52, 'archiveMessage', 'Here you can find and search through every post made on VRIM. Simply enter a room, time frame, and the number of results to show and we can get started:', ''),
(53, 'archiveReversePostOrderLabel', 'Oldest First:', ''),
(54, 'archiveUserIdsLabel', 'User IDs (Optional):', ''),
(55, 'templateAdmin', 'AdminCP', ''),
(56, 'templateAdminImages', 'Moderate Images', ''),
(57, 'templateAdminUsers', 'Moderate Users', ''),
(58, 'templateAdminBanUser', 'Ban a User', ''),
(59, 'templateAdminUnbanUser', 'Unban a User', ''),
(60, 'templateAdminCensor', 'Modify Censor', ''),
(61, 'templateAdminPhrases', 'Modify Branding', ''),
(62, 'templateAdminMaintenance', 'Maintenance', ''),
(63, 'templateLogin', 'Login', ''),
(64, 'templateLogout', 'Logout', ''),
(65, 'templateChangeSettings', 'Change Settings', ''),
(66, 'templateKickUser', 'Kick a User', ''),
(67, 'templateUnkickUser', 'Unkick a User', ''),
(68, 'templateManageKickedUsers', 'Manage kicked Users', ''),
(69, 'templateStats', 'View Stats', ''),
(70, 'templateActiveUsers', 'Who''s Online', ''),
(71, 'templatePrivateIM', 'Enter Private IM', ''),
(72, 'templateEditRoom', 'Edit Room', ''),
(73, 'templateCreateRoom', 'Create a Room', ''),
(74, 'templateRoomList', 'Room List', ''),
(75, 'templateArchive', 'Message Archive', ''),
(76, 'templateCommunityLinksCat', 'Community', ''),
(77, 'templateUserCat', 'Me', '私'),
(78, 'templateRoomListCat', 'Rooms', ''),
(79, 'templateActiveUsersCat', 'Online', ''),
(80, 'templateCopyrightCat', 'Copyright', ''),
(81, 'statsNumResults', 'Number of Results: ', ''),
(82, 'statsRoomList', 'Room List (IDs): ', ''),
(83, 'statsChooseSettings', 'Choose Settings', ''),
(84, 'statsChooseSettingsSubmit', 'Go', 'いきます。'),
(85, 'statsChooseSettingsReset', 'Reset', ''),
(86, 'statsPlace', '#', ''),
(87, 'templateShowAllRooms', 'Show All', ''),
(88, 'templateAllCopyrights', 'See All Copyrights', ''),
(89, 'archiveChooseSettings', 'The Archives: Select a Room', ''),
(90, 'chatRoomDoesNotExist', 'After hours of intense computation, we have failed to locate the room you selected.', ''),
(91, 'archiveHeaderUser', 'User', ''),
(92, 'archiveHeaderTime', 'Time', ''),
(93, 'archiveHeaderMessage', 'Message', ''),
(94, 'archivePageSelect', 'Page: ', ''),
(95, 'archiveViewAs', 'Results Format: ', ''),
(96, 'archiveFormatHTML', 'HTML', ''),
(97, 'archiveFormatBBCode', 'Forum BBCode', ''),
(98, 'help', '<div id="help" style="height: 400px;">\r\n<h3><a href="#">A Quick Introduction</a></h3>\r\n<div>\r\nFreezeMessenger 2.0 (FIM or FIM2) is an advanced AJAX-based online webmessenger and Instant Messenger substitute created to allow anybody to easily communicate with anybody else all across the web. It is highly sophisticated, supporting all modern browsers and utilizing various cutting-edge features in each. It was written from scratch by Joseph T. Parsons ("FreezeWarp") with PHP, MySQL, and other tricks along the way.\r\n</div>\r\n\r\n<h3><a href="#">Rules</a></h3>\r\n<div>In no part of the chat, whether it be in a public, private, official, or nonofficial room, are you allowed to:\r\n<ul>\r\n<li>Promote or inflict hatespeech.</li>\r\n<li>Post, link to, or encourage illegal material.</li>\r\n<li>Encourage or enable another member to do any of the above.</li>\r\n</ul></div>\r\n\r\n<h3><a href="#">Formatting Messages</a></h3>\r\n<div>The following tags are enabled for formatting:\r\n\r\n<ul>\r\n{{bbcodeBlock}}\r\n</ul></div>\r\n\r\n<h3><a href="#">Users Under the Legal Age</a></h3>\r\n<div>We take no responsibility for any harrassment, hate speach, or other issues users may encounter, however we will do our best to stop them if they are reported to proper administrative staff. Users will not be allowed to see mature rooms unless they have specified the "Disable Parental Controls" option in their user settings, and are encouraged to only talk to people privately whom they know.<br /><br />\r\n\r\nKeep in mind all content is heavily encrytped for privacy. Private conversations may only be viewed by server administration when neccessary, but can not be accessed by chat staff.</div>\r\n\r\n<h3><a href="#">Browser Requirements</a></h3>\r\n<div>FIM will work with any of the following browsers:\r\n<ul>\r\n  <li><a href="http://www.google.com/chrome" target="_BLANK">Chrome 10+</a></li>\r\n  <li><a href="http://windows.microsoft.com/ie9" target="_BLANK">Internet Explorer 8+</a></li>\r\n  <li><a href="http://www.mozilla.com/en-US/firefox/" target="_BLANK">Firefox 3.0+</a></li>\r\n  <li><a href="http://www.opera.com/download/" target="_BLANK">Opera 9+</a></li>\r\n  <li><a href="http://www.apple.com/safari/" target="_BLANK">Safari 5+</a></li>\r\n</ul><br /><br />\r\n\r\nThe Lite Layout also works on Internet Explorer 6+.</div>\r\n\r\n<h3><a href="#">FAQs</a></h3>\r\n<div>\r\n<ul>\r\n  <li><b>Can I Change the Style?</b> - Not normally, no, though in the full mode users can use an experimental and incredibly buggy feature by visiting <a href="../?experimental=true">this location</a>. There, under the "Me" category you can use one of many different, but untested, themes. Be warned: these are not complete. Once a theme is set, this style switcher will always appear. To remove it and the theme, clear your browser''s cookies.</li>\r\n  <li><b>Where Do I Report Bugs?</b> - If possible, please PM FreezeWarp.</li>\r\n  <li><b>When Will VRIM2 be Released?</b> - May 1st, if possible</li>\r\n  <li><b>Can I Create a Custom Frontend?</b> - If you want to, the XML API will soon be publicly documented. Both Cat333Pokémon and FreezeWarp already have plans for custom frontends.</li>\r\n  <li><b>Can I Donate to the Awesome Project?</b> - <a href="javascript:alert(''Donations not yet set up. But, please, if you want to, they will be shortly.'');">Please do. It really helps keep development going.</a></li>\r\n</ul></div>\r\n\r\n<h3><a href="#">Debug Information</a></h3>\r\n<div>Below is basic information useful for submitting bug reports:<br /><br />\r\n{{debugBlock}}\r\n</div>', ''),
(99, 'templateLoading', 'Loading...', ''),
(100, 'templateQuickCat', 'Key', ''),
(101, 'templateAdminHooks', 'Modify Hooks', ''),
(102, 'chatBannedTitle', 'We''re Sorry', ''),
(103, 'chatMatureTitle', 'We''re Sorry', ''),
(104, 'chatMatureMessage', 'This room is marked as being mature, thus access has been restricted. Parental controls can be disabled from within your user <a href="#" class="changeSettingsMulti">options</a>.', ''),
(105, 'archiveNoMessages', 'This room has no messages.', ''),
(106, 'archiveTitle', 'The Archives', ''),
(107, 'archiveSubmit', 'View Archive', ''),
(108, 'archiveNumResultsHook', '', ''),
(109, 'statsNumResultsHook', '', ''),
(110, 'uploadHeaderPreview', 'Preview', ''),
(111, 'uploadHeaderName', 'Name', 'なまえ'),
(112, 'uploadHeaderSize', 'Size', ''),
(113, 'uploadHeaderMime', 'Mime Type', ''),
(114, 'uploadHeaderActions', 'Actions', ''),
(115, 'uploadRating6', '6+ (E/G)', ''),
(116, 'uploadRating10', '10+ (E10+/PG)', ''),
(117, 'uploadRating13', '13+ (T/PG-13)', ''),
(118, 'uploadRating16', '16+ (M/R)', ''),
(119, 'uploadRating18', '18+ (AO/NC-17)', ''),
(120, 'uploadNewFileTitle', 'Upload New File', ''),
(121, 'uploadNewFileComputer', 'Upload from Computer', ''),
(122, 'uploadNewFilePreview', 'Preview & Submit', ''),
(123, 'uploadNewFileSubmitButton', 'Upload', ''),
(124, 'uploadErrorNoUrl', 'You did not specify a URL.', ''),
(125, 'uploadErrorNoYoutube', 'The URL does not appear to be a Youtube video.', ''),
(126, 'uploadErrorNoExist', 'That image does not exist or refuses to load.', ''),
(127, 'uploadErrorBadType', 'That image is not of a valid type.', ''),
(128, 'uploadErrorSize', 'The file you are trying to upload is too large.', ''),
(129, 'uploadErrorOther', 'Other Error: ', ''),
(130, 'uploadErrorFinal', 'Could not upload the file for unknown reasons.', ''),
(131, 'uploadErrorFileContents', 'Could not obtain file contents.', ''),
(132, 'uploadErrorMethod', 'Unknown upload method.', ''),
(133, 'editRoomNameLabel', 'Name', 'なまえ'),
(134, 'editRoomNameBlurb', 'Your group''s name. Note: This should not container anything vulgar or it will be deleted.', ''),
(135, 'editRoomAllowedUsersLabel', 'Allowed Users', ''),
(136, 'editRoomAllowedUsersBlurb', 'A comma-seperated list of User IDs who can view this chat. Moderators can see your conversation regardless of this setting. Use \\"*\\" for everybody.', ''),
(137, 'editRoomAllowedGroupsLabel', 'Allowed Groups', ''),
(138, 'editRoomAllowedGroupsBlurb', 'A comma-seperated list of Group IDs who can view this chat. Moderators can see your conversation regardless of this setting. Use "*" for everybody.', ''),
(139, 'editRoomModeratorsLabel', 'Moderators', ''),
(140, 'editRoomModeratorsBlurb', 'A comma-seperated list of moderators who can delete posts from your group.', ''),
(141, 'editRoomMatureLabel', 'Mature', ''),
(142, 'editRoomMatureBlurb', 'Mature rooms allow certain content that is otherwise not allowed in that users are required to enable access to these rooms first. In addition, the censor is disabled for all such rooms. <strong>Hatespeech, illegal content, and similar is disallowed regardless.', ''),
(143, 'editRoomBBCode', 'BB Code Settings', ''),
(144, 'editRoomBBCodeAll', 'Allow All Content', ''),
(145, 'editRoomBBCodeNoMulti', 'Disallow Multimedia (Youtube, etc.)', ''),
(146, 'editRoomBBCodeNoImg', 'Disallow Images and Multimedia', ''),
(147, 'editRoomBBCodeLink', 'Only Allow Basic Formatting and Links', ''),
(148, 'editRoomBBCodeBasic', 'Only Allow Basic Formatting', ''),
(149, 'editRoomBBCodeNothing', 'Allow No Formatting', ''),
(150, 'editRoomBBCodeBlurb', 'To prevent certain kinds of spam, different levels of BB code can be disallowed. Generally, this doesn\\''t really come at much benefit anybody, save for the nitpicky (like us).', ''),
(151, 'createRoomSubmit', 'Create Room', ''),
(152, 'createRoomReset', 'Reset', ''),
(153, 'editRoomSubmit', 'Edit Room', ''),
(154, 'editRoomReset', 'Reset', ''),
(155, 'createRoomCreatedAt', 'Your group was successfully created at:', ''),
(156, 'createRoomFail', '', ''),
(157, 'editRoomNoName', 'You did not specify a name.', ''),
(158, 'createRoomUnknownAction', 'Unknown Action', ''),
(159, 'editRoomUnknownAction', '', ''),
(160, 'editRoomNameTaken', 'The name for your group is already taken.', ''),
(161, 'createRoomDisabled', 'Room creation has been disabled.', ''),
(162, 'createRoomBanned', 'You have been banned from creating rooms.', ''),
(163, 'editRoomCreatedGo', 'Go There!', ''),
(164, 'editRoomNotOwner', 'You must be the owner to edit a room.', ''),
(165, 'editRoomDeleted', 'This room is deleted, and as such may not be edited.', ''),
(166, 'editRoomCensorLabel', 'Enable Censors', ''),
(167, 'onlineUsername', 'Username', ''),
(168, 'onlineRoom', 'Room', ''),
(169, 'onlineLoading', 'Loading...', ''),
(170, 'loginNoUser', 'No user exists with that user title. Is it possible that you have changed your name recently?', ''),
(171, 'loginNoPass', 'You appeared to have entered a wrong password. Remeber, passwords are case sensitive.', ''),
(172, 'loginBad', 'Unsuccessful Login', ''),
(173, 'loginTitle', 'Login to Victory Road Chat', ''),
(174, 'loginIntro', 'Hello. Please Enter Your Login Credentials Below:', ''),
(175, 'loginUsername', 'Username:', ''),
(176, 'loginPassword', 'Password:', ''),
(177, 'loginRemember', 'Remember Me for One Week?:', ''),
(178, 'loginSubmit', 'Launch', ''),
(179, 'loginReset', 'Reset', ''),
(180, 'loginGuestLinks', 'Guest Links', ''),
(181, 'templateAdminTemplates', 'Modify Templates', '');
