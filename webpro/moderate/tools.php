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
        switch($_GET['tool'] ?? false) {
            case false:
                echo container('Please Choose a Tool','<ul>
                    <li><a href="./moderate.php?do=tools&tool=viewCache">View Cache</a> - This shows all cache entries available to the current server. APC caches for other servers will not be displayed.</li>
                    <li><a href="./moderate.php?do=tools&tool=clearCache">Clear Cache</a> - This clears all cache entries available to the current server. APC caches for other servers will not be cleared.</li>
                    <li><a href="./moderate.php?do=tools&tool=updateDatabaseSchema">Perform Database Schema Update</a> - This will update your database schema to correspond with install/dbSchema.xml. It is primarily intended for development purposes (as updating to a new version should come with its own custom schema update procedure), but you also have the option of manually tweaking the DB schema in dbSchema.xml and then using this tool to have the changes take effect. Note that the tool is currently not fully tested, and will currently take a while, as it does not check if a column has changed before running the update command.</li>
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

                foreach ($generalCache->methods AS $method) {
                    echo container('Cache Dump: ' . $method, '<pre>' . print_r($generalCache->dump($method), true) . '</pre>');
                }

                echo '<br />';
                echo container('Contents of fim_fimUser_1 Cache Entry', '<pre>' . print_r($generalCache->get('fim_fimUser_1'), true) . '</pre>');
                echo '<br />';
                echo container('Contents of fim_activeCensorWords_1 Cache Entry', '<pre>' . print_r($generalCache->get('fim_activeCensorWords_1'), true) . '</pre>');
                break;

            case 'clearCache':
                $config->disableDestruction();

                if ($generalCache->clearAll())
                    echo container('Cache Cleared','The cache has been cleared.<br /><br /><form action="moderate.php?do=tools" method="POST"><button type="submit">Return to Tools</button></form>');

                else
                    echo container('Failed','The clear was unsuccessful.<form action="moderate.php?do=tools" method="POST"><button type="submit">Return to Tools</button></form>');
                break;

            case 'updateDatabaseSchema':
                require(__DIR__ . '/../../functions/Xml2Array.php');
                $showTable = (array) $database->getTablesAsArray();
                $showTables = array_map('strtolower', $showTable); // ...In Windows, table names may not be returned as entered (uppercase letters usually become lowercase), so this is the most efficient work-around I could come up with.

                $showColumns = $database->getTableColumnsAsArray();
                $showColumns = array_change_key_case($showColumns, CASE_LOWER); // How is this even a function?
                array_walk($showColumns, function(&$a) { $a = array_map('strtolower', $a); });

                $xmlData = new Xml2Array(file_get_contents('../install/dbSchema.xml')); // Get the XML Data from the dbSchema.xml file, and feed it to the Xml2Array class
                $xmlData = $xmlData->getAsArray(); // Get the XML data as an array
                $xmlData = $xmlData['dbSchema']; // Get the contents of the root node

                foreach ($xmlData['database'][0]['table'] AS $table) { // Run through each table from the XML
                    $tableComment = $table['@comment'] ?? '';
                    $tablePartition = $table['@partitionBy'] ?? false;
                    $tableType = $table['@type'] ?? 'general';

                    $tablePartitions = $table['@hardPartitions'] ?? 1;

                    for ($i = 0; $i < $tablePartitions; $i++) {
                        $tableName = $database->sqlPrefix . $table['@name'] . ($tablePartitions > 1 ? '__part' . $i : '');

                        $tableColumns = [];
                        $tableIndexes = [];

                        foreach ($table['column'] AS $column) {
                            $tableColumns[$column['@name']] = [
                                'type'          => $column['@type'],
                                'autoincrement' => $column['@autoincrement'] ?? false,
                                'restrict'      => (isset($column['@restrict']) ? explode(',', $column['@restrict']) : false),
                                'maxlen'        => $column['@maxlen'] ?? false,
                                'bits'          => $column['@bits'] ?? false,
                                'default'       => $column['@default'] ?? null,
                                'comment'       => $column['@comment'] ?? false,
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
                            echo 'Update: ' . $tableName . ': ' . $database->alterTable($tableName, $tableComment, $tableType) . '<br />';

                            foreach ($tableColumns AS $name => $column) {
                                if (in_array(strtolower($name), $showColumns[strtolower($tableName)])) {
                                    echo 'Update: ' . $tableName . ',' . $name . ': ' . $database->alterTableColumns($tableName, [$name => $column], $tableType) . '<br />';
                                }
                                else {
                                    echo 'Create: ' . $tableName . ',' . $name . ': ' . $database->createTableColumns($tableName, [$name => $column], $tableType) . '<br />';
                                }
                            }
                        }
                        else {
                            if (!$database->createTable($tableName, $tableComment, $tableType, $tableColumns, $tableIndexes, $tablePartition)) {
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