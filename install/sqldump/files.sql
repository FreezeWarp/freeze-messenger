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

CREATE TABLE IF NOT EXISTS `{prefix}files` (
  `fileId` int(10) NOT NULL AUTO_INCREMENT COMMENT 'A unique ID for the file.',
  `name` varchar(255) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL COMMENT 'The file''s name to be sent for downloading.',
  `type` varchar(255) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL DEFAULT 'upload' COMMENT 'The file''s source - in most cases "upload".',
  `mime` varchar(255) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL COMMENT 'The file''s mime-type/content-type.',
  `userId` int(10) NOT NULL COMMENT 'The ID of the user who uploaded the file.',
  `rating` enum('6','10','13','16','18') NOT NULL COMMENT 'The file''s parental content age rating.',
  `flags` varchar(255) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL COMMENT 'A comma-seperated list of parent content flags to apply to the file.',
  `date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'The date the file was uploaded.',
  `deleted` enum('yes','no') NOT NULL DEFAULT 'no' COMMENT 'Whether or not the file in question is deleted.',
  PRIMARY KEY (`fileId`)
) ENGINE={engine} DEFAULT CHARSET=utf8;
