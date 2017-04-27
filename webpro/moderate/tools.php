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
        'tool' => array(
            'cast' => 'string'
        ),
    ));

    if ($user->hasPriv('modPrivs')) {
        switch($_GET['tool']) {
            case false:
                echo container('Please Choose a Tool','<ul>
                    <li><a href="./moderate.php?do=tools&tool=viewCache">View Cache</a></li>
                    <li><a href="./moderate.php?do=tools&tool=clearCache">Clear Cache</a></li>
                    <li><a href="./moderate.php?do=tools&tool=updateDatabaseSchema">Perform Database Schema Update</a></li>
                </ul>');
                break;

            case 'viewCache':
                foreach (array('fim_config') AS $cache) {
                    $formattedCache = '';

                    foreach ((array) $generalCache->get($cache) AS $key => $value) {
                        if (is_array($value)) {
                            $value = print_r($value, true);
                        }

                        $formattedCache .= '<tr><td>' . $key . '</td><td><pre>' . $value . '</pre></td></tr>';
                    }

                    echo container('Cache Entries: ' . $cache, '<table class="page ui-widget ui-widget-content" border="1">' . $formattedCache . '</table>');
                    echo '<br />';
                }

                echo container('All Cache Info', '<pre>' . print_r($generalCache->dump(), true) . '</pre>');
                echo '<br />';
                echo container('Contents of fim_fimUser_1 Cache Entry', '<pre>' . print_r($generalCache->get('fim_fimUser_1'), true) . '</pre>');
                echo '<br />';
                echo container('Contents of fim_activeCensorWords_1 Cache Entry', '<pre>' . print_r($generalCache->get('fim_activeCensorWords_1'), true) . '</pre>');
                break;

            case 'clearCache':
                if ($generalCache->clearAll())
                    echo container('Cache Cleared','The cache has been cleared.<br /><br /><form action="moderate.php?do=tools" method="POST"><button type="submit">Return to Tools</button></form>');

                else
                    echo container('Failed','The clear was unsuccessful.<form action="moderate.php?do=tools" method="POST"><button type="submit">Return to Tools</button></form>');
                break;

            case 'updateDatabaseSchema':
                require('../functions/xml.php');
                $showTable = (array) $database->getTablesAsArray();
                $showTables = array_map('strtolower', $showTable); // ...In Windows, table names may not be returned as entered (uppercase letters usually become lowercase), so this is the most efficient work-around I could come up with.

                $showColumns = $database->getTableColumnsAsArray();
                $showColumns = array_change_key_case($showColumns, CASE_LOWER); // How is this even a function?
                array_walk($showColumns, function(&$a) { $a = array_map('strtolower', $a); });

                $xmlData = new Xml2Array(file_get_contents('../install/dbSchema.xml')); // Get the XML Data from the dbSchema.xml file, and feed it to the Xml2Array class
                $xmlData = $xmlData->getAsArray(); // Get the XML data as an array
                $xmlData = $xmlData['dbSchema']; // Get the contents of the root node

                foreach ($xmlData['database'][0]['table'] AS $table) { // Run through each table from the XML
                    $tablePartitions = isset($table['@hardPartitions']) ? $table['@hardPartitions'] : 1;

                    for ($i = 0; $i < $tablePartitions; $i++) {
                        $tableType = isset($table['@type']) ? $table['@type'] : 'general';
                        $tableName = $database->sqlPrefix . $table['@name'] . ($tablePartitions > 1 ? '__part' . $i : '');
                        $tableComment = $table['@comment'];

                        $tableColumns = [];
                        $tableIndexes = [];


                        foreach ($table['column'] AS $column) {
                            $tableColumns[$column['@name']] = [
                                'type'          => $column['@type'],
                                'autoincrement' => (isset($column['@autoincrement']) ? $column['@autoincrement'] : false),
                                'restrict'      => (isset($column['@restrict']) ? explode(',', $column['@restrict']) : false),
                                'maxlen'        => (isset($column['@maxlen']) ? $column['@maxlen'] : false),
                                'bits'          => (isset($column['@bits']) ? $column['@bits'] : false),
                                'default'       => (isset($column['@default']) ? $column['@default'] : null),
                                'comment'       => (isset($column['@comment']) ? $column['@comment'] : false),
                            ];
                        }


                        if (isset($table['key'])) {
                            foreach ($table['key'] AS $key) {
                                $tableIndexes[$key['@name']] = [
                                    'type' => $key['@type'],
                                ];
                            }
                        }

                        if (in_array(strtolower($tableName), $showTables)) {
                            echo 'Update: ' . $tableName . ' (Unimplemented)<br />';

                            foreach ($tableColumns AS $name => $column) {
                                if (in_array(strtolower($name), $showColumns[strtolower($tableName)])) {
                                    echo 'Update: ' . $tableName . ',' . $name . ' (Unimplemented)<br />';
                                }
                                else {
                                    echo 'Create: ' . $tableName . ',' . $name . ' (Unimplemented)<br />';
                                }
                            }
                        }
                        else {
                            if (!$database->createTable($tableName, $tableComment, $tableType, $tableColumns, $tableIndexes, isset($table['@partitionBy']) ? $table['@partitionBy'] : false, isset($table['@hardPartitions']) ? $table['@hardPartitions'] : 1)) {
                                die("Could not create table.\n" . $database->getLastError());
                            }
                            else {
                                echo 'Created Table: ' . $tableName . '<br />';
                            }
                        }
                    }
                }
                break;
        }
    }
    else {
        echo 'You do not have permission to use the tools.';
    }
}
?>