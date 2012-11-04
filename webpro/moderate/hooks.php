<?php
/* FreezeMessenger Copyright © 2012 Joseph Todd Parsons

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
  if ($user['adminDefs']['modHooks']) {

    switch ($_GET['do2']) {
      case false:
      case 'view':
      $hooks2 = $database->select(array(
        "{$sqlPrefix}hooks" => "hookId, hookName, code, state",
      ));
      $hooks2 = $hooks2->getAsArray(true);

      foreach ($hooks2 AS $hook) {
        $hook['code'] = nl2br(htmlentities($hook['code']));

        $rows .= "<tr><td>$hook[hookName]</td><td>$hook[code]</td><td><a href=\"./moderate.php?do=hooks&do2=edit&hookId=$hook[hookId]\">Edit</a> | <a href=\"./moderate.php?do=hooks&do2=state&hookId=$hook[hookId]\">" . ($hook['state'] == 'on' ? 'Deactivate' : 'Activate') . "</a></td></tr>";
      }

      echo container('Hooks','<table class="page rowHover" border="1">
  <thead>
    <tr class="hrow ui-widget-header">
      <td>Hook</td>
      <td>Current Value</td>
      <td>Actions</td>
    </tr>
  </thead>
  <tbody>
' . $rows . '
  </tbody>
</table>');
      break;

      case 'edit':
      $hookID = intval($_GET['hookId']);

      $hook = dbRows("SELECT * FROM {$sqlPrefix}hooks WHERE id = $hookID");

      echo container("Edit Hook '$hook[name]'","<form action=\"./moderate.php?do=hooks&do2=edit2&hookId=$hook[id]\" method=\"post\">
  <label for=\"text\">New Value:</label><br />
  <textarea name=\"text\" id=\"textClike\" style=\"width: 100%; height: 300px;\">$hook[code]</textarea><br /><br />

  <button type=\"submit\">Update</button>
</form>");
      break;

      case 'edit2':
      $hookId = $_GET['hookId'];
      $text = $_POST['text'];

      $database->update(array(
        'code' => $text,
      ),
      "{$sqlPrefix}phrases",
      array(
        'hookId' => (int) $hookId,
      ));

      $database->modLog('hookEdit',$hookID);

      echo container('Updated','The hook has been updated.<br /><br /><form action="moderate.php?do=hooks" method="POST"><button type="submit">Return</button></form>');
      break;
    }
  }
  else {
    echo 'You do not have permission to modify hooks.';
  }
}
?>