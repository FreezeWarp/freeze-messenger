<?php
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

$reqHooks = true;
$reqPhrases = true;
require_once('../global.php');
?>

<script type="text/javascript">function updateOnline() { $.ajax({ url: '/ajax/fim-online.php', type: 'GET', timeout: 2400, cache: false, success: function(html) { if (html) $('#onlineUsers').html(html); }, error: function() { $('#onlineUsers').html('Refresh Failed'); }, }); } var timer2 = setInterval(updateOnline,2500);</script>
<?php
echo '<table class="page">
  <thead>
    <tr class="hrow">
      <td>' . $phrases['onlineUsername'] . '</td>
      <td>' . $phrases['onlineRoom'] . '</td>
    </tr>
  </thead>

  <tbody id="onlineUsers">
    <tr>
      <td colspan="2">' . $phrases['onlineLoading'] . '</td>
    </tr>
  </tbody>
</table>';
?>