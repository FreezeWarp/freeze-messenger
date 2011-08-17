<?php
if (!defined('WEBPRO_INMOD')) {
  die();
}
else {
  echo container('Welcome','<div style="text-align: center; font-size: 40px; font-weight: bold;">Welcome</div><br /><br />

Welcome to the FreezeMessenger control panel. Here you, as one of our well-served grandé and spectacular administrative staff, can perform every task needed to you during normal operation. Still, be careful: you can mess things up here!<br /><br />

To perform an action, click a link on the sidebar. Further instructions can be found in the documentation.<br /><br />
<table class="page ui-widget" border="1">
  <tr>
    <td>System Load Averages</td>
    <td>' . (function_exists('sys_getloadavg') ? print_r(sys_getloadavg(), true) : '--') . '</td>
  </tr>
  <tr>
    <td>Active User</td>
    <td>' . $user['userName'] . '</td>
  </tr>
  <tr>
    <td>FIM Release</td>
    <td>FIMv3.0 ("Bad Wolf")</td>
</table>');
}
?>