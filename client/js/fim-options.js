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

/* Option-specific functions
 **/

var roomRef = new Object;
var roomList = new Array;

function getRoomList() {
  $.ajax({
    url: 'api/getRooms.php?permLevel=post',
    timeout: 5000,
    type: 'GET',
    async: false,
    cache: false,
    success: function(xml) {

      $(xml).find('room').each(function() {
        var roomName = $(this).find('roomName').text();
        var roomId = $(this).find('roomId').text();
        var roomTopic = $(this).find('roomTopic').text();
        var isFav = ($(this).find('favorite').text() == 'true' ? true : false);
        var isPriv = ($(this).find('optionDefinitions > privateIm').text() == 'true' ? true : false);
        var isOwner = (parseInt($(this).find('owner').text()) == userid ? true : false);
        
        roomRef[roomName] = roomId;
        roomList.push(roomName);
      });
    },
    error: function() {
      alert('Rooms not obtained.');
    }
  });
  
  return true;
}

$(document).ready(function() {
  getRoomList();
  
  $("#defaultRoom").autocomplete({
    source: roomList
  });
  $("#watchRoomBridge").autocomplete({
    source: roomList
  });
  
  $('#defaultHighlight').ColorPicker({
    color: '',
    onShow: function (colpkr) {
      $(colpkr).fadeIn(500);
      return false;
    },
    onHide: function (colpkr) {
      $(colpkr).fadeOut(500);
      return false; 
    },
    onChange: function(hsb, hex, rgb) {
      $('#defaultHighlight').css('background-color','#' + hex);
      $('#defaultHighlight').val(hex);
      $('#fontPreview').css('background-color','#' + hex);
    }
  });

  $('#defaultColour').ColorPicker({
    color: '',
    onShow: function (colpkr) {
      $(colpkr).fadeIn(500);
      return false;
    },
    onHide: function (colpkr) {
      $(colpkr).fadeOut(500);
      return false; 
    },
    onChange: function(hsb, hex, rgb) {
      $('#defaultColour').css('background-color','#' + hex);
      $('#defaultColour').val(hex);
      $('#fontPreview').css('color','#' + hex);
    }
  });

  $('#fontPreview').css('color','');
  $('#defaultColour').css('background-color','');
  $('#fontPreview').css('background-color','');
  $('#defaultHighlight').css('background-color','');

  if ($('#defaultItalics').is(':checked')) {
    $('#fontPreview').css('font-style','italic');
  }
  else {
    $('#fontPreview').css('font-style','normal');
  }

  if ($('#defaultBold').is(':checked')) {
    $('#fontPreview').css('font-weight','bold');
  }
  else {
    $('#fontPreview').css('font-style','normal');
  }

  $("#changeSettingsForm").submit(function(){
    data = $("#changeSettingsForm").serialize(); // Serialize the form data for AJAX.
    $.post("content/options.php?phase=2",data,function(html) {
      quickDialogue(html,'','changeSettingsResultDialogue');
    }); // Send the form data via AJAX.

    $("#changeSettingsDialogue").empty().remove(); // Housecleaning, needed if we want the colorpicker to work in another changesettings dialogue.
    $(".colorpicker").empty().remove(); // Housecleaning, needed if we want the colorpicker to work in another changesettings dialogue.

    return false; // Don't submit the form.
  });
});

function addRoom() {
  var val = $("#watchRoomBridge").val();
  var id = roomRef[val];
  
  if (!id) {
    alert('Room does not exist.');
  }
  else {
    var currentRooms = $("#watchRooms").val().split(",");
    currentRooms.push(id);

    $("#watchRoomsList").append("<span id=\"watchRoomSubList" + id + "\">" + val + " (<a href=\"javascript:void(0);\" onclick=\"removeRoom(" + id + ");\">x</a>), </span>");
    $("#watchRooms").val(currentRooms.toString(","));
  }
}

function removeRoom(id) {
  $("#watchRoomSubList" + id).fadeOut(500, function() {
    $(this).remove();
  });

  var currentRooms = $("#watchRooms").val().split(",");
  for (var i = 0; i < currentRooms.length; i++) {
    if(currentRooms[i] == id) {
      currentRooms.splice(i, 1);
      break;
    }
  }

  $("#watchRooms").val(currentRooms.toString(","));
}