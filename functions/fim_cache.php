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


class generalCache {
  public function __construct($method, $servers) {
    global $config;

    if ($method) {
      $this->method = $method;
    }
    else {
      if (extension_loaded('apc')) {
        $this->method = 'apc';
      }
    }

    if ($this->method === 'memcache') {
      $memcache = new Memcache;

      foreach ($servers AS $server) {
        $memcache->addServer($server['host'], $server['port'], $server['persistent'], $server['weight'], $server['timeout'], $server['retry_interval']);
      }
    }
  }

  public function getCachedVar($index) {
    switch ($this->method) {
      case 'apc':
      return apc_fetch($index);
      break;

      case 'memcache':

      break;
    }
  }

  public function setCachedVar($index, $variable, $ttl) {
    switch ($this->method) {
      case 'apc':
      apc_delete($index);
      apc_store($index, $variable, $ttl);
      break;

      case 'memcache':

      break;
    }
  }
}

?>