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
  $request = fim_sanitizeGPC('r', array(
    'templateName' => array(
      'cast' => 'string',
    ),

    'data' => array(
      'cast' => 'string',
    ),

    'do2' => array(
      'cast' => 'string',
    ),
  ));

  $json = json_decode(file_get_contents('client/data/templates.json'), true);

  if ($user['adminDefs']['modTemplates']) {
    switch ($request['do2']) {
      case 'view':
      case false:
      foreach (array_keys($json) AS $template) {
        $rows .= "<tr><td>$template</td><td align=\"center\"><a href=\"./moderate.php?do=templates&do2=edit&templateName=$template\"><img src=\"./images/document-edit.png\" /></td></tr>";
      }

      echo container('Edit Templates','<table class="page rowHover" border="1">
  <thead>
    <tr class="hrow ui-widget-header">
      <td width="20%">Template</td>
      <td width="20%">Actions</td>
    </tr>
  </thead>
  <tbody>
' . $rows . '
  </tbody>
</table>');
      break;

      case 'edit':
      $template = $json[$request['templateName']];
      $template = str_replace(array('<', '>'), array('&lt;', '&gt;'), formatXmlString($template));
      $template = preg_replace('/template\.([A-Z])/e', '"template." . lcfirst($1)', $template);

      echo container("Edit Template \"$request[templateName]\"","<form action=\"./moderate.php?do=templates&do2=edit2&templateName=$request[templateName]\" method=\"post\">

  <label for=\"data\">New Value:</label><br />
  <textarea name=\"data\" id=\"textXml\" style=\"width: 100%; height: 300px;\">$template</textarea><br /><br />

  <button type=\"submit\">Update</button>
</form>");
      break;

      case 'edit2':
      $template = $request['data'];
      $template = str_replace(array("\r","\n","\r\n"), '', $template); // Remove new lines (required for JSON).
      $template = preg_replace("/\>(\ +)/", ">", $template); // Remove extra space between  (looks better).
      $json[$request['templateName']] = $template; // Update the JSON object with the new template data.

      file_put_contents('client/data/templates.json', json_encode($json)) or die('Unable to write'); // Send the new JSON data to the server.

      $database->modLog('templateEdit',$template['templateName'] . '-' . $template['interfaceId']);
      $database->fullLog('templateEdit',array('template' => $template));

      echo container('Template "' . $request['templateName'] . '" Updated','The template has been updated.<br /><br /><form action="./moderate.php?do=templates&do2=view" method="POST"><button type="submit">Return</button></form>');
      break;
    }
  }
  else {
    echo 'You do not have permission to modify templates.';
  }
}
?>
