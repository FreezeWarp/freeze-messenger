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

CREATE TABLE IF NOT EXISTS `{prefix}censorWords` (
  `bbcodeId` int(10) NOT NULL AUTO_INCREMENT COMMENT 'A unique identifier for the word.',
  `bbcodeSlot` int(10) NOT NULL COMMENT 'The slot the BBcode should occupy. In general, only 16 or so of these can exist, thus there are very low limits on what bbcodes can be added.',
  `bbcodeName` varchar(100) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL COMMENT 'The name of the BBcode for display purposes.',
  `searchRegex` varchar(1000) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL COMMENT 'The regex to use for BBcode searching.',
  `replacement` varchar(1000) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL COMMENT 'The replacement text to be used.',
  PRIMARY KEY (`bbcodeId`)
) ENGINE={engine} DEFAULT CHARSET=utf8;