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

CREATE TABLE IF NOT EXISTS `{prefix}groups` (
  `groupId` int(10) NOT NULL COMMENT 'The unique ID of the group.',
  `groupName` varchar(100) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL COMMENT 'The name of the group.',
  `memberIds` varchar(1000) NOT NULL COMMENT 'A comma-seperated list of members.',
  `userFormatStart` varchar(300) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL COMMENT 'Username formatted to be prepended to the username.',
  `userFormatEnd` varchar(300) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL COMMENT 'Username formatted to be appended to the username.',
  PRIMARY KEY (`groupId`)
) ENGINE={engine} DEFAULT CHARSET=utf8;