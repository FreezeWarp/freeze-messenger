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


/*******************************
 ****** Static Functions *******
 *******************************/

function unxml(data) {
  data = str_replace('&lt;','<',data);
  data = str_replace('&gt;','>',data);
  data = str_replace('&apos;',"'",data);
  data = str_replace('&quot;','"',data);

  return data;
}

function quickDialogue(content,title,id,width,cF,oF) {
  var dialog = $('<div style="display: none;" id="' + id +  '">' + content + '</div>').appendTo('body');

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





/*******************************
 ****** Variable Setting *******
 *******************************/

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

var roomRef = new Object;
var roomList = new Array;
var userRef = new Object;
var userList = new Array;
var groupRef = new Object;
var groupList = new Array;



$.ajax({
  url: 'api/getUsers.php',
  type: 'GET',
  timeout: 2400,
  cache: false,
  success: function(xml) {
    var data = '';

    $(xml).find('user').each(function() {
      var userName = unxml($(this).find('userName').text().trim());
      var userId = parseInt($(this).find('userId').text().trim());

      userRef[userName] = userId;
      userList.push(userName);
    });
  },
  error: function() {
    alert('User Not Obtained - Problems May Occur');
  },
});


$.ajax({
  url: 'api/getRooms.php?permLevel=post',
  timeout: 5000,
  type: 'GET',
  async: true,
  cache: false,
  success: function(xml) {
    $(xml).find('room').each(function() {
      var roomName = unxml($(this).find('roomName').text().trim());
      var roomId = parseInt($(this).find('roomId').text());

      roomRef[roomName] = roomId;
      roomList.push(roomName);
    });
  },
  error: function() {
    alert('Rooms Not Obtained - Problems May Occur');
  }
});


$.ajax({
  url: 'api/getGroups.php',
  timeout: 5000,
  type: 'GET',
  async: true,
  cache: false,
  success: function(xml) {
    $(xml).find('group').each(function() {
      var groupName = unxml($(this).find('groupName').text());
      var groupId = parseInt($(this).find('groupId').text());

      groupRef[groupName] = groupId;
      groupList.push(groupName);
    });
  },
  error: function() {
    alert('Groups Not Obtained - Problems May Occur');
  }
});



/*******************************
 ****** Content Functions ******
 *******************************/

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


function archive(idMax,idMin) {
  var encrypt = 'base64';
  var lastMessage = 0;
  var firstMessage = 0;

  if (idMax) {
    var where = 'messageIdStart=' + idMax;
  }
  else if (idMin) {
    var where = 'messageIdEnd=' + idMin;
  }
  else {
    var where = 'messageIdStart=0';
  }

  $('#archiveMessageList').html('');

  $.ajax({
    url: 'api/getMessages.php?rooms=' + roomId + '&archive=1&messageLimit=20&' + where,
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
          var text = unxml($(this).find('htmlText').text().trim());
          var messageTime = $(this).find('messageTimeFormatted').text().trim();
          var messageId = parseInt($(this).find('messageId').text().trim());

          var userName = $(this).find('userData > userName').text().trim();
          var userId = parseInt($(this).find('userData > userId').text().trim());
          var groupFormatStart = unxml($(this).find('userData > startTag').text().trim());
          var groupFormatEnd = unxml($(this).find('userData > endTag').text().trim());

          var styleColor = $(this).find('defaultFormatting > color').text().trim();
          var styleHighlight = $(this).find('defaultFormatting > highlight').text().trim();
          var styleFontface = $(this).find('defaultFormatting > fontface').text().trim();
          var styleGeneral = parseInt($(this).find('defaultFormatting > general').text().trim());

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
          if (messageId < firstMessage || !firstMessage) {
            firstMessage = messageId;
          }
        });

        if (typeof contextMenuParse === 'function') {
          contextMenuParse();
        }
      }

      $('#archiveNext').attr('onclick','archive(' + lastMessage + ',false);');
      $('#archivePrev').attr('onclick','archive(false,' + firstMessage + ');');
    },
    error: function() {
      alert('Error');
    },
  });
}


function addEntry(type,source) {
  var val = $("#" + type + "Bridge").val();
  switch(type) {
    case 'watchRooms':
    var id = roomRef[val];
    var type2 = 'Room';
    break;

    case 'moderators':
    case 'allowedUsers':
    var id = userRef[val];
    var type2 = 'User';
    break;

    case 'allowedGroups':
    var id = groupRef[val];
    var type2 = 'Group';
    break;
  }

  if (!id) {
    alert(type2 + ' does not exist.');
  }
  else {
    var currentRooms = $("#" + type).val().split(",");
    currentRooms.push(id);

    $("#" + type + "List").append("<span id=\"" + type + "SubList" + id + "\">" + val + " (<a href=\"javascript:void(0);\" onclick=\"removeEntry('" + type + "'," + id + ");\">×</a>), </span>");
    $("#" + type).val(currentRooms.toString(","));
  }
}


function removeEntry(type,id) {
  $("#" + type + "SubList" + id).fadeOut(500, function() {
    $(this).remove();
  });

  var currentRooms = $("#" + type).val().split(",");

  for (var i = 0; i < currentRooms.length; i++) {
    if(currentRooms[i] == id) {
      currentRooms.splice(i, 1);
      break;
    }
  }

  $("#" + type).val(currentRooms.toString(","));
}





/*******************************
 ****** Frontend Creation ******
 *******************************/

$(document).ready(function() {

  roomId = $('body').attr('data-roomId');

  $('#uploadFileForm').attr('action','uploadFile.php?roomId=' + roomId);

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




  /*** Kick ***/

  $('a#kick').click(function() {
    quickDialogue('<form action="#" id="kickUserForm" method="post">  <label for="userId">User</label>: <select name="userId">$userSelect</select><br />  <label for="roomId">Room</label>: <select name="roomId">$roomSelect</select><br />  <label for="time">Time</label>: <input type="text" name="time" id="time" style="width: 50px;" />  <select name="interval">    <option value="1">Seconds</option>    <option value="60">Minutes</option>    <option value="3600">Hours</option>    <option value="86400">Days</option>    <option value="604800">Weeks</option>  </select><br /><br />  <button type="submit">Kick User</button><button type="reset">Reset</button></form>','Kick User','kickUserDialogue',1000);

    $.ajax({
      url: 'api/getRooms.php',
      timeout: 5000,
      type: 'GET',
      cache: false,
      success: function(xml) {
        var roomHtml = '';

        $(xml).find('room').each(function() {
          var roomName = unxml($(this).find('roomName').text());
          var roomId = parseInt($(this).find('roomId').text());

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
          var userName = unxml($(this).find('userName').text().trim());
          var userId = parseInt($(this).find('userId').text().trim());

          userHtml += '<option value="' + userId + '">' + userName + '</option>';
        });

        $('select[name=userId]').html(userHtml);
      },
      error: function() {
        alert('Failed to show all users');
      }
    });

    $("#kickUserForm").submit(function() {
      data = $("#kickUserForm").serialize(); // Serialize the form data for AJAX.
      $.post("api/moderate.php",data + '&action=kick',function(html) {
        var status = $(xml).find('errorcode').text().trim();
        var emessage = $(xml).find('errormessage').text().trim();

        switch (status) {
          case '':
          $("#kickUserDialogue").dialog('close');
          break;

          case 'badroom':
          $('<div style="display: none;">A valid room was not provided.</div>').dialog({ title : 'Error'});
          break;
        }
        quickDialogue(html,'','kickUserResultDialogue');
      }); // Send the form data via AJAX.

      return false; // Don't submit the form.
    });
  });




  /*** Private Room ***/

  $('a#privateRoom').click(function() {
    quickDialogue('<form action="index.php?action=privateRoom&phase=2" method="post" id="privateRoomForm"><label for="userName">Username</label>: <input type="text" name="userName" id="userName" /><br /><small><span style="margin-left: 10px;">The other user you would like to talk to.</span></small><br /><br />  <input type="submit" value="Go" /></form>','Enter Private Room','privateRoomDialogue',1000,false,function() {
      $('#userName').autocomplete({
        source: userList
      });

      $("#privateRoomForm").submit(function() {
        data = $("#privateRoomForm").serialize(); // Serialize the form data for AJAX.
        $.post("api/createRoom.php",data,function(html) {
          quickDialogue(html,'','privateRoomResultDialogue');
        }); // Send the form data via AJAX.

        $("#privateRoomDialogue").dialog('close');

        return false; // Don't submit the form.
      });
    });
  });




  /*** Manage Kick ***/

  $('a#manageKick').click(function() {
    quickDialogue('<table class="page"><thead><tr class="hrow"><th>User</th><th>Kicked By</th><th>Kicked On</th><th>Expires On</th><th>Actions</th></tr>  </thead><tbody id="kickedUsers"></tbody></table>','Manage Kicked Users in This Room','manageKickDialogue',1000);

   //<tr>  <td>$kickedUser[userName]</td>  <td>$kickedUser[kickername]</td>  <td>$kickedUser[kickedOn]</td>  <td>$kickedUser[expiresOn]</td>  <td>    <form action="#" method="post" data-formid="unkick">      <input type="submit" value="Unkick" />      <input type="hidden" name="userId" value="$kickedUser[userId]" />      <input type="hidden" name="roomId" value="$room[id]" />    </form>  </td></tr>

    $("form[data-formid=unkick]").submit(function() {
      data = $(this).serialize(); // Serialize the form data for AJAX.
      $.post("content/unkick.php?phase=2",data,function(html) {
        quickDialogue(html,'','unkickDialogue');
      }); // Send the form data via AJAX.

      $("#manageKickDialogue").dialog('destroy');
      return false; // Don\\''t submit the form.
    });
  });




  /*** Online ***/

  $('a#online').click(function() {
    quickDialogue('<table class="page"><thead><tr class="hrow"><th>User</th><th>Rooms</th></tr></thead><tbody id="onlineUsers"><tr><td colspan="2">Loading...</td></tr></tbody></table>','View Active Users','onlineDialogue',600,function() {
      clearInterval(timer2);
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
            var userName = unxml($(this).find('userName').text().trim());
            var userId = parseInt($(this).find('userId').text().trim());
            var startTag = unxml($(this).find('startTag').text().trim());
            var endTag = unxml($(this).find('endTag').text().trim());
            var roomData = new Array();

            $(this).find('room').each(function() {
              var roomId = parseInt($(this).find('roomId').text().trim());
              var roomName = unxml($(this).find('roomName').text().trim());
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

    var timer2 = setInterval(updateOnline,2500);
  });




  /* Create Room */

  $('a#createRoom').click(function() {
    ajaxTabDialogue('template.php?template=editRoomForm&action=create','createRoomDialogue',1000,false,function() {
      $("#moderatorsBridge").autocomplete({
        source: userList
      });
      $("#allowedUsersBridge").autocomplete({
        source: userList
      });
      $("#allowedGroupsBridge").autocomplete({
        source: groupList
      });

      $("#editRoomForm").submit(function() {
        var data = $("#editRoomForm").serialize(); // Serialize the form data for AJAX.

        $.post("api/moderate.php",data + '&action=createRoom',function(xml) {
          var errorCode = unxml($(xml).find('errorcode').text().trim());
          var errorMessage = unxml($(xml).find('errortext').text().trim());
          var newRoomId = parseInt($(xml).find('insertId').text().trim());

          if (errorCode) {
            alert('An error has occured: ' + errorMessage);
          }
          else {
            ajaxDialogue('template.php?template=createRoomSuccess&insertId=' + newRoomId,'Room Created!','createRoomResultDialogue',600);
            $("#editRoomDialogue").dialog('close');
          }
        }); // Send the form data via AJAX.
        return false; // Don't submit the form.
      });
    });
  });




  /*** Edit Room ***/

  $('a#editRoom').click(function() {
    ajaxTabDialogue('template.php?template=editRoomForm','editRoomDialogue',1000,false,function() {
      $("#moderatorsBridge").autocomplete({
        source: userList
      });
      $("#allowedUsersBridge").autocomplete({
        source: userList
      });
      $("#allowedGroupsBridge").autocomplete({
        source: groupList
      });

      $.ajax({
        url: 'api/getRoomInfo.php?roomId=' + roomId,
        type: 'GET',
        timeout: 2400,
        cache: false,
        success: function(xml) {
          var data = '';

          var roomName = unxml($(xml).find('roomName').text().trim());
          var roomId = parseInt($(xml).find('roomId').text().trim());
          var allowedUsers = $(xml).find('allowedUsers').text().trim();
          var allowedGroups = $(xml).find('allowedGroups').text().trim();
          var moderators = $(xml).find('moderators').text().trim();

          $('#name').val(roomName);
          $('#allowedUsers').val(allowedUsers);
          $('#allowedGroups').val(allowedGroups);
          $('#moderators').val(moderators);

        },
        error: function() {
          alert('Error');
        },
      });
    });

    $("#editRoomForm").submit(function() {
      var data = $("#editRoomForm").serialize(); // Serialize the form data for AJAX.

      $.post("api/moderate.php",data + '&action=editRoom',function(xml) {
        var errorCode = unxml($(xml).find('errorcode').text().trim());
        var errorMessage = unxml($(xml).find('errortext').text().trim());
        var newRoomId = parseInt($(xml).find('insertId').text().trim());

        if (errorCode) {
          alert('An error has occured: ' + errorMessage);
        }
        else {
          ajaxDialogue('template.php?template=editRoomSuccess&insertId=' + newRoomId,'Room Edited!','editRoomResultDialogue',600);
          $("#editRoomDialogue").dialog('close');
        }
      }); // Send the form data via AJAX.
      return false; // Don't submit the form.
    });
  });

  $('a.editRoomMulti').click(function() {
    ajaxDialogue('template.php?template=editRoomForm&roomId=' + $(this).attr('data-roomId'),'Edit Room','editRoomDialogue',1000);
  });




  /*** Help ***/

  $('#icon_help').click(function() {
    ajaxTabDialogue('template.php?template=help','helpDialogue',1000);
  });




  /*** Archive ***/

  $('#icon_note, #messageArchive').click(function() {
    quickDialogue('<table><thead><tr><th>User</th><th>Time</th><th>Message</th></tr></thead><tbody id="archiveMessageList"></tbody></table><br /><br /><button id="archivePrev"><< Prev</button><button id="archiveNext">Next >></button>','Archive','archiveDialogue',1000);

    var lastMessage = archive(0);
  });




  /*** Room List ***/

  $('#roomList').click(function() {
    var roomHtml = '';

    $.ajax({
      url: 'api/getRooms.php',
      timeout: 5000,
      type: 'GET',
      cache: false,
      success: function(xml) {
        $(xml).find('room').each(function() {
          var roomName = $(this).find('roomName').text().trim();
          var roomId = parseInt($(this).find('roomId').text().trim());
          var roomTopic = $(this).find('roomTopic').text().trim();
          var isFav = ($(this).find('favorite').text().trim() == 'true' ? true : false);
          var isPriv = ($(this).find('optionDefinitions > privateIm').text().trim() == 'true' ? true : false);
          var isOwner = (parseInt($(this).find('owner').text().trim()) == userId ? true : false);

          roomHtml += '<tr id="room' + roomId + '"><td><a href="/chat.php?room=' + roomId + '">' + roomName + '</a></td><td>' + roomTopic + '</td><td>' + (isOwner ? '<a href="#" class="editRoomMulti" data-roomId="' + roomId + '"><img src="images/document-edit.png" class="standard" alt="Configure" /></a>' : '') + '</td></tr>';
        });
        quickDialogue('<table><thead><tr><th>Name</th><th>Topic</th><th>Actions</th></tr></thead><tbody>' + roomHtml + '</tbody></table>','Room List','roomListDialogue',1000);
      },
      error: function() {
        alert('Failed to show all rooms');
      }
    });
  });




  /*** Stats ***/

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
          var roomName = unxml($(this).find('roomName').text().trim());
          var roomId = parseInt($(this).find('roomId').text().trim());

          $(this).find('user').each(function() {
            var userName = unxml($(this).find('userData > userName').text().trim());
            var userId = parseInt($(this).find('userData > userId').text().trim());
            var startTag = unxml($(this).find('userData > startTag').text().trim());
            var endTag = unxml($(this).find('userData > endTag').text().trim());
            var position = parseInt($(this).find('position').text().trim());
            var messageCount = parseInt($(this).find('messageCount').text().trim());

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




  /*** Copyright & Credits ***/

  $('#copyrightLink').click(function() {
    ajaxTabDialogue('template.php?template=copyright','copyrightDialogue',600);
  });




  /*** User Settings ***/

  $('#icon_settings, #changeSettings, a.changeSettingsMulti').click(function() {
    ajaxTabDialogue('template.php?template=userSettingsForm','changeSettingsDialogue',1000,function() {
      $('.colorpicker').empty().remove();
    }, function() {
      $("#defaultRoom").autocomplete({
        source: roomList
      });
      $("#watchRoomsBridge").autocomplete({
        source: roomList
      });

      $.ajax({
        url: 'api/getFonts.php',
        timeout: 5000,
        type: 'GET',
        async: true,
        cache: false,
        success: function(xml) {
          $(xml).find('font').each(function() {
            var fontName = unxml($(this).find('fontName').text().trim());
            var fontId = parseInt($(this).find('fontId').text().trim());
            var fontGroup = unxml($(this).find('fontGroup').text().trim());
            var fontData = unxml($(this).find('fontData').text().trim());

            $('#defaultFace').append('<option value="' + fontId + '" style="' + fontData + '" data-font="' + fontData + '">' + fontName + '</option>');
          });
        },
        error: function() {
          alert('Fonts not obtained.');
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

      $("#changeSettingsForm").submit(function() {
        data = $("#changeSettingsForm").serialize(); // Serialize the form data for AJAX.
        $.post("api/moderate.php",data + "&action=userOptions&userId=" + userId,function(xml) {

          quickDialogue(xml,'','changeSettingsResultDialogue');
        }); // Send the form data via AJAX.

        $("#changeSettingsDialogue").empty().remove(); // Housecleaning, needed if we want the colorpicker to work in another changesettings dialogue.
        $(".colorpicker").empty().remove(); // Housecleaning, needed if we want the colorpicker to work in another changesettings dialogue.

        window.reload();

        return false; // Don't submit the form.
      });
    });
  });

  windowResize();
});

function windowResize () {
  var windowWidth = (window.innerWidth ? window.innerWidth : document.documentElement.clientWidth);
  var windowHeight = (window.innerHeight ? window.innerHeight : document.documentElement.clientHeight);

  switch (window.layout) {
    default:
    $('#messageList').css('height',(windowHeight - 230));
    $('#messageList').css('max-width',((windowWidth - 10) * .75));

  /* Body Padding: 10px
   * Right Area Width: 75%
   * "Enter Message" Table Padding: 10px
   *** TD Padding: 2px (on Standard Styling)
   * Message Input Container Padding : 3px (all padding-left)
   * Left Button Width: 36px
   * Message Input Text Area Padding: 6px */
    $('#messageInput').css('width',(((windowWidth - 10) * .75) - 10 - 2 - 3 - 36 - 6 - 20));


    $('body').css('height',window.innerHeight);
    break;
  }
}

window.onresize = windowResize;