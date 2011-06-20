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
  `userId` int(10) NOT NULL,
  `userName` varchar(300) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL,
  `userGroup` int(10) NOT NULL DEFAULT 1,
  `allGroups` varchar(1000) NOT NULL DEFAULT '1',
  `avatar` varchar(1000) NOT NULL,
  `profile` varchar(1000) NOT NULL,
  `socialGroups` varchar(1000) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL,
  `userFormatStart` varchar(100) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL,
  `userFormatEnd` varchar(100) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL,
  `password` varchar(32) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL,
  `joinDate` timestamp NOT NULL,
  `birthDate` timestamp NOT NULL,
  `lastSync` timestamp NOT NULL,
  `defaultRoom` int(10) NOT NULL DEFAULT 1,
  `favRooms` varchar(300) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL DEFAULT '1',
  `watchRooms` varchar(300) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL,
  `status` int(3) NOT NULL,
  `defaultFormatting` int(10) NOT NULL,
  `defaultHighlight` varchar(20) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL,
  `defaultColor` varchar(20) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL,
  `defaultFontface` varchar(100) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL,
  `settings` int(10) NOT NULL DEFAULT 0,
  `userPrivs` int(10) NOT NULL DEFAULT 16,
  `adminPrivs` int(10) NOT NULL,
  PRIMARY KEY (`userId`)
) ENGINE={engine} DEFAULT CHARSET=utf8;