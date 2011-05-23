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

$(document).ready(function(){
  
  
  $('#icon_settings').click(function() {
    ajaxTabDialogue('/content/options.php','changeSettingsDialogue',1000,function() {
        $('.colorpicker').empty().remove();
      });
/*    
  var dialog = $('<div id="settingsDialog"><ul class="tabList">  <li><a href="#settings1">Chat Display</a></li>  <li><a href="#settings2">Message Formatting</span></a></li>  <li><a href="#setings3">General</a></li></ul></div>').appendTo('body');

  dialog.tabbedDialog({'modal':false,'width':800, 'height':600,'minWidth':400, 'minHeight':300,'draggable':true});
  */
/*    tabDialogue('/content/options.php','Change My Settings','changeSettingsDialogue',1000,
      function() {
        $('.colorpicker').empty().remove();
      }
    );*/
//    quickDialogue('<div class="leftright middle"><form action="/chat.php" method="post"><label><input type="checkbox" name="s[audio]"' + (soundOn ? ' checked="checked"' : '') + '  /> Audio</label><br /><label><input type="checkbox" name="s[reverse]"' + (reverse ? ' checked="checked"' : '') + ' /> Posts Ordered Oldest First</label><br /><label><input type="checkbox" name="s[complex]" ' + (complex ? ' checked="checked"' : '') + ' /> Complex Layout (with Avatars)</label><br /><label><select name="s[style]"><option value="1" data-name="ui-darkness">jQueryUI Darkness</option><option value="2" data-name="ui-lightness">jQueryUI Lightness</option><option value="3" data-name="redmond">Redmond (High Contrast 1)</option><option value="4" data-name="cupertino">Cupertino</option><option value="5" data-name="dark-hive">Dark Hive</option></select> Style</label><br /><input type="submit" value="Refresh Page" /></form><br /><br /><button onclick="webkitNotifyRequest(function() {});">Enable Desktop Notifications</button></div>','Settings','settingsAltDialouge');
  });
  
  roomid = $('body').attr('data-roomid');

  if ($('body').attr('data-mode') == 'mobile') {
    
  }
  else {
      $('#menu').accordion({
        autoHeight: false,
        navigation: true,
        clearStyle: true
      });
  }

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
    ajaxDialogue('/content/online.php','View Active Users','onlineDialogue',600);
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

  $('a#changeSettings,a.changeSettingsMulti').click(function() {
    ajaxDialogue('/content/options.php','Change My Settings','changeSettingsDialogue',1000,
      function() {
        $('.colorpicker').empty().remove();
      }
    );
  });

  $('#icon_help').click(function() {
    ajaxDialogue('/content/help.php','Help','helpDialogue',1000);
  });
  
  $('#icon_note').click(function() {
    window.location = '/archive.php?roomid=' + roomid;
  });

  $('#copyrightLink').click(function() {
    quickDialogue('<div style="text-align: center;">FIM, including (but not limited to) FIM\'s private web API, FIM\'s public XML API, FIM\'s legacy public CSV API, and all sourcecode created for use originally with FIM &copy; 2010-2011 Joseph T. Parsons.<br /><br />jQuery, jQueryUI, and all jQueryUI Themeroller Themes &copy; The jQuery Project.<br /><br />jGrowl &copy; 2009 Stan Lemon.<br />jQuery Cookie Plugin &copy; 2006 Klaus Hartl<br />EZPZ Tooltip &copy; 2009 Mike Enriquez<br />Beeper &copy; 2009 Patrick Mueller<br />Error Logger Utility &copy; Ben Alman<br />Context Menu &copy; 2008 Cory S.N. LaViska<br />jQTubeUtil &copy; 2010 Nirvana Tikku</div>','FIM Copyrights','copyrightDialogue');
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

<script type="text/javascript">
var roomRef = new Object;
{$roomData4}

$(document).ready(function(){
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

$(function() {
  var rooms = [
    $roomData3
  ];
  $("#defaultRoom").autocomplete({
    source: rooms
  });
  $("#watchRoomBridge").autocomplete({
    source: rooms
  });
});

function addRoom() {
  var val = $("#watchRoomBridge").val();
  var id = roomRef[val];

  var currentRooms = $("#watchRooms").val().split(",");
  currentRooms.push(id);

  $("#watchRoomsList").append("<span id=\"watchRoomSubList" + id + "\">" + val + " (<a href=\"javascript:void(0);\" onclick=\"removeRoom(" + id + ");\">x</a>), </span>");
  $("#watchRooms").val(currentRooms.toString(","));
}

function removeRoom(id) {
  $("#watchRoomSubList" + id).fadeOut(500,function(){\$(this).remove()});

  var currentRooms = $("#watchRooms").val().split(",");
  for(var i = 0; i < currentRooms.length; i++) {
    if(currentRooms[i] == id) {
      currentRooms.splice(i, 1);
      break;
    }
  }

  $("#watchRooms").val(currentRooms.toString(","));
}
</script>