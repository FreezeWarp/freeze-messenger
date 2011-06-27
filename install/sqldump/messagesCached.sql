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

CREATE TABLE IF NOT EXISTS `{prefix}messagesCached` (
  `id` int(10) NOT NULL AUTO_INCREMENT COMMENT 'A unique (temporary) ID for the message cache entry.',
  `messageId` int(10) NOT NULL COMMENT 'The ID of the message.',
  `roomId` int(10) NOT NULL COMMENT 'The ID of the room the message is in.',
  `userId` int(10) NOT NULL COMMENT 'The ID of the user who made the message.',
  `userName` varchar(300) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL COMMENT 'The name of the user who made the message.',
  `avatar` varchar(1000) NOT NULL COMMENT 'The URL of the avatar of the user who made the message.',
  `profile` varchar(1000) NOT NULL COMMENT 'The profile URL of the user who made the message.',
  `userGroup` int(10) NOT NULL DEFAULT 1 COMMENT 'The ID of the admin-defined usergroup of the user.',
  `allGroups` varchar(300) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL COMMENT 'A comma-seperated list of admin-set groups the user is a part of.',
  `socialGroups` varchar(300)  CHARACTER SET utf8 COLLATE utf8_bin NOT NULL COMMENT 'A comma-seperated list of groups the user has joined.',
  `userFormatStart` varchar(100) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL COMMENT 'Formatting to be prepended to the user''s name.',
  `userFormatEnd` varchar(100) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL COMMENT 'Formatting to be appended to the user''s name.',
  `defaultFormatting` int(10) NOT NULL COMMENT 'The default formatting of the user''s text (bitfield).',
  `defaultHighlight` varchar(20) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL COMMENT 'The default highlight of the user''s text.',
  `defaultColor` varchar(20) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL COMMENT 'The default color of the user''s text.',
  `defaultFontface` varchar(100) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL COMMENT 'The default fontface of the user''s text.',
  `time` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'The time the message was made on.',
  `htmlText` varchar(5000) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL COMMENT 'The HTML-formatted text of the message.',
  `apiText` varchar(5000) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL COMMENT 'The API-formatted text of the message.',
  `flag` varchar(10) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL COMMENT 'The content-type flag set for the message.',
  PRIMARY KEY (`id`)
) ENGINE=MEMORY DEFAULT CHARSET=utf8;