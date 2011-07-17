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

CREATE TABLE IF NOT EXISTS `{prefix}uploadTypes` (
  `typeId` int(11) NOT NULL AUTO_INCREMENT COMMENT 'The unique ID for the room.',
  `extension` varchar(10) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL COMMENT 'The extension that will allow upload of files using these settings.',
  `mime` varchar(200) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL COMMENT 'The content type to output files of this type as.',
  `maxSize` int(10) NOT NULL COMMENT 'The maximum size (in bytes) files of this type can be.',
  `container` enum('video','image','audio','text','html','archive','other') CHARACTER SET utf8 COLLATE utf8_bin NOT NULL COMMENT 'The container that should be used to output the file. This is recognized by clients for best viewing.',
  PRIMARY KEY (`typeId`),
  UNIQUE KEY (`extension`)
) ENGINE={engine} DEFAULT CHARSET=utf8;

-- DIVIDE

INSERT INTO `{prefix}uploadTypes` (`typeId`, `extension`, `mime`, `maxSize`, `container`) VALUES
(1, 'png', 'image/png', '1048576', 'image'),
(2, 'jpg', 'image/jpeg', '1048576', 'image'),
(3, 'jpeg', 'image/jpeg', '1048576', 'image'),
(4, 'gif', 'image/gif', '1048576', 'image');