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

CREATE TABLE IF NOT EXISTS `{prefix}rooms` (
  `roomId` int(11) NOT NULL AUTO_INCREMENT,
  `roomName` varchar(50) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL,
  `roomTopic` varchar(200) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL,
  `owner` int(10) NOT NULL,
  `allowedGroups` TEXT(10000) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL,
  `allowedUsers` TEXT(10000) NOT NULL,
  `moderators` TEXT(10000) NOT NULL,
  `options` int(10) NOT NULL,
  `bbcode` int(1) NOT NULL,
  `lastMessageTime` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `lastMessageId` int(10) NOT NULL,
  PRIMARY KEY (`roomId`),
  KEY `options` (`options`),
  KEY `owner` (`owner`),
  KEY `lastMessageId` (`lastMessageId`),
  KEY `lastMessageTime` (`lastMessageTime`)
) ENGINE={engine} DEFAULT CHARSET=utf8;

-- DIVIDE

INSERT INTO `{prefix}rooms` (`roomId`, `roomName`, `roomTopic`, `allowedGroups`, `allowedUsers`, `options`) VALUES
(1, 'Your Room!', 'Hit the Edit Room Button to Change Things or Use /topic to Change the Topic', '*', '*', 1)