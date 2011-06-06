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

function ajaxDialogue(uri,title,id,width,cF,oF) {
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
        open: function() {
          if (oF) {
            oF();
          }
        },
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

function ajaxTabDialogue(uri,id,width,cF,oF) {
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
        open: function() {
          if (oF) {
            oF();
          }
        },
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
    ajaxDialogue('template.php?template=kickForm','Kick User','kickUserDialogue',1000,false,function() {
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

            roomHtml += '<option value="' + roomId + '">' + roomName + '</option>';
          });

          $('select[name=roomId]').html(roomHtml);
        },
        error: function() {
          alert('Failed to show all rooms');
        }
      });

      $.ajax({
        url: 'api/getUsers.php',
        timeout: 5000,
        type: 'GET',
        cache: false,
        success: function(xml) {
          var userHtml = '';
  
          $(xml).find('user').each(function() {
            var userName = $(this).find('userName').text();
            var userId = $(this).find('userId').text();
  
            userHtml += '<option value="' + userId + '">' + userName + '</option>';
          });
  
          $('select[name=userId]').html(userHtml);
        },
        error: function() {
          alert('Failed to show all users');
        }
      });
    });
  });

  $('a#privateRoom').click(function() {
    ajaxDialogue('template.php?template=privateRoomForm','Enter Private Room','privateRoomDialogue',1000);
  });

  $('a#manageKick').click(function() {
    ajaxDialogue('template.php?template=manageKickForm&roomId=' + roomId,'Manage Kicked Users in This Room','manageKickDialogue',600);
  });

  $('a#online').click(function() {
    ajaxDialogue('template.php?template=online','View Active Users','onlineDialogue',600,false,function() {
      var timer2 = setInterval(updateOnline,2500);
    });

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
            var startTag = unxml($(this).find('startTag').text());
            var endTag = unxml($(this).find('endTag').text());
            var roomData = new Array();
    
            $(this).find('room').each(function() {
              var roomId = $(this).find('roomId').text();
              var roomName = $(this).find('roomName').text();
              roomData.push('<a href="/chat.php?room=' + roomId + '">' + roomName + '</a>');
            });
            roomData = roomData.join(', ');
    
            data += '<tr><td>' + startTag + '<span class="userName">' + userName + '</span>' + endTag + '</td><td>' + roomData + '</td></tr>';
          });
    
          $('#onlineUsers').html(data);
        },
        error: function() {
          $('#onlineUsers').html('Refresh Failed');
        },
      });
    }
  });

  $('a#createRoom').click(function() {
    ajaxTabDialogue('template.php?template=editRoomForm','createRoomDialogue',1000,false,function() {
      $("#editRoomForm").submit(function() {
        var data = $("#editRoomForm").serialize(); // Serialize the form data for AJAX.

        $.post("api/moderate.php?roomId=" + roomId,data,function(xml) {
          quickDialogue(html,'','editRoomResultDialogue');
        }); // Send the form data via AJAX.

        $("#editRoomDialogue").dialog('close');
        return false; // Don't submit the form.
      });
    });
  });

  $('a#editRoom').click(function() {
    ajaxTabDialogue('template.php?template=editRoomForm&roomId=' + roomId,'editRoomDialogue',1000,false,function() {
      $("#editRoomForm").submit(function() {
        var data = $("#editRoomForm").serialize(); // Serialize the form data for AJAX.

        $.post("api/moderate.php?roomId=0",data,function(xml) {
          quickDialogue(html,'','editRoomResultDialogue');
        }); // Send the form data via AJAX.

        $("#editRoomDialogue").dialog('close');
        return false; // Don't submit the form.
      });
    });
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
  
  $('#viewStats').click(function() {
    var statsHtml = new Object;
    var statsHtml2 = '';
    var roomHtml = '';
    var number = 10;
    
    for (var i = 1; i <= number; i++) {
      statsHtml[i] = '';
    }
    
    $.ajax({
      url: 'api/getStats.php?rooms=' + roomId + '&maxResults=' + number,
      timeout: 5000,
      type: 'GET',
      cache: false,
      success: function(xml) {
        $(xml).find('room').each(function() {
          var roomName = $(this).find('roomName').text();
          var roomId = $(this).find('roomId').text();

          $(this).find('user').each(function() {
            var userName = $(this).find('userData > userName').text();
            var userId = $(this).find('userData > userId').text();
            var startTag = $(this).find('userData > startTag').text();
            var endTag = $(this).find('userData > endTag').text();
            var position = parseInt($(this).find('position').text());
            var messageCount = $(this).find('messageCount').text();

            statsHtml[position] += '<td>' + startTag + userName + endTag + ' (' + messageCount + ')</td>';
          });
          
          
          roomHtml += '<th>' + roomName + '</th>';
          
        });
        
        for (var i = 1; i <= number; i++) {
          statsHtml2 += '<tr><th>' + i + '</th>' + statsHtml[i] + '</tr>';
        }
        
        quickDialogue('<table><thead><tr><th>Position</th>' + roomHtml + '</tr></thead><tbody>' + statsHtml2 + '</tbody></table>','Room Stats','roomStatsDialogue',600);
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
    },function() {
      var roomRef = new Object;
      var roomList = new Array;
  
      $.ajax({
        url: 'api/getRooms.php?permLevel=post',
        timeout: 5000,
        type: 'GET',
        async: true,
        cache: false,
        success: function(xml) {
          $(xml).find('room').each(function() {
            var roomName = $(this).find('roomName').text();
            var roomId = $(this).find('roomId').text();
  
            roomRef[roomName] = roomId;
            roomList.push(roomName);
          });
  
          $("#defaultRoom").autocomplete({
            source: roomList
          });
          $("#watchRoomBridge").autocomplete({
            source: roomList
          });
        },
        error: function() {
          alert('Rooms not obtained.');
        }
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
    });
    
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