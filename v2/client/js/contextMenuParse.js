function contextMenuParse() {
  $('.username').contextMenu({
    menu: 'userMenu'
  },
  function(action, el) {
    switch(action) {
      case 'private_im':
      window.open('/index.php?action=privateRoom&phase=2&userid=' + $(el).attr('data-userid'),'privateim' + attr('data-userid')); 
      break;
      case 'profile':
      window.open('http://victoryroad.net/member.php?u=' + $(el).attr('data-userid'),'profile' + attr('data-userid')); 
      break;
      case 'kick':
      $('#kick').dialog();
      break;
      case 'ban':
      window.open('/index.php?action=moderate&do=banuser2&userid=' + $(el).attr('data-userid'),'banuser' + attr('data-userid'));
      break;
    }
  });

  $('.messageLine .messageText').contextMenu({
    menu: 'messageMenu'
  },
  function(action, el) {
    switch(action) {
      case 'delete':
      break;
    }
  });

  $('.room').contextMenu({
    menu: 'roomMenu'
  },
  function(action, el) {
    switch(action) {
      case 'delete':
      break;
      case 'edit':
      ajaxDialogue('/content/editRoom.php?roomid=' + $(el).attr('data-roomid'),'Edit Room','editRoomDialogue',1000);
      break;
    }
  });

  $('.username').ezpz_tooltip({
    contentId: 'tooltext',
    beforeShow: function(content,el) {
      var thisid = $(el).attr('data-userid');

      if (thisid != $('#tooltext').attr('data-lastuserid')) {
        $('#tooltext').attr('data-lastuserid',thisid)
        $.get("ajax/fim-usernameHover.php?userid=" + thisid, function(html) {
           content.html(html);
        });
      }
    }
  });
}