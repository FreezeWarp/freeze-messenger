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

/**
 * Admin Control Panel: Log Tools
 * This script can list the short and long moderation logs, as well as the full access log and query log, if enabled.
 * To use this script, users must have modPrivs permissions.
 */

if (!defined('WEBPRO_INMOD')) {
    die();
}
else {
    $request = \Fim\Utilities::sanitizeGPC('r', array(
        'log' => array(
            'cast' => 'string',
            'default' => 'mod',
            'valid' => ['mod', 'access', 'full', 'query']
        ),
        'actions' => array(
            'cast' => 'list',
        ),
        'ips' => array(
            'cast' => 'list',
        ),
        'userIds' => array(
            'cast' => 'list',
            'filter' => 'int',
        ),
        'page' => array(
            'cast' => 'int',
            'default' => 0
        )
    ));

    if ($user->hasPriv('modPrivs')) { // TODO: modLogs 
        echo '<center><a href="./index.php?do=log&log=mod">Mod Log</a> <a href="./index.php?do=log&log=full">Full Log</a> <a href="./index.php?do=log&log=access">Access Log</a> <a href="./index.php?do=log&log=query">Query Log</a></center>'; // TODO 

        if ($request['log'] === 'query') {
            $file = fopen(\Fim\Config::$logQueriesFile, 'r') or die(container("Error", "Could not open query log file."));

            fseek($file, $request['page'] * 250000 - ($request['page'] > 0 ? 5000 : 0));
            echo "<textarea style=\"width: 100%; height: 500px;\"'>" . fread($file, 255000) . "</textarea>";

            $numPages = (int) (filesize(\Fim\Config::$logQueriesFile) / 250000);
            if ($request['page'] > 0) echo "<a href=\"./index.php?do=log&log=query&page=" . ($request['page'] - 1) . "\">Previous Page</a> | ";
            if ($request['page'] < $numPages) echo "<a href=\"./index.php?do=log&log=query&page=" . ($request['page'] + 1) . "\">Next Page</a> | ";
            echo "Jump to Page: <form style=\"display: inline\"><select id=\"page\" onchange=\"window.location = './index.php?do=log&log=query&page=' + jQuery('#page option:selected').val();\">";
            for ($page = 0; $page <= $numPages; $page++) {
                echo "<option value=\"$page\"" . ($request['page'] == $page ? ' selected' : '') . ">$page</option>";
            }
            echo '</select>';
        }
        else {
            $logsResult = \Fim\Database::instance()->getModLog([
                'actions' => $request['actions'] ?? [],
                'ips' => $request['ips'] ?? [],
                'log' => $request['log'],
                'userIds' => $request['userIds'] ?? [],
            ], array('time' => 'desc'), 100, $request['page']);
            $logs = $logsResult->getAsArray(true);

            $rows = "";
            foreach ($logs AS $log) {
                $rows .= "<tr> 
                    <td><a href=\"./index.php?do=log&log={$request['log']}&userIds[]={$log['userId']}\">{$log['userId']} ({$log['userName']})</a></td> 
                    <td><a href=\"./index.php?do=log&log={$request['log']}&ips[]={$log['ip']}\">{$log['ip']}</a></td> 
                    <td>" . date('r', $log['time']) . "</td>"
                    . ($request['log'] === 'access' ? "<td>{$log['executionTime']}</td>" : '')
                    . ($request['log'] === 'full' ? '<td><pre style="white-space: pre-wrap; font-size: .8em;">' . json_encode(json_decode($log['server']), JSON_PRETTY_PRINT) . '</pre></td>' : '') . " 
                    <td><a href=\"./index.php?do=log&log={$request['log']}&actions[]={$log['action']}\">{$log['action']}</a></td> 
                    <td><pre style=\"white-space: pre-wrap; font-size: .8em;\">" . ($request['log'] !== 'mod' ? json_encode(json_decode($log['data']), JSON_PRETTY_PRINT) : $log['data']) . "</pre></td> 
                </tr>";
            }

            echo container('Mod Log',
                ($request['page'] > 0 ? "<div style=\"float: left;\"><a href=\"./index.php?do=log&" . http_build_query(array_merge($request, ['page' => $request['page'] - 1])) . "\">Previous Page</a></div>" : "") .
                ($logsResult->paginated ? "<div style=\"float: right;\"><a href=\"./index.php?do=log&" . http_build_query(array_merge($request, ['page' => $request['page'] + 1])) . "\">Next Page</a></div>" : "") . '<table class="table table-sm table-striped"> 
      <thead class="thead-light"> 
        <tr> 
          <th>User ID (Username)</th> 
          <th>IP</th> 
          <th>Time</th>'
                . ($request['log'] === 'access' ? "<th>Execution Time</th>" : "")
                . ($request['log'] === 'full' ? '<th>Server Data</th>' : '') . ' 
          <th>Action</th> 
          <th>Data</th> 
        </tr> 
      </thead> 
      <tbody> 
    ' . $rows . ' 
      </tbody> 
    </table>');
        }
    }
    else {
        echo 'You do not have permission to view the logs.';
    }
}