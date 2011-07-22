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

CREATE TABLE IF NOT EXISTS `{prefix}kick` (
  `userId` int(10) NOT NULL COMMENT 'The ID of the user who has been kicked.',
  `roomId` int(10) NOT NULL COMMENT 'The ID of the room the kick is active in.',
  `kickerid` int(10) NOT NULL COMMENT 'The ID of the user who made the kick.',
  `time` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'The time the kick was made on.',
  `length` int(10) NOT NULL COMMENT 'The time the kick will remain active for.',
  PRIMARY KEY (`userId`,`roomId`),
  KEY `time` (`time`),
  KEY `length` (`length`)
) ENGINE=MEMORY  DEFAULT CHARSET=utf8;
