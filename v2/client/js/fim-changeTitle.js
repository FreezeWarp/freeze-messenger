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

function changeTitle(room,title) {
  $.ajax({
    url: '/ajax/setTitle.php',
    type: 'POST',
    contentType: 'application/x-www-form-urlencoded;charset=UTF-8',
    cache: false,
    data: 'room=' + room + '&title=' + escape(title),
    success: function(html) {
      $('#title' + room).html('<a href="javascript:void(0);" onclick="$(\'td#title' + room + '\').html(\'<form action=&quot;#&quot; onsubmit=&quot;var title = $(\\\'#input' + room + '\\\').val(); changeTitle(' + room + ',title); return false;&quot; style=&quot;display: inline;&quot;><input type=&quot;text&quot; name=&quot;newTitle&quot; style=&quot;width: 300px&quot; value=&quot;' + escape(title) + '&quot; id=&quot;input' + room + '&quot; /></form>\'); $(this).hide();"><img src="/images/edit-rename.png" class="standard" alt="Configure" />' + title);
    },
    error: function() { alert('Could not update the topic.'); }
  });
}