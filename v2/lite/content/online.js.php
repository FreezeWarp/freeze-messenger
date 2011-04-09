function updateOnline() {
  $.ajax({
    url: '/ajax/online.php',
    type: 'GET',
    timeout: 2400,
    cache: false,
    success: function(html) {
      if (html) $('#onlineUsers').html(html);
    },
    error: function() {
      $('#onlineUsers').html('Refresh Failed');
    },
  });
}

var timer2 = setInterval(updateOnline,2500);