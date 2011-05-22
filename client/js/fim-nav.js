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

var light;
var roomid;


  if ($('body').attr('data-mode') == 'mobile') {
(function($, undefined ) {

$( "#menu" ).live( "listviewcreate", function() {
        var list = $( this ),
                listview = list.data( "listview" );

  var accordionDecorator = function() {
        list.find('li').each(function(index, accordion) {
                // Format the accordion accordingly:
                // <li>...normal stuff in a jQM li
                //   <div class="ui-li-accordion">...contents of this</div>
                // </li>
                // If we find an accordion element, make the li action be to open the accordion element
      // console.log('accordion found ' + accordion);
                // Get the li 
                var $accordion = $(accordion);
                $li = $accordion.closest('li');
                // Move the contents of the accordion element to the end of the <li>
                $li.append($accordion.clone());
                $accordion.remove();
                // Unbind all click events
                $li.unbind('click');
                // Remove all a elements
                $li.find('a').remove();
                // Bind click handler to show the accordion
                $li.bind('click', function() {
                        // Check that the current flap isn't already open
                        var $accordion = $(this).find('.ui-li-accordion');
                        if ($accordion.css('display') != 'none') {
                                $accordion.slideUp();
                                $(this).removeClass('ui-li-accordion-open');
                                return;
                        }
                        // Close all other accordion flaps
                        list.find('.ui-li-accordion').slideUp();
                        // Open this flap 
                        $accordion.slideToggle();
                        $(this).toggleClass('ui-li-accordion-open');
                });
        });
        };

        accordionDecorator();

        // Make sure that the decorator gets called on listview refresh too
  var orig = listview.refresh;
  listview.refresh = function() {
    orig.apply(listview, arguments[0]);
    accordionDecorator();
  };
});

})( jQuery );
  }

function ajaxDialogue(uri,title,id,width,cF) {
  var dialog = $('<div style="display: none;" id="' + id +  '"></div>').appendTo('body');
  dialog.load(
    uri,
    {},
    function (responseText, textStatus, XMLHttpRequest) {
      $('button').button();

      if (light) {
        var windowWidth = document.documentElement.clientWidth;
        if (width > windowWidth || !width) {
          width = windowWidth;
        }
      }

      dialog.dialog({
        width: (width ? width : 600),
        title: title,
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

function quickDialogue(content,title,id,width) {
  var dialog = $('<div style="display: none;" id="' + id +  '">' + content + '</div>').appendTo('body');
  dialog.dialog({
    width: (width ? width: 600),
    title: title,
    hide: "puff",
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

function notify(text,header,id,id2) {
  if ($('#' + id + ' > #' + id + id2).html()) {
    
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
  
  $('#icon_settings').click(function() {
    quickDialogue('<div class="leftright middle"><form action="/chat.php" method="post"><label><input type="checkbox" name="s[audio]"' + (soundOn ? ' checked="checked"' : '') + '  /> Audio</label><br /><label><input type="checkbox" name="s[reverse]"' + (reverse ? ' checked="checked"' : '') + ' /> Posts Ordered Oldest First</label><br /><label><input type="checkbox" name="s[complex]" ' + (complex ? ' checked="checked"' : '') + ' /> Complex Layout (with Avatars)</label><br /><label><select name="s[style]"><option value="1" data-name="ui-darkness">jQueryUI Darkness</option><option value="2" data-name="ui-lightness">jQueryUI Lightness</option><option value="3" data-name="redmond">Redmond (High Contrast 1)</option><option value="4" data-name="cupertino">Cupertino</option><option value="5" data-name="dark-hive">Dark Hive</option></select> Style</label><br /><input type="submit" value="Refresh Page" /></form><br /><br /><button onclick="webkitNotifyRequest(function() {});">Enable Desktop Notifications</button></div>','Settings','settingsAltDialouge');
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