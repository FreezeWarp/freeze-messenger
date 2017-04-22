<?php
/* FreezeMessenger Copyright © 2017 Joseph Todd Parsons

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

if (!defined('WEBPRO_INMOD')) {
  die();
}
else {
  echo container('Welcome','Welcome to the FreezeMessenger control panel. Here you, as one of our well-served grandé and spectacular administrative staff, can perform every task needed to you during normal operation. Still, be careful: you can mess things up here!<br /><br />

To perform an action, click a link on the sidebar. Further instructions can be found in the documentation.<br /><br />

<strong>Note</strong>: All users can sign into this control panel to see copyright information.<br /><br />
<table class="page ui-widget">
  <thead>
    <tr class="ui-widget-header">
        <th colspan="2">About FreezeMessenger</td>
    </tr>
  </thead>
  <tbody>
    <tr>
      <td>Active User</td>
      <td>' . $user['userName'] . '</td>
    </tr>
    <tr>
      <td>FIM Release</td>
      <td>FIMv3.0 ("Bad Wolf")</td>
    </tr>
  </tbody>
</table>');
}
?>