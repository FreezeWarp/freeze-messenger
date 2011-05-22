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


importScripts('jquery-1.5.1.min.js');

$.ajax({
  url: 'api/getMessages.php?rooms=' + roomid + '&messageIdMin=' + (lastMessage) + '&messageLimit=40&watchRooms=1&activeUsers=1&order=' + (reverse ? 'reverse' : 'normal'),
  type: 'GET',
  timeout: timeout,
  async: true,
  data: '',
  contentType: "text/xml; charset=utf-8",
  dataType: "xml",
  cache: false,
  success: function no(xml) { postMessage(xml) },
//  error: ,
});