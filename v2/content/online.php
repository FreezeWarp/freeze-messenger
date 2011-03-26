<script type="text/javascript">function updateOnline() { $.ajax({ url: '/ajax/online.php', type: 'GET', timeout: 2400, cache: false, success: function(html) { if (html) $('#onlineUsers').html(html); }, error: function() { $('#onlineUsers').html('Refresh Failed'); }, }); } var timer2 = setInterval(updateOnline,2500);</script>
<?php
echo '<table class="page">
  <thead>
    <tr class="hrow">
      <td>Username</td>
      <td>Rooms</td>
    </tr>
  </thead>

  <tbody id="onlineUsers">
    <tr>
      <td colspan="2">Loading...</td>
    </tr>
  </tbody>
</table>';
?>