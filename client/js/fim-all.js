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

function ajaxTabDialogue(uri,id,width,cF) {
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

  window.light = ($('body').attr('data-mode') === 'light' ? 1 : 0);
  window.complex = ($('body').attr('data-complex') === '1' ? 1 : 0);
  window.userid = parseInt($('body').attr('data-userid'));
  window.roomid = parseInt($('body').attr('data-roomid'));
  window.layout = ($('body').attr('data-layout'));
  window.soundOn = ($('body').attr('data-ding') === '1' ? true : false);
  window.reverse = ($('body').attr('data-reverse') === '1' ? 1 : 0);
  window.longPolling = ($('body').attr('data-longPolling') === '1' ? 1 : 0);
});