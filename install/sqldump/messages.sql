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
  `messageId` int(10) NOT NULL AUTO_INCREMENT COMMENT 'The unique ID of the message.',
  `userId` int(10) NOT NULL COMMENT 'The ID of the user who made the message.',
  `roomId` int(10) NOT NULL COMMENT 'The ID of the room the message was made in.',
  `rawText` varchar(5000) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL COMMENT 'The raw (uncensored, unformatted) text of the message.',
  `htmlText` varchar(5000) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL COMMENT 'The HTML-formatted text of the message.',
  `apiText` varchar(5000) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL COMMENT 'The API-formatted text of the message.',
  `salt` int(10) NOT NULL COMMENT 'The ID of the salt used for encryption (with the corrosponding value found in the product''s configuration file).',
  `iv` varchar(15) CHARACTER SET utf8 COLLATE utf8_bin  NOT NULL COMMENT 'The base64-encoded IV used for encryption.',
  `deleted` int(1) NOT NULL COMMENT 'Whether or not the message has been deleted by an administrator.',
  `time` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'The time the message was made on.',
  `ip` varchar(20) CHARACTER SET utf8 COLLATE utf8_bin  NOT NULL COMMENT 'The IP of the user who made the message.',
  `flag` varchar(10) CHARACTER SET utf8 COLLATE utf8_bin  NOT NULL COMMENT 'The content-type flag of the message (e.g. video, image, url, email).',
  PRIMARY KEY (`messageId`),
  KEY `deleted` (`deleted`),
  KEY `time` (`time`),
  KEY `roomId` (`roomId`),
  KEY `userId` (`userId`)
) ENGINE={engine} DEFAULT CHARSET=utf8 COLLATE=utf8_bin;