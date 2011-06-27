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

CREATE TABLE IF NOT EXISTS `{prefix}censorLists` (
  `listId` int(10) NOT NULL AUTO_INCREMENT COMMENT 'The unique ID of the list.',
  `name` varchar(400) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL COMMENT 'The name of the list. It is only used for user identification.',
  `type` enum('black','white') NOT NULL COMMENT 'The list type - black to apply to no rooms automatically, and white to apply to all rooms automatically (opt-in vs. opt-out)',
  `options` int(4) NOT NULL DEFAULT '3' COMMENT 'A bitfield of specific options for the list.',
  PRIMARY KEY (`listId`)
) ENGINE={engine}  DEFAULT CHARSET=utf8;

-- DIVIDE

INSERT INTO `{prefix}censorLists` (`listId`, `name`, `type`) VALUES
(1, 'Example Censor', 'black');