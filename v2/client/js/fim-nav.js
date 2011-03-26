$(document).ready(function(){
  roomid = $('data[name=roomid]').attr('value');

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

  $('a#changeSettings').click(function() {
    ajaxDialogue('/content/options.php','Change My Settings','changeSettingsDialogue',1000,
      function() {
        $('.colorpicker').empty().remove();
      }
    );
  });

  $('#icon_help').click(function() {
    ajaxDialogue('/content/help.php','Help','helpDialogue',1000);
  });

  $('#copyrightLink').click(function() {
    quickDialogue('FIM, including (but not limited to) FIM\'s private web API, FIM\'s public XML API, FIM\'s legacy public CSV API, and all sourcecode created for use originally with FIM &copy; 2010-2011 Joseph T. Parsons.<br /><br />jQuery, jQueryUI, and all jQueryUI Themeroller Themes &copy; The jQuery Project.<br /><br />jGrowl &copy; 2009 Stan Lemon.<br /><br />jQuery Cookie Plugin &copy; 2006 Klaus Hartl<br /><br />EZPZ Tooltip &copy; 2009 Mike Enriquez<br /><br />Context Menu &copy; ???<br /><br />Youtube Plugin &copy; ???','FIM Copyrights','copyrightDialogue');
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

function ajaxDialogue(uri,title,id,width,cF) {
  var dialog = $('<div style="display: none;" id="' + id +  '"></div>').appendTo('body');
  dialog.load(
    uri,
    {},
    function (responseText, textStatus, XMLHttpRequest) {
      dialog.dialog({
        width: (width ? width: 600),
        title: title,
        close: function() {
          $('#' + id).empty().remove(); // Housecleaning, needed if we want the next dialouge to work properly.
          cF();
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
    close: function() {
      $('#' + id).empty().remove(); // Housecleaning, needed if we want the next dialouge to work properly.
    }
  });

  return false;
}

function notify(text,header,id,id2) {
  if ($('#' + id + ' > #' + id + id2).html()) { }
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