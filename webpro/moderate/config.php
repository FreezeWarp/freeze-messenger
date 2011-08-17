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
      'directive' => array(
        'context' => array(
          'type' => 'int',
        ),
      ),
    ),

    'post' => array(
      'value' => array(
        'context' => array(
          'type' => 'string',
        ),
      ),

      'type' => array(
        'context' => array(
          'type' => 'string',
        ),
        'valid' => array('integer', 'bool', 'string', 'float', 'array'),
      ),
    ),
  ));

  if ($user['adminDefs']['modCore']) {
    switch ($_GET['do2']) {
      case 'view':
      case false:
      $config2s2 = $database->select(array(
        "{$sqlPrefix}configuration" => "directive, value, type",
      ));
      $config2s2 = $config2s2->getAsArray(true);

      foreach ($config2s2 AS $config2) {
        $rows .= "<tr><td>$config2[directive]</td><td>$config2[searchRegex]</td><td>$config2[replacement]</td><td><a href=\"./moderate.php?do=config&do2=edit&directive=$config2[directive]\">Edit</td></tr>";
      }

      echo container('Configurations<a href="./moderate.php?do=config&do2=edit"><span class="ui-icon ui-icon-plusthick" style="float: right;" ></span></a>','<table class="page rowHover" border="1">
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
        $config2 = $database->getConfiguration($request['directive']);
        $title = 'Edit Configuration Value "' . $config2['directive'] . '"';
      }
      else {
        $config2 = array(
          'directive' => 0,
          'value' => '',
          'type' => 'string',
        );

        $title = 'Create New Configuration Value';
      }

      $selectBlock = fimHtml_buildSelect('type', array(
        'bool' => 'Boolean',
        'integer' => 'Integer',
        'float' => 'Float',
        'string' => 'String',
        'array' => 'Array',
      ), $config2['type']);

      echo container($title, '<form action="./moderate.php?do=config&do2=edit2" method="post">
  <table border="1" class="ui-widget page">
    <tr>
      <td>Directive:</td>
      <td>' . ($config2['directive'] ? $config['directive'] : '<input type="text" name="directive" value="' . $config2['directive'] . '" />') . '</td>
    </tr>
    <tr>
      <td>Type:</td>
      <td>
        ' . $selectBlock . '<br />
        <small>This is the type of the variable when interpretted. It should not normally be altered.</small>
      </td>
    </tr>
    <tr>
      <td>Value:</td>
      <td>
        <input type="text" name="value" value="' . $config2['value'] . '" /><br />
        <small>Note that for array types, values should be entered using comma-seperated notation. You can escape commas in entries by prepending a "\".</small>
      </td>
    </tr>
  </table>

  <button type="submit">Submit</button>
  <button type="reset">Reset</button>
  <input type="hidden" name="directive" value="' . $config2['directive'] . '" />
</form>');
      break;

      case 'edit2':
      $config2 = $database->getConfiguration($request['directive']);

      if ($request['directive']) {
        $database->modLog('deleteCensorWord', $config2['wordId']);
        $database->fullLog('deleteCensorWord', array('config' => $config2));

        $database->update("{$sqlPrefix}config", array(
          'type' => $request['type'],
          'value' => $request['value'],
        ), array(
          'directive' => $request['directive'],
        ));

        echo container('Configuration Updated','The configuration has been updated.<br /><br /><form method="post" action="moderate.php?do=config"><button type="submit">Return to Viewing Lists</button></form>');
      }
      else {
        $config2 = array(
          'directive' => $request['directive'],
          'type' => $request['type'],
          'value' => $request['value'],
        );

        $database->insert("{$sqlPrefix}config", $config2);
        $config2['directive'] = $database->insertId;

        $database->modLog('createConfigDirective', $config2['directive']);
        $database->fullLog('createConfigDirective', array('config' => $config2));

        echo container('Configuration Added','The config has been added.<br /><br /><form method="post" action="moderate.php?do=config"><button type="submit">Return to Viewing Lists</button></form>');
      }
      break;

      case 'delete':
      $config2 = $database->getConfiguration($request['directive']);

      if ($config2) {
        $database->modLog('deleteCensorWord', $config2['wordId']);
        $database->fullLog('deleteCensorWord', array('config' => $config2));

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