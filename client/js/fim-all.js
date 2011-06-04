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

function quickDialogue(content,title,id,width) {
  var dialog = $('<div style="display: none;" id="' + id +  '">' + content + '</div>').appendTo('body');
  dialog.dialog({
    width: (width ? width: 600),
    title: title,
    hide: "puff",
    modal: true,
    close: function() {
      $('#' + id).empty().remove(); // Housecleaning, needed if we want the next dialouge to work properly.
    }
  });

  return false;
}

function quickConfirm(text) {
  $('<div id="dialog-confirm"><span class="ui-icon ui-icon-alert" style="float: left; margin: 0px 7px 20px 0px;"></span>' + text + '</div>').dialog({
    resizable: false,
    height: 240,
    modal: true,
    hide: "puff",
    buttons: {
      Confirm: function() {
        $(this).dialog("close");
        return true;
      },
      Cancel: function() {
        $(this).dialog("close");
        return false;
      }
    }
  });
}

function ajaxDialogue(uri,title,id,width,cF) {
  var dialog = $('<div style="display: none;" id="' + id +  '"></div>').appendTo('body');
  dialog.load(
    uri,
    {},
    function (responseText, textStatus, XMLHttpRequest) {
      $('button').button();

      var windowWidth = document.documentElement.clientWidth;
      if (width > windowWidth || !width) {
        width = windowWidth;
      }

      dialog.dialog({
        width: (width ? width : 600),
        title: title,
        hide: "puff",
        modal: true,
        close: function() {
          $('#' + id).empty().remove(); // Housecleaning, needed if we want the next dialouge to work properly.
          if (cF) {
            cF();
          }
        }
      });
    }
  );

  return false;
}

function ajaxTabDialogue(uri,id,width,cF) {
  var dialog = $('<div style="display: none;" id="' + id +  '"></div>').appendTo('body');
  dialog.load(
    uri,
    {},
    function (responseText, textStatus, XMLHttpRequest) {
      $('button').button();

      var windowWidth = document.documentElement.clientWidth;
      if (width > windowWidth || !width) {
        width = windowWidth;
      }

      dialog.tabbedDialog({
        width: (width ? width : 600),
        modal: true,
        hide: "puff",
        close: function() {
          $('#' + id).empty().remove(); // Housecleaning, needed if we want the next dialouge to work properly.
          if (cF) {
            cF();
          }
        }
      });
    }
  );

  return false;
}

function notify(text,header,id,id2) {
  if ($('#' + id + ' > #' + id + id2).html()) {
    // Do nothing
  }
  else {
    if ($('#' + id).html()) {
      $('#' + id).append('<br />' + text);
    }
    else {
      $.jGrowl('<div id="' + id + '"><span id="' + id + id2 + '">' + text + '</span></div>', {
        sticky: true,
        glue: true,
        header: header
      }); 
    }
  }
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




//// TODO: Merge into plugins
$.fn.tabbedDialog = function (dialogOptions,tabOptions) {
  this.tabs(tabOptions);
  this.dialog(dialogOptions);
  this.find('.ui-tab-dialog-close').append($('a.ui-dialog-titlebar-close'));
  this.find('.ui-tab-dialog-close').css({'position':'absolute','right':'0', 'top':'23px'});
  this.find('.ui-tab-dialog-close > a').css({'float':'none','padding':'0'});
  var tabul = this.find('ul:first');
  this.parent().addClass('ui-tabs').prepend(tabul).draggable('option','handle',tabul); 
  this.siblings('.ui-dialog-titlebar').remove();
  tabul.addClass('ui-dialog-titlebar');
}




$(document).ready(function() {
  window.forumUrl = 'http://www.victoryroad.net/';

  window.complex = ($('body').attr('data-complex') === '1' ? 1 : 0);
  window.userId = parseInt($('body').attr('data-userId'));
  window.roomId = parseInt($('body').attr('data-roomId'));
  window.layout = ($('body').attr('data-layout'));
  window.soundOn = ($('body').attr('data-ding') === '1' ? true : false);
  window.reverse = ($('body').attr('data-reverse') === '1' ? 1 : 0);
  window.longPolling = ($('body').attr('data-longPolling') === '1' ? 1 : 0);
});


function showAllRooms() {
  $.ajax({
    url: 'api/getRooms.php',
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
        var roomName = $(this).find('roomName').text();
        var roomId = $(this).find('roomId').text();
        var roomTopic = $(this).find('roomTopic').text();
        var isFav = ($(this).find('favorite').text() == 'true' ? true : false);
        var isPriv = ($(this).find('optionDefinitions > privateIm').text() == 'true' ? true : false);
        var isOwner = (parseInt($(this).find('owner').text()) == userId ? true : false);
        
        var text = '<li><a href="chat.php?room=' + roomId + '">' + roomName + '</a></li>';
        
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

  roomId = $('body').attr('data-roomId');

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
    ajaxDialogue('template.php?template=kickForm','Kick User','kickUserDialogue',1000);
    
    $.ajax({
      url: 'api/getRooms.php',
      timeout: 5000,
      type: 'GET',
      cache: false,
      success: function(xml) {
        var roomHtml = '';

        $(xml).find('room').each(function() {
          var roomName = $(this).find('roomName').text();
          var roomId = $(this).find('roomId').text();
          var roomTopic = $(this).find('roomTopic').text();
          var isFav = ($(this).find('favorite').text() == 'true' ? true : false);
          var isPriv = ($(this).find('optionDefinitions > privateIm').text() == 'true' ? true : false);
          var isOwner = (parseInt($(this).find('owner').text()) == userId ? true : false);

          roomHtml += '<option value="' + roomId + '">' + roomName + '</option>';
        });
        
        $('select[name=roomId]').html(roomHtml);
      },
      error: function() {
        alert('Failed to show all rooms');
      }
    });
    
    $.ajax({
      url: 'api/getRooms.php',
      timeout: 5000,
      type: 'GET',
      cache: false,
      success: function(xml) {
        var roomHtml = '';

        $(xml).find('room').each(function() {
          var roomName = $(this).find('roomName').text();
          var roomId = $(this).find('roomId').text();
          var roomTopic = $(this).find('roomTopic').text();
          var isFav = ($(this).find('favorite').text() == 'true' ? true : false);
          var isPriv = ($(this).find('optionDefinitions > privateIm').text() == 'true' ? true : false);
          var isOwner = (parseInt($(this).find('owner').text()) == userId ? true : false);

          roomHtml += '<option value="' + roomId + '">' + roomName + '</option>';
        });
        
        $('select[name=roomId]').html(roomHtml);
      },
      error: function() {
        alert('Failed to show all rooms');
      }
    });
  });

  $('a#privateRoom').click(function() {
    ajaxDialogue('template.php?template=privateRoomForm','Enter Private Room','privateRoomDialogue',1000);
  });

  $('a#manageKick').click(function() {
    ajaxDialogue('template.php?template=manageKickForm&roomId=' + roomId,'Manage Kicked Users in This Room','manageKickDialogue',600);
  });

  $('a#online').click(function() {
    ajaxDialogue('template.php?template=online','View Active Users','onlineDialogue',600);
  });

  $('a#createRoom').click(function() {
    ajaxDialogue('template.php?template=createRoomForm','Create a New Room','createRoomDialogue',1000);
  });

  $('a#editRoom').click(function() {
    ajaxDialogue('template.php?template=editRoomForm&roomId=' + roomId,'Edit Room','editRoomDialogue',1000);
  });

  $('a.editRoomMulti').click(function() {
    ajaxDialogue('template.php?template=editRoomForm&roomId=' + $(this).attr('data-roomId'),'Edit Room','editRoomDialogue',1000);
  });

  $('#icon_help').click(function() {
    ajaxTabDialogue('template.php?template=help','helpDialogue',1000);
  });
  
  $('#icon_note, #messageArchive').click(function() {
    quickDialogue('<table><thead><tr><th>User</th><th>Time</th><th>Message</th></tr></thead><tbody id="archiveMessageList"></tbody></table>','Archive','archiveDialogue',1000);

    archive(0);
  });
  
  $('#roomList').click(function() {
    var roomHtml = '';
    
    $.ajax({
      url: 'api/getRooms.php',
      timeout: 5000,
      type: 'GET',
      cache: false,
      success: function(xml) {
        $(xml).find('room').each(function() {
          var roomName = $(this).find('roomName').text();
          var roomId = $(this).find('roomId').text();
          var roomTopic = $(this).find('roomTopic').text();
          var isFav = ($(this).find('favorite').text() == 'true' ? true : false);
          var isPriv = ($(this).find('optionDefinitions > privateIm').text() == 'true' ? true : false);
          var isOwner = (parseInt($(this).find('owner').text()) == userId ? true : false);

          roomHtml += '<tr id="room' + roomId + '"><td><a href="/chat.php?room=' + roomId + '">' + roomName + '</a></td><td>' + roomTopic + '</td><td>' + (isOwner ? '<a href="#" class="editRoomMulti" data-roomId="' + roomId + '"><img src="images/document-edit.png" class="standard" alt="Configure" /></a>' : '') + '</td></tr>';
        });
        quickDialogue('<table><thead><tr><th>Name</th><th>Topic</th><th>Actions</th></tr></thead><tbody>' + roomHtml + '</tbody></table>','Room List','roomListDialogue',600);
      },
      error: function() {
        alert('Failed to show all rooms');
      }
    });
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
    $("#kickUserForm").submit(function(){
      data = $("#kickUserForm").serialize(); // Serialize the form data for AJAX.
      $.post("content/kick.php?phase=2",data,function(html) {
        quickDialogue(html,'','kickUserResultDialogue');
      }); // Send the form data via AJAX.

      $("#kickUserDialogue").dialog('close');

      return false; // Don't submit the form.
    });
  });
});

function archive(id) {
  var encrypt = 'base64';

  $.ajax({
    url: 'api/getMessages.php?rooms=' + roomId + '&messageIdMin=' + (lastMessage) + '&archive=1&messageIdMin=' + id + '&messageLimit=40',
    type: 'GET',
    timeout: 1000,
    async: true,
    data: '',
    contentType: "text/xml; charset=utf-8",
    dataType: "xml",
    cache: false,
    success: function (xml) {
      if ($(xml).find('messages > message').length > 0) {
        $(xml).find('messages > message').each(function() {
          var text = unxml($(this).find('htmlText').text());
          var messageTime = $(this).find('messageTimeFormatted').text();

          var messageId = Number($(this).find('messageId').text());

          var userName = $(this).find('userData > userName').text();
          var userId = Number($(this).find('userData > userId').text());
          var groupFormatStart = unxml($(this).find('userData > startTag').text());
          var groupFormatEnd = unxml($(this).find('userData > endTag').text());

          var styleColor = $(this).find('defaultFormatting > color').text();
          var styleHighlight = $(this).find('defaultFormatting > highlight').text();
          var styleFontface = $(this).find('defaultFormatting > fontface').text();
          var styleGeneral = parseInt($(this).find('defaultFormatting > general').text());

          var style = 'color: rgb(' + styleColor + '); background: rgb(' + styleHighlight + '); font-family: ' + styleFontface + ';';

          if (styleGeneral & 256) {
            style += 'font-weight: bold;';
          }
          if (styleGeneral & 512) {
            style += 'font-style: oblique;';
          }
          if (styleGeneral & 1024) {
            style += 'text-decoration: underline;';
          }
          if (styleGeneral & 2048) {
            style += 'text-decoration: line-through;';
          }

            var data = '<tr id="archiveMessage' + messageId + '"><td>' + groupFormatStart + '<span class="userName userNameTable" data-userId="' + userId + '">' + userName + '</span>' + groupFormatEnd + '</td><td>' + messageTime + '</td><td style="' + style + '" data-messageid="' + messageId + '">' + text + '</td></tr>';

            if (window.reverse) {
            $('#archiveMessageList').append(data);
          }
          else {
            $('#archiveMessageList').prepend(data);
          }

            if (messageId > lastMessage) {
            lastMessage = messageId;
          }
        });

          if (typeof contextMenuParse === 'function') {
          contextMenuParse();
        }
      }
    },
    error: function() {  alert('Error'); },
  });
}