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
 * Admin Control Panel: Tools
 * This script houses the view and clear cache tools, as well as the "update database schema" tool.
 * To use this script, users must have modPrivs permissions.
 */

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
                    <li><a href="./index.php?do=tools&tool=viewCache">View Cache</a> - This shows all cache entries available to the current server. APC caches for other servers will not be displayed.</li>
                    <li><a href="./index.php?do=tools&tool=clearCache">Clear Cache</a> - This clears all cache entries available to the current server. APC caches for other servers will not be cleared.</li>
                    <li><a href="./index.php?do=tools&tool=updateDatabaseSchema">Perform Database Schema Update</a> - This will update your database schema to correspond with install/dbSchema.xml. It is primarily intended for development purposes (as updating to a new version should come with its own custom schema update procedure), but you also have the option of manually tweaking the DB schema in dbSchema.xml and then using this tool to have the changes take effect. Note that the tool is currently not fully tested, and will currently take a while, as it does not check if a column has changed before running the update command.</li>
                </ul>');
                break;

            case 'viewCache':
                foreach (array(\Fim\Cache::CONFIG_KEY, \Fim\Cache::EMOTICON_KEY, 'fim_fimRoom_1', 'fim_fimUser_1') AS $cache) {
                    $formattedCache = '';

                    foreach ((array) \Cache\CacheFactory::get($cache) AS $key => $value) {
                        if (is_array($value)) {
                            $value = print_r($value, true);
                        }

                        $formattedCache .= '<tr><td>' . $key . '</td><td><pre>' . $value . '</pre></td></tr>';
                    }

                    echo container('Cache Entries: ' . $cache, '<table class="table table-bordered table-striped table-sm">' . $formattedCache . '</table>');
                    echo '<br />';
                }

                foreach (\Cache\CacheFactory::$methods AS $method) {
                    echo container('Cache Dump: ' . get_class($method), '<pre>' . print_r(\Cache\CacheFactory::dump($method), true) . '</pre>');
                }

                echo '<br />';
                echo container('Contents of \Fim\Config', '<pre>' . print_r((new ReflectionClass('\Fim\Config'))->getStaticProperties(), true) . '</pre>');
                echo '<br />';
                echo container('Contents of roomPermissionsCache Table', '<table class="table table-bordered table-striped table-sm"><tr><th>Room ID</th><th>User ID</th><th>Permissions</th><th>Expires</th></tr>' . \Fim\Database::instance()->select([\Fim\Database::$sqlPrefix . 'roomPermissionsCache' => 'roomId, userId, permissions, expires'])->getAsTemplate('<tr><td>$roomId</td><td>$userId</td><td>$permissions</td><td>$expires</td></tr>') . '</table>');
                echo '<br />';
                echo container('Contents of counters Table', '<table class="table table-bordered table-striped table-sm"><tr><th>Name</th><th>Value</th></tr>' . \Fim\Database::instance()->select([\Fim\Database::$sqlPrefix . 'counters' => 'counterName, counterValue'])->getAsTemplate('<tr><td>$counterName</td><td>$counterValue</td></tr>') . '</table>');
                break;

            case 'clearCache':
                if (\Cache\CacheFactory::clearAll())
                    echo container('Cache Cleared','The cache has been cleared.<br /><br /><form action="index.php?do=tools" method="POST"><button type="submit">Return to Tools</button></form>');

                else
                    echo container('Failed','The clear was unsuccessful.<form action="index.php?do=tools" method="POST"><button type="submit">Return to Tools</button></form>');
                break;

            case 'updateDatabaseSchema':
                require(__DIR__ . '/../../functions/Xml2Array.php');
                $showTables = \Fim\Database::instance()->getTablesAsArray();
                $showColumns = \Fim\Database::instance()->getTableColumnsAsArray();
                set_time_limit(0);

                $xmlData = new Xml2Array(file_get_contents('../install/dbSchema.xml')); // Get the XML Data from the dbSchema.xml file, and feed it to the Xml2Array class
                $xmlData = $xmlData->getAsArray(); // Get the XML data as an array
                $xmlData = $xmlData['dbSchema']; // Get the contents of the root node

                foreach ($xmlData['database'][0]['table'] AS $table) { // Run through each table from the XML
                    $tableComment = $table['@comment'] ?? '';
                    $tablePartition = $table['@partitionBy'] ?? false;
                    $tableType = $table['@type'] ?? 'general';

                    $tablePartitions = $table['@hardPartitions'] ?? 1;

                    for ($i = 0; $i < $tablePartitions; $i++) {
                        $tableName = \Fim\Database::$sqlPrefix . $table['@name'] . ($tablePartitions > 1 ? '__part' . $i : '');

                        $tableColumns = [];
                        $tableIndexes = [];

                        foreach ($table['column'] AS $column) {
                            $tableColumns[$column['@name']] = [
                                'type'          => $column['@type'],
                                'autoincrement' => $column['@autoincrement'] ?? false,
                                'restrict'      => (isset($column['@restrict'])
                                    ? explode(',', $column['@restrict'])
                                    : false),
                                'maxlen'        => $column['@maxlen'] ?? false,
                                'bits'          => $column['@bits'] ?? false,
                                'default'       => $column['@default'] ?? null,
                                'comment'       => $column['@comment'] ?? false,
                            ];

                            if (isset($column['@fkey'])) {
                                $values = explode('.', $column['@fkey']);
                                $tableColumns[$column['@name']]['restrict'] = new \Database\DatabaseType(\Database\DatabaseTypeType::tableColumn, $values);
                            }
                        }


                        if (isset($table['key'])) {
                            foreach ($table['key'] AS $key) {
                                $tableIndexes[$key['@name']] = [
                                    'type' => $key['@type'],
                                ];
                            }
                        }

                        //\Fim\Database::instance()->startTransaction();
                        \Fim\Database::instance()->holdTriggers(true);
                        if (in_array(strtolower($tableName), $showTables)) {
                            echo 'Update: ' . $tableName . ': ' . \Fim\Database::instance()->alterTable($tableName, $tableComment, $tableType, $tablePartition  ) . '<br />';
                            fim_flush();
                            echo 'Delete Foreign Keys : ' . $tableName . ': ' . \Fim\Database::instance()->deleteForeignKeyConstraints($tableName) . '<br />';
                            fim_flush();
                            \Fim\Database::instance()->createTableIndexes($tableName, $tableIndexes);

                            foreach ($tableColumns AS $name => $column) {
                                if (in_array($name, $showColumns[strtolower($tableName)])) {
                                    echo 'Update: ' . $tableName . ',' . $name . ': ' . \Fim\Database::instance()->alterTableColumns($tableName, [$name => $column], $tableType) . '<br />';
                                    fim_flush();
                                }
                                else {
                                    echo 'Create: ' . $tableName . ',' . $name . ': ' . \Fim\Database::instance()->createTableColumns($tableName, [$name => $column], $tableType) . '<br />';
                                    fim_flush();
                                }
                            }
                        }
                        else {
                            if (!\Fim\Database::instance()->createTable($tableName, $tableComment, $tableType, $tableColumns, $tableIndexes, $tablePartition)) {
                                die("Could not create table.\n" . \Fim\Database::instance()->getLastError());
                            }
                            else {
                                echo 'Created Table: ' . $tableName . '<br />';
                                fim_flush();
                            }
                        }
                        //\Fim\Database::instance()->endTransaction();
                    }
                }

                echo 'Running Triggers for All Tables and Columns...<br />';
                fim_flush();
                \Fim\Database::instance()->holdTriggers(false);

                echo '<strong>Complete.</strong>';
                break;
        }
    }
    else {
        echo 'You do not have permission to use the tools.';
    }
}
?>