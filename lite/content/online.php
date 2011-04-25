<script src="/content/online.js.php"></script>
<?php
echo container('<h3>Active Users</h3>','Note: This list only applies to the chat. You can also see a list of active users for VictoryRoad, Victory Battles, and VRIM <a href="javascript:void(0);" onclick="newwindow=window.open(\'http://www.victoryroad.net/online.php?popup=true\',\'online\',\'height=500,width=700\'); if (window.focus) { newwindow.focus() }">here</a>.<br /><br />

<table class="page">
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
</table>');
?>