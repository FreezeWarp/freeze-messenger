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
    $request = fim_sanitizeGPC('r', array(
        'log' => array(
            'cast' => 'string',
            'default' => 'mod',
            'valid' => ['mod', 'access', 'full']
        ),
        'actions' => array(
            'cast' => 'list',
            'default' => [],
        ),
        'ips' => array(
            'cast' => 'list',
            'default' => [],
        ),
        'userIds' => array(
            'cast' => 'list',
            'filter' => 'int',
            'default' => [],
        ),
        'page' => array(
            'cast' => 'int',
            'default' => 0
         )
    ));

    if ($user->hasPriv('modPrivs')) {
        echo '<center><a href="./moderate.php?do=log&log=mod">Mod Log</a> <a href="./moderate.php?do=log&log=full">Full Log</a> <a href="./moderate.php?do=log&log=access">Access Log</a></center>'; // TODO


        $logs = $database->getModLog([
            'actions' => $request['actions'],
            'ips' => $request['ips'],
            'log' => $request['log'],
            'userIds' => $request['userIds'],
         ], array('time' => 'desc'), 100, $request['page'])->getAsArray(true);

        foreach ($logs AS $log) {
            $rows .= "<tr>
                <td><a href=\"./moderate.php?do=log&log={$request['log']}&userIds[]={$log['userId']}\">{$log['userId']} ({$log['userName']})</a></td>
                <td><a href=\"./moderate.php?do=log&log={$request['log']}&ips[]={$log['ip']}\">{$log['ip']}</a></td>
                <td>" . date('r', $log['time']) . "</td>
                " . ($request['log'] === 'full' ? '<td><pre style="white-space: pre-wrap; font-size: .8em;">' . json_encode(json_decode($log['server']), JSON_PRETTY_PRINT) . '</pre></td>' : '') . "
                <td><a href=\"./moderate.php?do=log&log={$request['log']}&actions[]={$log['action']}\">{$log['action']}</a></td>
                <td><pre style=\"white-space: pre-wrap; font-size: .8em;\">" . ($request['log'] !== 'mod' ? json_encode(json_decode($log['data']), JSON_PRETTY_PRINT) :  $log['data']) . "</pre></td>
            </tr>";
        }

        echo container('Mod Log','<table class="page rowHover">
  <thead>
    <tr class="ui-widget-header">
      <td>User ID (Username)</td>
      <td>IP</td>
      <td>Time</td>
      ' . ($request['log'] === 'full' ? '<td>Server Data</td>' : '') . '
      <td>Action</td>
      <td>Data</td>
    </tr>
  </thead>
  <tbody>
' . $rows . '
  </tbody>
</table>');
    }
    else {
        echo 'You do not have permission to view the logs.';
    }
}
?>