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

if (!file_exists('config.php')) {
  if (file_exists('install.php')) {
    header('Location: install.php');
    die('FreezeMessenger must first be installed. <a href="install.php">Click here</a> to do so.');
  }
  else {
    die('FreezeMessenger must first be installed. Please modify config-base.php and save as config.php.');
  }
}

header('Location: chat.php');
?>