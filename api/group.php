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
 * TODO
 */

/* Common Resources */

class group
{
    static $xmlData;

    static $requestHead;

    static function init()
    {
        self::$requestHead = \Fim\Utilities::sanitizeGPC('g', [
            '_action' => [],
        ]);

        self::{self::$requestHead['_action']}();
    }


    static function get()
    {
        $request = \Fim\Utilities::sanitizeGPC('g', [
            'groupIds' => [
                'cast'     => 'list',
                'filter'   => 'int',
                'evaltrue' => true,
                'default'  => [],
                'max'      => 50,
            ],

            'groupNames' => [
                'cast'     => 'list',
                'filter'   => 'string',
                'default'  => [],
                'max'      => 50,
            ],

            'sort' => [
                'valid'   => ['id', 'name'],
                'default' => 'id',
            ],
        ]);

        \Fim\Database::instance()->accessLog('getGroups', $request);


        /* Data Predefine */
        self::$xmlData = [
            'groups' => [],
        ];



        /* Get Users from Database */
        if (isset($groupData)) { // From api/user.php
            $groups = [$groupData];
        }
        else {
            $groups = \Fim\DatabaseSlave::instance()->getGroups(
                \Fim\Utilities::arrayFilterKeys($request, ['groupIds', 'groupNames']),
                [$request['sort'] => 'asc']
            )->getAsArray(true);
        }



        /* Start Processing */
        foreach ($groups AS $groupData) {
            self::$xmlData['groups'][$groupData['id']] = \Fim\Utilities::arrayFilterKeys($groupData, ['id', 'name']);
        }
    }
}


/* Entry Point Code */
$apiRequest = true;
require('../global.php');
group::init();
echo new Http\ApiData(group::$xmlData);