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


$apiRequest = true;
require('../global.php');


/* Header parameters -- identifies what we're doing as well as the message itself, if applicable. */
$request = fim_sanitizeGPC('p', [
    'endpoint' => [
        'require' => true
    ],
    'p256dh' => [
        'require' => true
    ],
    'auth' => [
        'require' => true
    ]
]);

\Cache\CacheFactory::setAdd('pushSubs_' . $user->id, $request['endpoint'], \Cache\DriverInterface::CACHE_TYPE_DISTRIBUTED);
\Cache\CacheFactory::set('pushSubsKeys_' . $request['endpoint'], [$request['p256dh'], $request['auth']], 31536000, \Cache\DriverInterface::CACHE_TYPE_DISTRIBUTED);

echo new Http\ApiData(['webpushSubscribe' => []]);
?>