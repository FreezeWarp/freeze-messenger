<?php
/* FreezeMessenger Copyright © 2012 Joseph Todd Parsons

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


/* Note: "none" is used solely for speed testing. NEVER EVER EVER use it. (at least with FIM) */

class generalCache {
  public function __construct($method = '', $servers) {
    global $config;

    $this->data = array();
    $this->method = $method;

    // Basically, use APC if we can, unless told not to. If we can't, use disk.
    if ($method !== 'apc' && $method !== 'disk') {
      if (extension_loaded('apc')) $this->method = 'apc';
      else $this->method = 'disk';
    }
    elseif ($method === 'apc' && !extension_loaded('apc')) $this->method = 'disk';

    if ($this->method === 'disk') {
      require_once('fileCache.php');

      $this->fileCache = new FileCache(dirname(dirname(__FILE__)) . '/cache/');
    }

/*    if ($this->method === 'memcache') {
      $memcache = new Memcache;

      foreach ($servers AS $server) {
        $memcache->addServer($server['host'], $server['port'], $server['persistent'], $server['weight'], $server['timeout'], $server['retry_interval']);
      }
    }*/
  }

  public function get($index) {
    switch ($this->method) {
      case 'none':
      return $this->data[$index];
      break;

      case 'disk':
      $value = $this->fileCache->get($index);

      //if ($error = $this->fileCache->get_error()) throw new Exception('Cache Error: ' . $error);
      //else return $value;
      return $value;
      break;

      case 'apc':
      return apc_fetch($index);
      break;

      case 'memcache':

      break;

      default:
      throw new Exception('Unknown cache method.');
      break;
    }
  }

  public function set($index, $variable, $ttl = 31536000) {
    switch ($this->method) {
      case 'none':
      $this->data[$index] = $variable;
      break;

      case 'disk':
      $this->fileCache->set($index, $variable, $ttl);

//      if ($error = $this->fileCache->get_error()) throw new Exception('Cache Error: ' . $error);
      break;

      case 'apc':
      apc_delete($index);
      apc_store($index, $variable, 0);
      break;

      case 'memcache':

      break;

      default:
      throw new Exception('Unknown cache method.');
      break;
    }
  }

  public function exists($index) {
    switch ($this->method) {
      case 'none':
      return isset($this->data[$index]);
      break;

      case 'disk': // No method exists.
      return false;
      break;

      case 'apc':
      return apc_exists($index);
      break;

      case 'memcache':

      break;

      default:
      throw new Exception('Unknown cache method.');
      break;
    }
  }

  public function clearAll() {
    switch ($this->method) {
      case 'none':
      $this->data = array();
      break;

      case 'disk': // No method exists.
      return false;
      break;

      case 'apc':
      if (apc_clear_cache() && apc_clear_cache('user') && apc_clear_cache('opcode')) return true;
      else return false;
      break;

      case 'memcache':

      break;

      default:
      throw new Exception('Unknown cache method.');
      break;
    }
  }
}

?>