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

CREATE TABLE IF NOT EXISTS `{prefix}hooks` (
  `hookId` int(10) NOT NULL AUTO_INCREMENT COMMENT 'A unique ID corrosponding to the hook. It is not used in retrieving hook contents (and thus could, for whatever reason, be changed on a dime).',
  `hookName` varchar(40) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL COMMENT 'The hook''s name, which will be used for most identification. These can be compounded (multiple rows with the same hookname can exist) if multiple entries exist for the hook.',
  `code` longtext CHARACTER SET utf8 COLLATE utf8_bin NOT NULL COMMENT 'The code of the particular hook. It will be executed using PHP''s EVAL.',
  `state` enum('on', 'off') NOT NULL COMMENT 'Whether or not the hook is active.',
  PRIMARY KEY (`hookId`),
  KEY (`hookName`)
) ENGINE={engine} DEFAULT CHARSET=utf8;