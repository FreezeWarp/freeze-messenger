<?php
/* FreezeMessenger Copyright © 2014 Joseph Todd Parsons

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
    'directive' => array(
      'cast' => 'string',
    ),

    'value' => array(
      'cast' => 'string',
    ),

    'type' => array(
      'cast' => 'string',
      'valid' => array('integer', 'bool', 'string', 'float', 'array'),
    ),
  ));

  if ($user['adminDefs']['modCore']) {
    switch ($_GET['do2']) {
      case 'view':
      case false:
      $config3 = $database->getConfigurations()->getAsArray(true);

      foreach ($config3 AS $config2) {
        if ($config2['type'] == 'array') $config2['value'] = str_replace(',', ', ', $config2['value']);

        $rows .= "<tr><td>$config2[directive]</td><td>$config2[type]</td><td>$config2[value]</td><td><a href=\"./moderate.php?do=config&do2=edit&directive=$config2[directive]\"><img src=\"./images/document-edit.png\" /></a></td></tr>";
      }

      echo container('Configurations<a href="./moderate.php?do=config&do2=edit"><img src="./images/document-new.png" style="float: right;" /></a>','<table class="page rowHover" border="1">
  <thead>
    <tr class="hrow ui-widget-header">
      <td>Directive</td>
      <td>Type</td>
      <td>Value</td>
      <td>Actions</td>
    </tr>
  </thead>
  <tbody>
' . $rows . '
  </tbody>
</table>');
      break;

      case 'edit':
      if ($request['directive']) {
        $config2 = $database->getConfigurations(array('directives' => array($request['directive'])))->getAsArray(false);
        $title = 'Edit Configuration Value "' . $config2['directive'] . '"';

        switch($config2['type']) {
          case 'bool':
          $valueBlock = fimHtml_buildSelect('value', array(
            'true' => 'true',
            'false' => 'false',
          ), $config2['value']);
          break;

          case 'integer':
          case 'float':
          $valueBlock = '<input type="number" name="value" required="required" value="' . $config2['value'] . '" />';
          break;

          default:
          $valueBlock = '<input type="text" name="value" value="' . str_replace('"', '&quot;', $config2['value']) . '" />';
        }
      }
      else {
        $config2 = array(
          'directive' => 0,
          'value' => '',
          'type' => 'string',
        );

        $title = 'Create New Configuration Value';
      }

      echo container($title, '<form action="./moderate.php?do=config&do2=edit2" method="post">
  <table border="1" class="ui-widget page">
    <tr>
      <td>Directive:</td>
      <td>' . ($config2['directive'] ? '<input type="hidden" name="directive" value="' . $config2['directive'] . '" />' . $config2['directive'] : '<input type="text" name="directive" value="' . $config2['directive'] . '" />') . '</td>
    </tr>
    <tr>
      <td>Type:</td>
      <td>
        ' . fimHtml_buildSelect('type', array(
          'bool' => 'Boolean',
          'integer' => 'Integer',
          'float' => 'Float',
          'string' => 'String',
          'array' => 'Array',
        ), $config2['type']) . '<br />
        <small>This is the type of the variable when interpretted. It should not normally be altered.</small>
      </td>
    </tr>
    <tr>
      <td>Value:</td>
      <td>
        ' . $valueBlock . '<br />
        <small>Note that for array types, values should be entered using comma-seperated notation. You can escape commas in entries by prepending a "\".</small>
      </td>
    </tr>
  </table>

  <button type="submit">Submit</button>
  <button type="reset">Reset</button>
</form>');
      break;

      case 'edit2':
      if ($request['directive']) {
        $config2 = $database->getConfiguration($request['directive']);

        $database->modLog('editConfigDirective', $config2['directive']);
        $database->fullLog('editConfigDirective', array('config' => $config2));

        $database->update("{$sqlPrefix}configuration", array(
          'type' => $request['type'],
          'value' => $request['value'],
        ), array(
          'directive' => $request['directive'],
        ));

        echo container('Configuration Updated','The configuration has been updated. Note that certain settings do not take effect retroactively (e.g. "userRoomCreation" does not change the setting for existing users). <br /><br /><form method="post" action="moderate.php?do=config"><button type="submit">Return to Viewing Lists</button></form>');
      }
      else {
        $config2 = array(
          'directive' => $request['directive'],
          'type' => $request['type'],
          'value' => $request['value'],
        );

        $database->insert("{$sqlPrefix}configuration", $config2);
        $config2['directive'] = $database->insertId;

        $database->modLog('createConfigDirective', $config2['directive']);
        $database->fullLog('createConfigDirective', array('config' => $config2));

        echo container('Configuration Added','The config has been added.<br /><br /><form method="post" action="moderate.php?do=config"><button type="submit">Return to Viewing Lists</button></form>');
      }
      break;

      case 'delete':
      $config2 = $database->getConfiguration($request['directive']);

      if ($config2) {
        $database->modLog('deleteConfigDirective', $config2['directive']);
        $database->fullLog('deleteConfigDirective', array('config' => $config2));

        $database->delete("{$sqlPrefix}config", array(
          'directive' => $request['directive'],
        ));

        echo container('Configuration Deleted','The config entry has been deleted.<br /><br /><form method="post" action="moderate.php?do=config"><button type="submit">Return to Viewing Configuration</button></form>');
      }
      else {
        echo container('Configuration Not Found','The config specified was not found.<br /><br /><form method="post" action="moderate.php?do=config"><button type="submit">Return to Viewing Configuration</button></form>');
      }
      break;
    }
  }
  else {
    echo 'You do not have permission to manage Configurations.';
  }
}
?>
