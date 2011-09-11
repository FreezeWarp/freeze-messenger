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

function fim_generateSalt() {
  $salt = rand(1, 10000);

  return $salt;
}


function fim_generatePassword($password, $salt) {
  for ($i; $i < 5000; $i++) { // Hash the password using sha256 5000 times
    $password = fim_sha256(fim_sha256($password) . $salt);
  }
}
?>