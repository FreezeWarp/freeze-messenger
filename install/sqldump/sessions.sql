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

CREATE TABLE IF NOT EXISTS `{prefix}sessions` (
  `sessionId` int(10) NOT NULL AUTO_INCREMENT COMMENT 'A unique ID corrosponding to all sessions. It is largely unused.',
  `userId` int(10) NOT NULL COMMENT 'The ID of the user the session belongs to.',
  `anonId` int(10) NOT NULL COMMENT 'A unique ID that can be used for anonymous users.',
  `time` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'The time the session was last renewed.',
  `ip` varchar(100) NOT NULL COMMENT 'The IP address of the user of the session.',
  `browser` varchar(300) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL COMMENT 'The useragent of the user of the session.',
  `magicHash` varchar(100) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL COMMENT 'The session hash itself.',
  PRIMARY KEY (`sessionId`),
  UNIQUE KEY (`magicHash`)
) ENGINE=MEMORY DEFAULT CHARSET=utf8;