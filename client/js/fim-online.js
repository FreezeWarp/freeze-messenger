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

/* Online-specific functions
 * updateOnline */

function updateOnline() {
  $.ajax({
    url: 'api/getAllActiveUsers.php',
    type: 'GET',
    timeout: 2400,
    cache: false,
    success: function(xml) {
      var data = '';

      $(xml).find('user').each(function() {
        var userName = $(this).find('userName').text();
        var userId = $(this).find('userId').text();
        var startTag = $(this).find('startTag').text();
        var endTag = $(this).find('endTag').text();
        var roomData = new Array();

        $(this).find('room').each(function() {
          var roomId = $(this).find('roomId').text();
          var roomName = $(this).find('roomName').text();
          roomData.push('<a href="/chat.php?room=' + roomId + '">' + roomName + '</a>');
        });
        roomData = roomData.join(', ');

        data += '<tr><td>' + startTag + userName + endTag + '</td><td>' + roomData + '</td></tr>';
      });

      $('#onlineUsers').html(data);
    },
    error: function() {
      $('#onlineUsers').html('Refresh Failed');
    },
  });
}

var timer2 = setInterval(updateOnline,2500);