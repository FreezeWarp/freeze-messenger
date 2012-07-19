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
  // There are quite a few ways of creating a salt, and unfortunately there is very little concencus as to what is safe and what is not. To this end, I will use three seperate randomisation, uniqids, rand/mt_rand, and str_shuffle, as well as microtime, until I get more concencus.	

  $salt = str_shuffle(str_replace('.','',uniqid('',true)) . str_replace('.','',microtime(true)) . mt_rand(1,100000000000));

  if (strlen($salt) > 50) {
    return substr($salt, 0, 50);
  }

  return $salt;
}



/**
 * Generates a password hash using a password, salt, and optionally a number of iterations to run the encryption over. The function will also make use of salts stored in config.php to prevent bruteforcing of passwords in the case of a database leak.
 *
 * @param string $password - The password.
 * @param string $salt - The salt to use. This salt is stored in the database.
 * @param string $privateSaltNum - The private salt to use. This salt is referrenced in the database, but stored on the fileserver.
 * @param int $hashStage - The level at which the password is already hashed. 0 is no hashing, 1 is sha256(password), 2 is sha256(sha256(password) . salt), and 3 is fully hashed.
 * @return void - true on success, false on failure
 * @author Joseph Todd Parsons <josephtparsons@gmail.com>
*/

function fim_generatePassword($password, $salt, $privateSaltNum, $hashStage = 0) {
  global $salts;

  $privateSalt = $salts[$privateSaltNum]; // Get the proper salt.

  switch ($hashStage) {
    case 0:
    $passwordHashed = fim_sha256(fim_sha256(fim_sha256($password) . $salt) . $privateSalt);
    break;

    case 1:
    $passwordHashed = fim_sha256(fim_sha256($password . $salt) . $privateSalt);
    break;

    case 2:
    $passwordHashed = fim_sha256($password . $privateSalt);
    break;

    default:
    $passwordHashed = $password;
    break;
  }

  return $passwordHashed;
}
?>