/* FreezeMessenger Copyright Â© 2011 Joseph Todd Parsons

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


function showAllRooms() {
  $.ajax({
    url: '/api/getRooms.php',
    timeout: 5000,
    type: 'GET',
    cache: false,
    success: function(xml) {
      var roomFavHtml = '';
      var roomMyHtml = '';
      var roomPrivHtml = '';
      var roomHtml = '';
      var text = '';

      $(xml).find('room').each(function() {
        var roomName = $(this).find('roomname').text();
        var roomId = $(this).find('roomid').text();
        var roomTopic = $(this).find('roomtopic').text();
        var isFav = ($(this).find('favorite').text() == 'true' ? true : false);
        var isPriv = ($(this).find('optionDefinitions > privateim').text() == 'true' ? true : false);
        var isOwner = (parseInt($(this).find('owner').text()) == userid ? true : false);
        
        var text = '<li><a href="/chat.php?room=' + roomId + '">' + roomName + '</a></li>';
        
        if (isFav) {
          roomFavHtml += text;
        }
        if (isOwner && !isPriv) {
          roomMyHtml += text;
        }
        if (isPriv) {
          roomPrivHtml += text;
        }
        if (!isFav && !isOwner && !isPriv) {
          roomHtml += text;
        }
      });
      $('#rooms').html('<li>Favourites<ul>' + roomFavHtml + '</ul></li><li>My Rooms<ul>' + roomMyHtml + '</ul></li><li>General<ul>' + roomHtml + '</ul></li><li>Private<ul>' + roomPrivHtml + '</ul></li>');
    },
    error: function() {
      alert('Failed to show all rooms');
    }
  });
}


$(document).ready(function() {

  roomid = $('body').attr('data-roomid');

  $('#menu').accordion({
    autoHeight: false,
    navigation: true,
    clearStyle: true
  });

  $('table > thead > tr:first-child > td:first-child, table > tr:first-child > td:first-child').addClass('ui-corner-tl');
  $('table > thead > tr:first-child > td:last-child, table > tr:first-child > td:last-child').addClass('ui-corner-tr');
  $('table > tbody > tr:last-child > td:first-child, table > tr:last-child > td:first-child').addClass('ui-corner-bl');
  $('table > tbody > tr:last-child > td:last-child, table > tr:last-child > td:last-child').addClass('ui-corner-br');

  $('button').button();

  $('a#kick').click(function() {
    ajaxDialogue('/content/kick.php','Kick User','kickUserDialogue',1000);
  });

  $('a#privateRoom').click(function() {
    ajaxDialogue('/content/privateRoom.php','Enter Private Room','privateRoomDialogue',1000);
  });

  $('a#manageKick').click(function() {
    ajaxDialogue('/content/manageKick.php?roomid=' + roomid,'Manage Kicked Users in This Room','manageKickDialogue',600);
  });

  $('a#online').click(function() {
    ajaxDialogue('template.php?template=online','View Active Users','onlineDialogue',600);
  });

  $('a#createRoom').click(function() {
    ajaxDialogue('/content/createRoom.php','Create a New Room','createRoomDialogue',1000);
  });

  $('a#editRoom').click(function() {
    ajaxDialogue('/content/editRoom.php?roomid=' + roomid,'Edit Room','editRoomDialogue',1000);
  });

  $('a.editRoomMulti').click(function() {
    ajaxDialogue('/content/editRoom.php?roomid=' + $(this).attr('data-roomid'),'Edit Room','editRoomDialogue',1000);
  });

  $('#icon_help').click(function() {
    ajaxTabDialogue('template.php?template=help','helpDialogue',1000);
  });
  
  $('#icon_note').click(function() {
    window.location = '/archive.php?roomid=' + roomid;
  });

  $('#copyrightLink').click(function() {
    ajaxTabDialogue('template.php?template=copyright','copyrightDialogue',600);
  });

  $('#icon_settings, #changeSettings, a.changeSettingsMulti').click(function() {
    ajaxTabDialogue('template.php?template=userSettingsForm','changeSettingsDialogue',1000,function() {
      $('.colorpicker').empty().remove();
    });
  });

  $(document).ready(function(){
    $("#kickForm").submit(function(){
      data = $("#kickForm").serialize(); // Serialize the form data for AJAX.

        $.post("content/kick.php?phase=2",data); // Send the form data via AJAX.

        $("#kick").dialog('close');

        return false; // Don\'t submit the form.
    });
  });
});