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
  `roomId` int(11) NOT NULL AUTO_INCREMENT COMMENT 'The unique ID for the room.',
  `roomName` varchar(50) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL COMMENT 'The name of the room.',
  `roomTopic` varchar(200) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL COMMENT 'The current topic of the room.',
  `owner` int(10) NOT NULL COMMENT 'The owner/creator of the room.',
  `allowedGroups` TEXT(10000) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL COMMENT 'A comma-seperated list of the IDs of the allowed groups in the room.',
  `allowedUsers` TEXT(10000) NOT NULL COMMENT 'A comma-seperated list of the IDs of the allowed users in the room.',
  `moderators` TEXT(10000) NOT NULL COMMENT 'A comma-seperated list of the IDs of the moderators of the room.',
  `options` int(10) NOT NULL COMMENT 'A bitfield corrosponding to certain room options.',
  `bbcode` int(1) NOT NULL COMMENT 'The level of bbcode allowed in the room.',
  `lastMessageTime` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00' COMMENT 'The time of the last message made in the room.',
  `lastMessageId` int(10) NOT NULL COMMENT 'The ID of the last message made in the room.',
  PRIMARY KEY (`roomId`),
  KEY `options` (`options`),
  KEY `owner` (`owner`),
  KEY `lastMessageId` (`lastMessageId`),
  KEY `lastMessageTime` (`lastMessageTime`)
) ENGINE={engine} DEFAULT CHARSET=utf8;

-- DIVIDE

INSERT INTO `{prefix}rooms` (`roomId`, `roomName`, `roomTopic`, `allowedGroups`, `allowedUsers`, `options`) VALUES
(1, 'Your Room!', 'Hit the Edit Room Button to Change Things or Use /topic to Change the Topic', '*', '*', 1)