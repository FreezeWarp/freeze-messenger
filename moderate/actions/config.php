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
 * Admin Control Panel: Configuration Tools
 * This script will list all configuration entries in the loaded {@see \Fim\Config} class and display their values. The values can then be edited (with permanence stored in the database), and database changes can be deleted, reverting configuration values to their defaults.
 * To use this script, users must have modPrivs permissions.
 */

if (!defined('WEBPRO_INMOD')) {
    die();
}
else {
    $request = fim_sanitizeGPC('r', array(
        'directive' => array(
            'cast' => 'string',
        ),

        'newDirective' => array(
            'cast' => 'bool',
        ),

        'value' => array(
            'cast' => 'string',
        ),

        'type' => array(
            'cast' => 'string',
            'valid' => array('int', 'bool', 'string', 'float', 'json'),
        ),
    ));

    if ($user->hasPriv('modPrivs')) {
        switch ($_GET['do2'] ?? 'view') {
            case 'view':
                $directives = (new ReflectionClass('\\Fim\\Config'))->getStaticProperties();

                $rows = '';
                foreach ($directives AS $directive => $value) {
                    $rows .= "<tr><td>$directive</td><td>" . gettype($value) . "</td><td>" . var_export($value, true) . "</td><td><a class='btn btn-sm btn-secondary' href='./index.php?do=config&do2=edit&directive=$directive'><i class='fas fa-edit'></i> Edit</a></td></tr>";
                }

                echo container('Configurations','<table class="table table-sm table-striped table-align-middle">
                    <thead class="thead-light">
                    <tr>
                        <th>Directive</th>
                        <th>Type</th>
                        <th>Value</th>
                        <th>Actions</th>
                    </tr>
                    </thead>
                    
                    <tbody>
                      ' . $rows . '
                    </tbody>
                </table>');
            break;

            case 'edit':
                $directiveValue = \Fim\Config::${$request['directive']};
                $directiveType = gettype($directiveValue);

                switch($directiveType) {
                    case 'boolean':
                        $valueBlock = fimHtml_buildSelect('value', array(
                            true => 'true',
                            false => 'false',
                        ), $directiveValue);
                    break;

                    case 'integer':
                    case 'float':
                        $valueBlock = '<input type="number" name="value" required="required" value="' . $directiveValue . '" />';
                    break;


                    case 'array':
                        $valueBlock = '<textarea name="value" class="form-control" style="height: 300px;">' . json_encode($directiveValue, JSON_PRETTY_PRINT) . '</textarea>';
                    break;


                    default:
                        $valueBlock = '<input type="text" name="value" value="' . str_replace('"', '&quot;', $directiveValue) . '" />';
                }

                echo container("Edit Directive '{$request['directive']}'", "<form action='./index.php?do=config&do2=edit2' method='post'>
                    <table class='table table-bordered'>
                    <tr>
                        <td>Directive:</td>
                        <td><input type='hidden' name='directive' value='{$request['directive']}' />{$request['directive']}</td>
                    </tr>
                    <tr>
                        <td>Type:</td>
                        <td>$directiveType</td>
                    </tr>
                    <tr>
                        <td>Value:</td>
                        <td>$valueBlock</td>
                    </tr>
                    </table>
                    
                  <button type='submit' class='btn btn-success'>Submit</button>
                  <button type='reset' class='btn btn-danger'>Reset</button>
                </form>");
            break;

            case 'edit2':
                $directiveType = gettype(\Fim\Config::${$request['directive']});

                if ($directiveType === 'array') {
                    $request['value'] = json_decode($request['value'], true);
                }
                else {
                    settype($request['value'], gettype(\Fim\Config::${$request['directive']}));
                }

                \Fim\Database::instance()->modLog('editConfigDirective', $request['directive']);
                \Fim\Database::instance()->fullLog('editConfigDirective',
                    [
                        'directive' => $request['directive'],
                        'prevValue'     => \Fim\Config::${$request['directive']},
                        'newValue'     => $request['value']
                    ]
                );

                \Fim\Database::instance()->upsert(\Fim\Database::$sqlPrefix . "configuration", [
                    'directive' => $request['directive'],
                ], [
                    'value' => serialize($request['value']),
                ]);

                \Fim\Cache::clearConfig();

                echo container('Configuration Updated','The configuration has been updated. Note that certain settings do not take effect retroactively (e.g. "userRoomCreation" does not change the setting for existing users). <br /><br /><form method="post" action="index.php?do=config"><button type="submit" class="btn btn-success">Return to Viewing Configuration</button></form>');
            break;

            /* TODO: delete DB added directives */
            case 'delete':
                $config2 = \Fim\Database::instance()->getConfiguration($request['directive']);

                if ($config2) {
                    \Fim\Database::instance()->modLog('deleteConfigDirective', $config2['directive']);
                    \Fim\Database::instance()->fullLog('deleteConfigDirective', array('config' => $config2));

                    \Fim\Database::instance()->delete(\Fim\Database::$sqlPrefix . "config", array(
                        'directive' => $request['directive'],
                    ));

                    \Fim\Cache::clearConfig();

                    echo container('Configuration Deleted','The config entry has been deleted.<br /><br /><form method="post" action="index.php?do=config"><button type="submit">Return to Viewing Configuration</button></form>');
                }
                else {
                    echo container('Configuration Not Found','The config specified was not found.<br /><br /><form method="post" action="index.php?do=config"><button type="submit">Return to Viewing Configuration</button></form>');
                }
            break;
        }
    }
    else {
        echo 'You do not have permission to manage Configurations.';
    }
}
?>
