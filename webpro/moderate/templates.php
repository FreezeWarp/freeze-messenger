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

if (!defined('WEBPRO_INMOD')) {
  die();
}
else {
  $request = fim_sanitizeGPC(array(
    'request' => array(
      'templateName' => array(
        'context' => array(
          'type' => 'string',
        ),
      ),

      'interfaceId' => array(
        'context' => array(
          'type' => 'int',
        ),
      ),
    ),

    'post' => array(
      'data' => array(
        'context' => array(
          'type' => 'string',
        ),
      ),

      'vars' => array(
        'context' => array(
          'type' => 'string',
        ),
      ),
    ),
  ));

  if ($user['adminDefs']['modTemplates']) {
    switch ($_GET['do2']) {
      case false:
      case 'interface':
      $interfaces = $database->select(array(
        "{$sqlPrefix}interfaces" => "interfaceId, interfaceName",
      ));
      $interfaces = $interfaces->getAsArray(true);

      $interfaceLinks = '';

      foreach ($interfaces AS $interface) {
        $interfaceLinks .= "<a href=\"moderate.php?do=templates&do2=view&interfaceId={$interface['interfaceId']}\">{$interface['interfaceName']}</a><br />";
      }

      echo container('Choose an Interface', $interfaceLinks);
      break;

      case 'view':
      $templates2 = $database->select(array(
        "{$sqlPrefix}templates" => "templateName, templateId, interfaceId, data, vars",
      ), array(
        'both' => array(
          array(
            'type' => 'e',
            'left' => array(
              'type' => 'column',
              'value' => 'interfaceId',
            ),
            'right' => array(
              'type' => 'int',
              'value' => (int) $request['interfaceId'],
            ),
          ),
        ),
      ));
      $templates2 = $templates2->getAsArray(true);

      foreach ($templates2 AS $template) {
        $rows .= "<tr><td>$template[templateName]</td><td align=\"center\"><a href=\"./moderate.php?do=templates&do2=edit&templateName=$template[templateName]&interfaceId=$template[interfaceId]\"><img src=\"./images/document-edit.png\" /></td></tr>";
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
      $template = $database->getTemplate($request['templateName'], $request['interfaceId']);

      echo container("Edit Template \"$template[templateName]\"","<form action=\"./moderate.php?do=templates&do2=edit2&templateName=$template[templateName]&interfaceId=$template[interfaceId]\" method=\"post\">
<label for=\"vars\">Variables:</label><br />
<textarea name=\"vars\" id=\"vars\" style=\"width: 100%;\">$template[vars]</textarea><br /><br />

<label for=\"data\">New Value:</label><br />
<textarea name=\"data\" id=\"textXml\" style=\"width: 100%; height: 300px;\">$template[data]</textarea><br /><br />

<button type=\"submit\">Update</button>
</form>");
      break;

      case 'edit2':
      $template = $database->getTemplate($request['templateName'], $request['interfaceId']);

      $database->update("{$sqlPrefix}templates", array(
        'data' => $request['data'],
        'vars' => $request['vars'],
      ), array(
        'templateName' => $template['templateName'],
        'interfaceId' => $template['interfaceId'],
      ));

      $database->modLog('templateEdit',$template['templateName'] . '-' . $template['interfaceId']);
      $database->fullLog('templateEdit',array('template' => $template));

      echo container('Template "' . $template['templateName'] . '" Updated','The template has been updated.<br /><br /><form action="./moderate.php?do=templates&do2=view&interfaceId=' . $request['interfaceId'] . '" method="POST"><button type="submit">Return</button></form>');
      break;
    }
  }
  else {
    echo 'You do not have permission to modify templates.';
  }
}
?>