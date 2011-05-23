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

/* Global Definitions
 * These are used throughout all other Javascript files, so are defined before all other FIM-specific files. */

function unxml(data) {
  data = str_replace('&lt;','<',data);
  data = str_replace('&gt;','>',data);
  data = str_replace('&apos;',"'",data);
  data = str_replace('&quot;','"',data);
  
  return data;
}

function webkitNotifyRequest(callback) {
  window.webkitNotifications.requestPermission(callback);
}

function webkitNotify(icon, title, notifyData) {
  if (window.webkitNotifications.checkPermission() > 0) {
    webkitNotifyRequest(function() { webkitNotify(icon, title, notifyData); });
  }
  else {
    notification = window.webkitNotifications.createNotification(icon, title, notifyData);
    notification.show();
  }
}

$(document).ready(function() {
  window.forumUrl = 'http://www.victoryroad.net/';

  window.light = ($('body').attr('data-mode') === 'light' ? 1 : 0);
  window.complex = ($('body').attr('data-complex') === '1' ? 1 : 0);
  window.userid = parseInt($('body').attr('data-userid'));
  window.roomid = parseInt($('body').attr('data-roomid'));
  window.layout = ($('body').attr('data-layout'));
  window.soundOn = ($('body').attr('data-ding') === '1' ? true : false);
  window.reverse = ($('body').attr('data-reverse') === '1' ? 1 : 0);
});