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

CREATE TABLE IF NOT EXISTS `{prefix}users` (
  `userId` int(10) NOT NULL COMMENT 'The unique ID of the user.',
  `userName` varchar(300) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL COMMENT 'The user''s name.',
  `userGroup` int(10) NOT NULL DEFAULT 1 COMMENT 'The admin-set usergroup ID of the user.',
  `allGroups` varchar(1000) NOT NULL DEFAULT '1' COMMENT 'A comma-seperated list of the IDs of all admin-set groups the user is a part of. This is not used on PHPBB and Vanilla logins (both instead use socialGroups).',
  `avatar` varchar(1000) NOT NULL COMMENT 'The URL of the user''s avatar.',
  `profile` varchar(1000) NOT NULL COMMENt 'The URL of the user''s profile or website.',
  `socialGroups` varchar(1000) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL COMMENT 'A comma-seperated list of the ids of all groups the user has joined.',
  `userFormatStart` varchar(100) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL COMMENT 'HTML-formatting to be prepended to the user''s name for its display.',
  `userFormatEnd` varchar(100) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL COMMENT 'HTML-formatting to be appended to the user''s name for its display.',
  `password` varchar(32) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL COMMENT 'The hashed password of the user. It is only used on vanilla logins.',
  `joinDate` timestamp NOT NULL COMMENT 'The date the user joined. It may not be used on logins other than vanilla.',
  `birthDate` timestamp NOT NULL COMMENT 'The date of the user''s birth, used for parental control settings.',
  `lastSync` timestamp NOT NULL COMMENT 'The date the userdata was last synced. This is not applicable for vanilla logins.',
  `defaultRoom` int(10) NOT NULL DEFAULT 1 COMMENT 'The user''s default room.',
  `interface` varchar(50) NOT NULL COMMENT 'The web-accessible interface the user prefers to default to.',
  `favRooms` varchar(1000) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL DEFAULT '1' COMMENT 'A comma-seperated list of rooms the user has ranked as being favourites.',
  `watchRooms` varchar(1000) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL COMMENT 'A comma-seperated list of rooms the user would like to be notified about when new messages are made',
  `status` int(3) NOT NULL COMMENT 'The user''s activity status.',
  `defaultFormatting` int(10) NOT NULL COMMENT 'A bitfield corrosponding to the user''s defaulting formatting (e.g. bold, italics) of all messages.',
  `defaultHighlight` varchar(20) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL COMMENT 'The user''s default highlight colour used for all messages.',
  `defaultColor` varchar(20) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL COMMENT 'The user''s default foreground colour used for all messages.',,
  `defaultFontface` varchar(100) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL COMMENT 'The fontface ID used for all of the user''s messages.',
  `settings` int(10) NOT NULL DEFAULT 0 COMMENT 'A bitfield corrosponding to different user settings.',
  `userPrivs` int(10) NOT NULL DEFAULT 16 COMMENT 'A bitfield corrosponding to admin-set user priviledges.',
  `adminPrivs` int(10) NOT NULL COMMENT 'A bitfield corrosponding to admin-set administrative priviledges.',,
  PRIMARY KEY (`userId`)
) ENGINE={engine} DEFAULT CHARSET=utf8;