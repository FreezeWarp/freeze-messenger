function changeTitle(room,title) {
  $.ajax({
    url: '/ajax/setTitle.php',
    type: 'POST',
    contentType: 'application/x-www-form-urlencoded;charset=UTF-8',
    cache: false,
    data: 'room=' + room + '&title=' + escape(title),
    success: function(html) {
      $('#title' + room).html('<a href="javascript:void(0);" onclick="$(\'#title' + room + '\').html(\'<form action=&quot;#&quot; onsubmit=&quot;var title = $(\\\'#input' + room + '\\\').val(); changeTitle(' + room + ',title); return false;&quot; style=&quot;display: inline;&quot;><input type=&quot;text&quot; name=&quot;newTitle&quot; style=&quot;width: 300px&quot; value=&quot;' + escape(title) + '&quot; id=&quot;input' + room + '&quot; /></form>\'); $(this).hide();"><img src="/images/edit-rename.png" class="standard" alt="Configure" />' + title);
    },
    error: function() { alert('Could not update the topic.'); }
  });
}