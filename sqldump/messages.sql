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

CREATE TABLE IF NOT EXISTS `{prefix}messages` (
  `messageId` int(10) NOT NULL AUTO_INCREMENT,
  `userId` int(10) NOT NULL,
  `roomId` int(10) NOT NULL,
  `rawText` varchar(5000) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL,
  `htmlText` varchar(5000) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL,
  `apiText` varchar(5000) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL,
  `salt` int(10) NOT NULL,
  `iv` varchar(15) CHARACTER SET utf8 COLLATE utf8_bin  NOT NULL,
  `deleted` int(1) NOT NULL,
  `flaggedUser` int(10) NOT NULL,
  `time` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `ip` varchar(20) CHARACTER SET utf8 COLLATE utf8_bin  NOT NULL,
  `flag` varchar(10) CHARACTER SET utf8 COLLATE utf8_bin  NOT NULL,
  PRIMARY KEY (`messageId`),
  KEY `deleted` (`deleted`),
  KEY `time` (`time`),
  KEY `roomId` (`roomId`),
  KEY `userId` (`userId`)
) ENGINE={engine} DEFAULT CHARSET=utf8 COLLATE=utf8_bin;