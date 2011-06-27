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

CREATE TABLE IF NOT EXISTS {prefix}censorBlackWhiteLists(
  `listId` int(10) NOT NULL COMMENT 'The unique ID of the censor list.',
  `roomId` int(10) NOT NULL COMMENT 'The room ID the status is being applied to.',
  `status` enum('block','unblock') NOT NULL COMMENT 'The status of the list in regards to the room - either explicit block or explicit unblock.',
  PRIMARY KEY (`listId`,`roomId`)
) ENGINE={engine}  DEFAULT CHARSET=utf8;