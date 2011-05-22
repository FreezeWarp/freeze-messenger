function updateOnline() {
  $.ajax({
    url: '/api/getAllActiveUsers.php',
    type: 'GET',
    timeout: 2400,
    cache: false,
    success: function(xml) {
      var data;
      $(xml).find('user').each(function() {
        var username = $(this).find('username').text();
        var userid = $(this).find('userid').text();
        var roomData = new Array();

        $(this).find('room').each(function() {
          var roomid = $(this).find('roomid').text();
          var roomname = $(this).find('roomname').text();
          roomData.push('<a href="/chat.php?room=' + roomid + '">' + roomname + '</a>');
        });
        roomData = roomData.join(', ');
        
        data += '<tr><td>' + username + '</td><td>' + roomData + '</td></tr>';
      });
      $('#onlineUsers').html(data);
    },
    error: function() {
      $('#onlineUsers').html('Refresh Failed');
    },
  });
}

var timer2 = setInterval(updateOnline,2500);