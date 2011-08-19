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

/**
 * Determines if any value in an array is found in a seperate array.
 *
 * @param array $needle - The array that contains all values that will be applied to $haystack
 * @param array $haystack - The matching array.
 * @return bool
 *
 * @author Joseph Todd Parsons <josephtparsons@gmail.com>
 */
function fim_inArray($needle, $haystack) {
  if (!$haystack) { // If the haystack is not valid, return false.
    return false;
  }
  elseif (!$needle) { // If the needle is not valid, return false.
    return false;
  }
  else {
    foreach($needle AS $need) { // Run through each entry of the needle
      if (!$need) { // If the needle value is false, skip it.
        continue;
      }

      if (in_array($need, $haystack)) { // If the needle is in the haystack, return true.
        return true;
      }
    }

    return false; // If we haven't returned true yet, return false.
  }
}



/**
 * Returns a "safe" array based on parameters.
 *
 * @param array $array - The array to be processed.
 * @param string $type - The variable type all entries in the returned array should corrospond to.
 * @param bool $preserveAll - Whether false, 0, and empty strings should be returned as a part of the array.
 * @return array
 * @author Joseph Todd Parsons <josephtparsons@gmail.com>
 */
function fim_arrayValidate($array, $type = 'int', $preserveAll = false) {
  $arrayValidated = array(); // Create an empty array we will use to store things.

  if (is_array($array)) { // Make sure the array is an array.
    foreach ($array AS $value) { // Run through each value of the array.
      switch ($type) { // What type are we validating to?
        case 'int': // Integer type.
        if ($preserveAll) { // Do we preserve false entries?
          $arrayValidated[] = (int) $value; // If so, simply cast the value as an integer.
        }

        else { // If we don't preserve false entries...
          $preValue = (int) $value; // Cast the value

          if ($preValue) { // If it is non-zero, add it to the new array.
            $arrayValidated[] = $preValue;
          }
        }
        break;
      }
    }
  }
  else { // If its not, we will return an empty array.
    $arrayValidated = array();
  }

  return $arrayValidated; // Return the validated array.
}



/**
 * Determines if a user has permission to do an action in a room.
 *
 * @param array $roomData - An array containing the room's data; indexes allowedUsers, allowedGroups, moderators, owner, and options may be used.
 * @param array $userData - An array containing the user's data; indexes userId, adminPrivs, and userPrivs may be used.
 * @param string $type - Either "topic", "view", "post", "moderate", or "admin", this defines the action the user is trying to do.
 * @param bool $trans - If true, return will be an information array; otherwise bool.
 * @global bool $banned - Whether or not the user is banned outright.
 * @global array $superUsers - The list of superUsers.
 * @global bool $valid - Whether or not the user has a valid login (required for posting, etc.)
 * @global string $sqlPrefix
 * @return mixed - Bool if $trans is false, array if $trans is true.
 * @author Joseph Todd Parsons <josephtparsons@gmail.com>
 */
function fim_hasPermission($roomData, $userData, $type = 'post', $quick = false) {
  global $sqlPrefix, $banned, $loginConfig, $valid, $database, $config, $kicksCache, $permissionsCache;


  // Set all of these to false to start.
  $isAdmin = false;
  $isModerator = false;
  $isAllowedUser = false;
  $isAllowedGroup = false;
  $isOwner = false;
  $isRoomDeleted = false;
  $isPrivateRoom = false;
  $kick = false;

  $reason = ''; // Create an empty string for the reason.
  $type = (array) $type; // Type cast the type as an array.


  // These are the corrosponding database permissions for each "type".
  $permMap = array(
    'view' => 1,
    'post' => 2,
    'topic' => 4,
    'moderate' => 8,
    'admin' => 128,
  );


  // Make sure all presented data is correct.
  if (!$roomData['roomId']) { // If the room is not valid...
    if ($quick) {
      return false;
    }
    else {
      return array(
        false,
        'invalidRoom',
        0
      );
    }
  }
  elseif (!isset($roomData['defaultPermissions'])) { // If the default permissions index is missing, through an exception.
    throw new Exception('Room data invalid (defaultPermissions index missing)');
  }
  elseif ($type == 'know') { // Transitional.
    throw new Exception('Room data invalid (type of "know")');
  }


  foreach ((array) $type AS $type2) { // Run through each type.
    /* Get the User's Kick Status */
    if (isset($userData['userId'])) { // Was a user specified?
      if ($userData['userId'] > 0) { // Is their userId non-zero?
        if (count($kicksCache) > 0) { // Is the kicks cache non-empty?
          if (isset($kicksCache[$roomData['roomId']][$userData['userId']])) { // Does a kick entry exist for the user in this room?
            $kick = true; // We're kicked!
          }
          else {
            $kick = false; // We're not kicked!
          }
        }
      }
    }


    /* Is the User an Allowed User? */
    if (isset($permissionsCache[$roomData['roomId']],$permissionsCache[$roomData['roomId']]['user'],$permissionsCache[$roomData['roomId']]['user'][$userData['userId']])) {
      if ($permissionsCache[$roomData['roomId']]['user'][$userData['userId']] & $permMap[$type2]) {
        $isAllowedUser = true;
      }
    }
    else {
      if ($roomData['defaultPermissions'] & $permMap[$type2]) {
        $isAllowedUser = true;
      }
    }


    /* Is the User Part of an Allowed Group? */
    if (isset($permissionsCache[$roomData['roomId']],$permissionsCache[$roomData['roomId']]['group'],$permissionsCache[$roomData['roomId']]['group'][$userData['userId']])) {
      if ($permissionsCache[$roomData['roomId']]['group'][$userData['userId']] & $permMap[$type2]) {
        $isAllowedGroup = true;
      }
    }

    if (isset($permissionsCache[$roomData['roomId']],$permissionsCache[$roomData['roomId']]['admingroup'],$permissionsCache[$roomData['roomId']]['admingroup'][$userData['userId']])) {
      if ($permissionsCache[$roomData['roomId']]['admingroup'][$userData['userId']] & $permMap[$type2]) {
        $isAllowedGroup = true;
      }
    }


    /* Is the User the Room's Owner/Creator */
    if (isset($roomData['owner'])) {
      if ($roomData['owner'] == $userData['userId']
        && $roomData['owner'] > 0) {
        $isOwner = true;
      }
    }
    else {
      throw new Exception('Room data invalid (owner index missing)'); // We need the owner index.
    }


    /* Is the Room a Private Room or Deleted? */
    if (isset($roomData['options'])) {
      if ($roomData['options'] & 4) {
        $isRoomDeleted = true; // The room is deleted.
      }

      if ($roomData['options'] & 16) {
        $isPrivateRoom = true; // The room is private
      }
    }
    else {
      throw new Exception('Room data invalid (options index missing)'); // We need the options index.
    }


    /* Is the user a super user? */
    if (isset($userData['userId']) && isset($userData['adminPrivs']) && isset($loginConfig['superUsers'])) {
      if (is_array($loginConfig['superUsers'])) {
        if (in_array($userData['userId'], $loginConfig['superUsers']) || $userData['adminPrivs'] & 1) {
          $isAdmin = true;
        }
      }
    }


    if ($type2 == 'post') {
      if ($banned) {
        $roomValid['post'] = false;
        $reason = 'banned';
      }
      elseif (!$valid) {
        $roomValid['post'] = false;
        $reason = 'invalid';
      }
      elseif ($kick && !$isAdmin) {
        $roomValid['post'] = false;
        $reason = 'kicked';
      }
      elseif ($isAdmin && !$isPrivateRoom) {
        $roomValid['post'] = true;
      }
      elseif ($isRoomDeleted) {
        $roomValid['post'] = false;
        $reason = 'deleted';
      }
      elseif ($isAllowedUser || $isAllowedGroup || $isOwner) {
        $roomValid['post'] = true;
      }
      else {
        $roomValid['post'] = false;
        $reason = 'general';
      }
    }

    if ($type2 == 'view') {
      if ($isAdmin && !$isPrivateRoom) {
        $roomValid['view'] = true;
      }
      elseif ($isRoomDeleted) {
        $roomValid['view'] = false;
        $reason = 'deleted';
      }
      elseif ($isAllowedUser || $isAllowedGroup || $isOwner) {
        $roomValid['view'] = true;
      }
      else {
        $roomValid['view'] = false;
        $reason = 'general';
      }
    }

    if ($type2 == 'moderate') {
      if ($banned) {
        $roomValid['moderate'] = false;
        $reason = 'banned';
      }
      elseif (!$valid) {
        $roomValid['moderate'] = false;
        $reason = 'invalid';
      }
      elseif ($kick && !$isAdmin) {
        $roomValid['moderate'] = false;
        $reason = 'kicked';
      }
      elseif ($isPrivateRoom) {
        $roomValid['moderate'] = false;
        $reason = 'private';
      }
      elseif ($isOwner || $isModerator || $isAdmin) {
        $roomValid['moderate'] = true;
      }
      else {
        $roomValid['moderate'] = false;
        $reason = 'general';
      }
    }

    if ($type2 == 'admin') {
      if ($banned) {
        $roomValid['admin'] = false;
        $reason = 'banned';
      }
      elseif (!$valid) {
        $roomValid['admin'] = false;
        $reason = 'invalid';
      }
      elseif ($kick) {
        $roomValid['admin'] = false;
        $reason = 'kicked';
      }
      elseif ($isPrivateRoom) {
        $roomValid['admin'] = false;
        $reason = 'private';
      }
      elseif ($isAdmin) {
        $roomValid['admin'] = true;
      }
      else {
        $roomValid['admin'] = false;
        $reason = 'general';
      }
    }
  }


  if ($quick) {
    return (count($type) > 1 ? $roomValid : $roomValid[$type[0]]);
  }
  else {
    return array(
      (count($type) > 1 ? $roomValid : $roomValid[$type[0]]),
      $reason,
      $kick['expiresOn']
    );
  }
}



/**
 * Decodes a specifically-formatted URL string, converting entities for "+", "&", '%', and new line to their respective string value.
 *
 * @param string $str - The string to be decoded.
 * @return string - The decoded text.
 * @author Joseph Todd Parsons <josephtparsons@gmail.com>
 */
function fim_urldecode($str) {
  return str_ireplace(
    array('%2b', '%26', '%20', '%25', '%0a'),
    array('+', '&', ' ', '%', "\n"),
    $str
  );
}



/**
 * Decrypts data.
 *
 * @param array $message - An array containing the message data; must include an "iv" index.
 * @param mixed $index - An array or string corrosponding to which indexes in the $message should be decrypted.
 * @global array $salts - Key-value pairs used for encryption.
 * @return array
 * @author Joseph Todd Parsons <josephtparsons@gmail.com>
 */
function fim_decrypt($message, $index = array('apiText', 'htmlText', 'rawText')) {
  global $salts, $config;

  if (isset($message['salt'], $message['iv'])) { // Make sure the proper indexes exist (just in case).
    if ($message['salt'] && $message['iv']) { // Make sure both the salt and the IV are non-false.
      $salt = $salts[$message['salt']]; // Get the proper salt.

      if ($index) { // If indexes are specified...
        foreach ((array) $index AS $index2) { // Run through each index. If the specified index variable is a string instead of an array, we will cast it as an array ("example" becomes array("example")).
          if (!isset($message[$index2])) { // If the index is not in the message, throw an exception.
            throw new Exception('Index not found: ' . $index2);
          }

          else { // Otherwise, unencrypt the index.
            $message[$index2] = rtrim( // Remove \0 bytes from the end.
              mcrypt_decrypt( // Decrypt the data.
                MCRYPT_3DES, // Decrypt the data using 3DES/Triple DES (see http://en.wikipedia.org/wiki/Triple_DES).
                $salt, // Use the salt we found above.
                base64_decode($message[$index2]), // Base64-decode the data first, since we store it encoded.
                MCRYPT_MODE_CBC, // Use Mcrypt CBC mode (see http://php.net/manual/en/function.mcrypt-cbc.php and http://www.php.net/manual/en/mcrypt.constants.php).
                base64_decode($message['iv']) // Uses the decoded IV (we store it using base64 encoding).
              ),
            "\0");
          }
        }
      }
    }
  }

  return $message; // Return the original array with the specified indexes unencrypted.
}



/**
 * Encrypts a string or all values in an array. Data is encrypted using 3DES and CBC.
 * The returned data will contain the encrypted $data value (as an array with key->value pairs left intact, or as a string, depending on how it was passed), the base64_encoded IV, and the number of the salt used for encryption (the corrosponding salt needed for unencrypted should be stored in config.php).
 *
 * @param mixed $data - The data to encrypt.
 * @global array $salts - Key-value pairs used for encryption.
 * @return array - list($data, $iv, $saltNum)
 * @author Joseph Todd Parsons <josephtparsons@gmail.com>
 */
function fim_encrypt($data) {
  global $salts, $config;

  $salt = end($salts); // Move the file pointer to the last entry in the array (and return its value)
  $saltNum = key($salts); // Get the key/id of the corrosponding salt.

  $iv_size = mcrypt_get_iv_size(MCRYPT_3DES, MCRYPT_MODE_CBC); // Get the length of the IV for the method used
  $iv = base64_encode( // Encode the IV using Base64 encoding (to avoid any datastore headaches).
    mcrypt_create_iv($iv_size, MCRYPT_RAND) // Generate an encryption Initilization Vector (see http://en.wikipedia.org/wiki/Initialization_vector)
  );

  if (is_array($data)) { // If $data is an array, we will encrypt each value, and retain the key->value structure.
    $newData = array(); // Create the array, since we will be adding key=>value pairs to it briefly.

    foreach ($data AS $key => $value) { // Run through the data array...
      $newData[$key] = base64_encode( // Encode the data as Base64.
        rtrim( // Trim \0 bytes from the _right_ of the encrypted value (see http://php.net/rtrim).
          mcrypt_encrypt( // Encrypt the $value.
            MCRYPT_3DES, // Encrypt the data using 3DES/Triple DES (see http://en.wikipedia.org/wiki/Triple_DES).
            $salt, // The salt we obtained from the system configuration earlier...
            $value, // Our value to encrypt.
            MCRYPT_MODE_CBC, // Use Mcrypt CBC mode (see http://php.net/manual/en/function.mcrypt-cbc.php and http://www.php.net/manual/en/mcrypt.constants.php).
            base64_decode($iv) // We need to use the raw IV, so we decode the earlier encoded value.
          ),
        "\0")
      );
    }
  }
  else {
    $newData = base64_encode( // See comments above.
      rtrim(
        mcrypt_encrypt(
          MCRYPT_3DES, $salt, $data, MCRYPT_MODE_CBC, base64_decode($iv)
        ),"\0"
      )
    );
  }

  return array($newData, $iv, $saltNum); // Return the data.
}



/**
 * Generates a SHA256 using whatever methods are available. If no valid function can be found, the data will be returned unhashed.
 *
 * @param string $data - The data to encrypt.
 * @return string - Encrypted data.
 * @author Joseph Todd Parsons <josephtparsons@gmail.com>
 */
function fim_sha256($data) {
  global $config;

  if (function_exists('hash')) { // hash() is available in PHP 5.1.2+, or in PECL Hash 1.1
    return hash('sha256', $data);
  }
  elseif (function_exists('mhash') && defined('MHASH_SHA256')) { // mhash() is available in pretty much all versions of PHP, but the SHA256 algo may not be available.
    return mhash(MHASH_SHA256, $data);
  }
  else { // Otherwise, we'll return the data unhashed. Better than dieing completely, really.
    return $data;
  }
}



/**
 * Encodes a string as specifically-formatted XML data, converting "&", "'", '"', "<", and ">" to their equivilent values.
 *
 * @param string $data - The data to be encoded.
 * @return string - Encoded data.
 * @author Joseph Todd Parsons <josephtparsons@gmail.com>
 */
function fim_encodeXml($data) {
  global $config;

  $data = str_replace($config['encodeXmlEntitiesFind'], $config['encodeXmlEntitiesReplace'], $data); // Replace the entities defined in $config (these are usually not changed).

  return $data;
}


/**
 * Encodes a string as specifically-formatted XML data attribute.
 *
 * @param string $data - The data to be encoded.
 * @return string - Encoded data.
 * @author Joseph Todd Parsons <josephtparsons@gmail.com>
 */
function fim_encodeXmlAttr($data) {
  global $config;

  $data = str_replace($config['encodeXmlAttrEntitiesFind'], $config['encodeXmlAttrEntitiesReplace'], $data); // Replace the entities defined in $config (these are usually not changed).

  return $data;
}


/**
 * Converts an HTML hexadecimal code to an array containing equivilent r-g-b values.
 *
 * @param string $color - The color, either 3 or 6 characters long with optional "#" appended.
 * @return array
 * @author Unknown
 */
function html2rgb($color) {
  global $config;


  if ($color[0] === '#') { // Strip a prepended "#" if it exists.
    $color = substr($color, 1);
  }


  // Get the RGB colour as an array
  if (strlen($color) === 6) { // Data is stored as a six-character hexadecimal string (e.g. FFFFFF)
    list($r, $g, $b) = array($color[0] . $color[1], $color[2] . $color[3], $color[4] . $color[5]);
  }
  elseif (strlen($color) === 3) { // Data is stored as a three-character hexadecimal string (e.g. FFF)
    list($r, $g, $b) = array($color[0] . $color[0], $color[1] . $color[1], $color[2].$color[2]);
  }
  else {
    throw new Exception('Invalid color: ' . $color);
  }


  // Convert hexadecimal values to decimalvalues
  $r = hexdec($r);
  $g = hexdec($g);
  $b = hexdec($b);


  return array($r, $g, $b); // Return as an array.
}



/**
 * Converts an r-g-b value array (or integer list) to equivilent hexadecimal code.
 *
 * @param mixed $r
 * @param int $g
 * @param int $b
 * @return string
 * @author Unknown
 */
function rgb2html($r, $g = false, $b = false) {
  global $config;


  // Support the first parameter as being an array of the three.
  if (is_array($r) && sizeof($r) === 3) {
    list($r, $g, $b) = $r;
  }


  // Cast the values as integers.
  $r = (int) $r;
  $g = (int) $g;
  $b = (int) $b;


  // Restrict the value to a range of 0-255, then convert the value from decimal to hexadecimal.
  $r = dechex($r < 0 ? 0 : ($r > 255 ? 255 : $r));
  $g = dechex($g < 0 ? 0 : ($g > 255 ? 255 : $g));
  $b = dechex($b < 0 ? 0 : ($b > 255 ? 255 : $b));


  // Create the color. If a hexadecimal value is only one character in length, prepend a "0" to it (e.g. "3" becomes "03").
  $color = (strlen($r) < 2 ? '0' : '') . $r;
  $color .= (strlen($g) < 2 ? '0' : '') . $g;
  $color .= (strlen($b) < 2 ? '0' : '') . $b;


  return '#' . $color; // Return the value, prepended with a "#" to signify an HTML colour.
}



/**
 * Produces a date string based on specialized conditions.
 *
 * @param string $format - The format to be used; if false a format will be generated based on the distance between the current time and the time specicied.
 * @param int $timestamp - The timestamp to be used, defaulting to the current timestamp.
 * @global $user
 * @return string
 * @author Unknown
 */
function fim_date($format, $timestamp = false) {
  global $user, $config;

  $timestamp = ($timestamp ? $timestamp : time()); // If a timestamp was specified, we'll go with it, otherwise get it from PHP's time().

  $hourdiff = (isset($user['timeZone']) ? $user['timeZone'] : $config['defaultTimeZone']); // If the user's timeZone offset (e.g. 2 = 2 hour ahead) is set, we use it, otherwise we'll use the systsem default.
  $timestampAdj = $timestamp + ($hourdiff * 3600); // Get the updated timestamp by adding the current timestamp and the hour diff, multiplied by 3600 (the number of seconds in hours).

  if ($format === false) { // If format is not specified, we use a dynamic format that will only show the day if the date is not on the current day.
    $midnight = strtotime("yesterday") - $hourdiff; // Get the time of the last midight.

    if ($timestampAdj > $midnight) { // The date falls on the current day (the timestamp is after the last midnight).
      $format = 'g:i:sa';
    }
    else { // The date does not fall on the current day.
      $format = 'm/d/y g:i:sa';
    }
  }

  $newDate = gmdate($format, $timestampAdj); // Using PHP's gmdate(), get a date string based on the specified format and the adjusted timezone. Note that gmdate() is the same as date(), but forces GMT/UST.

  return $newDate;
}



/**
 * Retrieves a hook from the database.
 *
 * @param string $name
 * @return evalcode (string)
 * @author Joseph Todd Parsons <josephtparsons@gmail.com>
 */
function hook($name) {
  global $hooks, $disableHooks;


  if ($disableHooks) { // If hooks are disabled, then return false.
    return false;
  }
  elseif (isset($hooks[$name])) { // If the hook is set...
    if (strlen($hooks[$name]) > 0) { // If the hook is not empty, return the code to eval.
      return $hook;
    }
    else { // Otherwise return false.
      return false;
    }
  }
  else { // And if the hook isn't set, return false.
    return false;
  }
}



/**
 * Retrieves a template from the database.
 *
 * @param string $name
 * @return string
 * @author Joseph Todd Parsons <josephtparsons@gmail.com>
 */
function template($name) {
  global $templates, $phrases, $title, $user, $room, $message, $template, $templateVars; // Lame approach.
  static $globalString;


  if (isset($templates[$name])) { // Does the template exist?
    if (isset($templateVars[$name])) { // Do additional variables exist?
      if (strlen($templateVars[$name]) > 0) { // Are the additional varaiables non-empty?
        $vars = explode(',', $templateVars[$name]); // Get the vars as an array.

        foreach ($vars AS $var) { // Run through each var.
          $globalVars[] = '$' . trim($var); // Format the var for an eval, trimming it to remove any spaces surrounding the var.
        }

        $globalString = implode(',', $globalVars); // Implode the variables for the below global eval.

        eval("global $globalString;"); // And make all those variables global.
      }
    }


    $template2 = parser1($templates[$name], 0, false, $globalString); // The initial parser, which is stored in the function below.
    $template2 = preg_replace('/(.+)/e', 'stripslashes("\\1")', $template2); // Eval the data returned, thus obtaining proper variables.


    return $template2; // Return the template string.
  }
  else { // Return an empty string if the template is not valid.
    return '';
  }
}



/**
 * Parser for template() function.
 *
 * @param string $text
 * @param int $offset
 * @param bool $stop
 * @param string $globalString
 * @return string
 * @author Joseph Todd Parsons <josephtparsons@gmail.com>
 */
function parser1($text, $offset, $stop = false, $globalString = '') {
  $i = $offset;

  $cValue = false;
  $cValueProc = false;
  $iValue = false;
  $iValueProc = false;
  $str = '';

  while ($i < strlen($text)) {
    $j = $text[$i];

    if ($iValueProc) {
      $str .= fim_iifl($cond,
        (isset($iv[1]) ? $iv[1] : ''),
        (isset($iv[2]) ? $iv[2] : ''),
        ($globalString ? "global $globalString;" : '')
      );
      if ($stop) return array($str, $i);

      $iv = array(1 => '', 2 => '');
      $cond = '';
      $iValueI = 1;
      $iValueProc = false;

      continue;
    }

    elseif ($cValueProc) {
      $str .= container("$cv[1]","$cv[2]");
      if ($stop) return array($str, $i);

      $cv = array(1 => '', 2 => '');
      $cValueProc = false;
      $cond = '';

      continue;
    }

    elseif ($cValue) {
      if (substr($text, $i,2) == '}{' && $cValueI == 1) {
        $cValueI = 2;

        $i += 2;
        continue;
      }

      elseif (substr($text, $i,2) == '}}') {
        $cValue = false;
        $cValueProc = true;
        $i += 2;

        continue;
      }

      elseif (substr($text, $i,13) == '{{container}{') {
        list($nstr, $offset) = parser1($text, $i,true, $globalString);
        $i = $offset;
        $cv[$cValueI] .= $nstr;

        continue;
      }

      elseif (substr($text, $i,6) == '{{if="') {// die('<<<' . $text . '>>>');
        list($nstr, $offset) = parser1($text, $i,true, $globalString);
        $i = $offset;
        $cv[$cValueI] .= $nstr;

        continue;
      }

      else {
        $escape = false;
        if (isset($cv[$cValueI])) {
          $cv[$cValueI] .= $j;
        }
        else {
          $cv[$cValueI] = $j;
        }
      }
    }

    elseif ($iValue) {
      if ((substr($text, $i,2) == '}{') && ($iValueI == 1)) {
        $iValueI = 2;

        $i += 2;
        continue;
      }

      elseif (substr($text, $i,2) == '}}') {
        $iValue = false;
        $iValueProc = true;
        $i += 2;

        continue;
      }

      elseif (substr($text, $i,13) == '{{container}{') {
        list($nstr, $offset) = parser1($text, $i,true, $globalString);
        $i = $offset;
        $iv[$iValueI] .= $nstr;

        continue;
      }

      elseif (substr($text, $i,6) == '{{if="') {
        list($nstr, $offset) = parser1($text, $i,true, $globalString);
        $i = $offset;
        $iv[$iValueI] .= $nstr;

        continue;
      }

      else {
        if (isset($iv[$iValueI])) {
          $iv[$iValueI] .= $j;
        }
        else {
          $iv[$iValueI] = $j;
        }
      }
    }

    else {
      if (substr($text, $i,6) == '{{if="') {
        $i += 6;
        while ($text[$i] != '"') {
          if (isset($cond)) {
            $cond .= $text[$i];
          }
          else {
            $cond = $text[$i];
          }

          $i++;
        }

        $i+=3;

        $iValue = true;
        $iValueI = 1;

        continue;
      }

      elseif (substr($text, $i,13) == '{{container}{') {
        $cValue = true;
        $cValueI = 1;

        $i += 13;

        continue;
      }

      else {
        $str .= $j;
      }
    }

    $i++;
  }

  if ($iValueProc) {
    $str .= fim_iifl("$cond","$iv[1]","$iv[2]",($globalString ? "global $globalString;" : ''));
    if ($stop) return array($str, $i);

    $iv = array(1 => '', 2 => '');
    $cond = '';
    $iValueI = 1;
    $iValueProc = false;
  }

  elseif ($cValueProc) {
    $str .= container("$cv[1]","$cv[2]");
    if ($stop) return array($str, $i);

    $cv = array(1 => '', 2 => '');
    $cValueProc = false;
    $cond = '';
  }

  return $str;
}



/**
 * Inline If
 *
 * @param string $condition
 * @param string $true
 * @param string $false
 * @param evalcode $eval
 * @return string
 * @author Joseph Todd Parsons <josephtparsons@gmail.com>
 */
function fim_iifl($condition, $true = '', $false = '', $eval = '') {
  global $config, $templates, $phrases, $title, $user, $room, $message, $template, $templateVars; // Lame approach.


  if (strlen($eval) > 0) { // Is there an eval condition? We use this primarily to set global variables (though there are other uses).
    eval($eval);
  }


  if (eval('return ' . $condition . ';')) { // Is the condition valid?
    return $true; // Return the true string.
  }
  else {
    return $false; // Return the false string.
  }
}



/**
 * Container Template
 *
 * @param string $title
 * @param string $content
 * @param string $class
 * @return string
 * @author Joseph Todd Parsons <josephtparsons@gmail.com>
 */
function container($title, $content, $class = 'page') {
  global $config;

  return $return = "<table class=\"$class ui-widget\">
  <thead>
    <tr class=\"hrow ui-widget-header ui-corner-top\">
      <td>$title</td>
    </tr>
  </thead>
  <tbody class=\"ui-widget-content ui-corner-bottom\">
    <tr>
      <td>
        <div>$content</div>
      </td>
    </tr>
  </tbody>
</table>

";
}



/**
 * API Layer
 *
 * @param array $array
 * @return string
 * @author Joseph Todd Parsons <josephtparsons@gmail.com>
 */
function fim_outputApi($data) {
  global $config;

  if (isset($_REQUEST['fim3_format'])) {
    switch ($_REQUEST['fim3_format']) {
      case 'phparray': // print_r
      return fim_outputArray($data);
      break;

      case 'keys': // HTML List format for the keys only (documentation thing)
      return fim_outputKeys($data);
      break;

      case 'xml2': // Compact XML
      return fim_outputXml2($data);
      break;

      case 'xml': // No-Attribute XML (all data expressed as nodes)
      return fim_outputXml($data);
      break;

      case 'json': // Javascript Object Notion
      default:
      return fim_outputJson($data);
      break;
    }
  }
  else {
    return fim_outputJson($data);
  }
}



/**
 * XML Parser
 *
 * @param array $array
 * @param int $level
 * @return string
 * @author Joseph Todd Parsons <josephtparsons@gmail.com>
 */
function fim_outputXml($array, $level = 0) {
  global $config;

  header('Content-type: application/xml');

  $indent = '';
  $data = '';

  for ($i = 0; $i < $level; $i++) {
    $indent .= '  ';
  }

  foreach ($array AS $key => $value) {
    $key = explode(' ', $key);
    $key = $key[0];

    $data .= "$indent<$key>\n";

    if (is_array($value)) {
      $data .= fim_outputXml($value, $level + 1);
    }
    else {
      if ($value === true) {
        $value = 'true';
      }
      elseif ($value === false) {
        $value = 'false';
      }
      elseif (is_string($value)) {
        $value = fim_encodeXml($value);
      }

      $data .= "$indent  $value\n";
    }

    $data .= "$indent</$key>\n";
  }

  if ($level == 0) {
    return "<?xml version=\"1.0\" encoding=\"UTF-8\" ?>
<!DOCTYPE html [
  <!ENTITY nbsp \" \">
]>

$data";
  }
  else {
    return $data;
  }
}



/**
 * XML Alternate Parser
 *
 * @param array $array
 * @param int $level
 * @return string
 * @author Joseph Todd Parsons <josephtparsons@gmail.com>
 */
function fim_outputXml2($array, $level = 0) {
  global $config;

  header('Content-type: application/xml');

  $indent = '';
  $data = '';

  for ($i = 0; $i < $level; $i++) {
    $indent .= '  ';
  }

  foreach ($array AS $key => $value) {
    $key = explode(' ', $key);
    $key = $key[0];

    if (is_array($value)) {
      if (hasArray($value)) {
        $data2 = '';
        foreach ($value AS $key2 => $value2) {
          if (!is_array($value2)) {
            $data2 .= " {$key2}=\"" . fim_encodeXmlAttr($value2) . "\"";
            unset($value[$key2]);
          }
        }
        $data .= "{$indent}<{$key}{$data2}>\n" . fim_outputXml2($value, $level + 1) . "{$indent}</{$key}>\n";
      }
      else {
        $data .= "{$indent}<{$key}";

        foreach ($value AS $key => $value2) {
          $data .= " {$key}=\"" . fim_encodeXmlAttr($value2) . "\"";
        }

        $data .= " />\n";
      }
    }
    else {
      if (empty($value)) {
        $data .= "{$indent}<$key />\n";
      }
      else {
        if ($value === true) {
          $value = 'true';
        }
        elseif ($value === false) {
          $value = 'false';
        }
        elseif (is_string($value)) {
          $value = fim_encodeXml($value);
        }

        $data .= "{$indent}<{$key}>{$value}</{$key}>\n";
      }
    }
  }

  if ($level == 0) {
    return "<?xml version=\"1.0\" encoding=\"UTF-8\" ?>
<!DOCTYPE html [
  <!ENTITY nbsp \" \">
]>

$data";
  }
  else {
    return $data;
  }
}



/**
 * JSON Parser
 *
 * @param array $array
 * @param int $level
 * @return string
 * @author Joseph Todd Parsons <josephtparsons@gmail.com>
 */
function fim_outputJson($array, $level = 0) {
  global $config;

  header('Content-type: application/json');

  $data = array();
  $indent = '';

  for ($i = 0; $i <= $level; $i++) {
    $indent .= '  ';
  }

  foreach ($array AS $key => $value) {
//    $key = explode(' ', $key);
//    $key = $key[0];

    $datapre = "$indent\"$key\":";

    if (is_array($value)) {
      $data[] = "$datapre  {
" . fim_outputJson($value, $level + 1) . "
$indent}

";
    }
    else {
      if ($value === true) {
        $value = 'true';
      }
      elseif ($value === false) {
        $value = 'false';
      }
      elseif (is_string($value)) {
        $value = '"' . addcslashes($value,"\"\\") . '"';
      }
      if ($value == '') {
        $value = '""';
      }

      $data[] = "$datapre  $value";
    }
  }

  if (count($data) > 0) {
    $data = implode(",\n",$data);

    if ($level == 0) {
      return "{
  $data
  }";
    }
    else {
      return $data;
    }
  }
}



/**
 * Key Parser
 *
 * @param array $array
 * @param int $level
 * @return string
 * @author Joseph Todd Parsons <josephtparsons@gmail.com>
 */
function fim_outputKeys($array, $level = 0) { // Used only for creating documentation.
  global $config;

  $indent = '';

  for ($i = 0; $i < $level; $i++) {
    $indent .= '  ';
  }

  foreach ($array AS $key => $value) {
    $key = explode(' ', $key);
    $key = $key[0];

    $data .= "$indent<li>$key</li>\n";

    if (is_array($value)) {
      $data .= $indent . '  <ul>
' . fim_outputKeys($value, $level + 1) . $indent . '</ul>
';
    }
  }

  return $data;
}



/**
 * Output Using print_r
 * @param array $array
 */
function fim_outputArray() {
  global $config;

  print_r($array);
}



/**
 * HTML+XML Compact Function So Google's PageSpeed Likes FIM (also great if GZIP isn't available)
 *
 * @param string $data
 * @return string
 * @author Joseph Todd Parsons <josephtparsons@gmail.com>
 */
function fim_apiCompact($data) {
  global $config;

  if (isset($_REQUEST['fim3_format'])) {
    switch ($_REQUEST['fim3_format']) {
      case 'xml2': // Compact XML
      case 'xml': // No-Attribute XML (all data expressed as nodes)
      $data = preg_replace($config['compactXmlStringsFind'], $config['compactXmlStringsReplace'], $data);
      break;

      case 'json': // Javascript Object Notion
      default:
      $data = preg_replace($config['compactJsonStringsFind'], $config['compactJsonStringsReplace'], $data);
      break;
    }
  }
  else {
    $data = preg_replace($config['compactJsonStringsFind'], $config['compactJsonStringsReplace'], $data);
  }

  return $data;
}



/**
 * Pretty Size
 *
 * @param float $size
 * @return string
 * @author Joseph Todd Parsons <josephtparsons@gmail.com>
 */
function formatSize($size) {
  global $config;

  $suffix = 0;

  while ($size > $config['fileIncrementSize']) { // Increase the Byte Prefix, Decrease the Number (1024B = 1KiB)
    $suffix++;
    $size /= $config['fileIncrementSize'];
  }

  return $size . $config['fileSuffixes'][$suffix];
}



/**
 * Strict Sanitization of GET/POST/COOKIE Globals
 *
 * @param array data
 * @return array
 * @author Joseph Todd Parsons <josephtparsons@gmail.com>
 */
function fim_sanitizeGPC($data) {
  global $config;

  $metaDataDefaults = array(
    'type' => 'string',
    'require' => false,
    'context' => false,
  );

  foreach ($data AS $type => $entry) {
    $activeGlobal = array(); // Clear after each run.

    switch ($type) { // Set the GLOBAL to a local var for processing.
      case 'get':
      $activeGlobal = $_GET;
      break;

      case 'post':
      $activeGlobal = $_POST;
      break;

      case 'cookie':
      $activeGlobal = $_COOKIE;
      break;

      case 'request':
      $activeGlobal = $_REQUEST;
      break;
    }


    if (count($activeGlobal) > 0 && is_array($activeGlobal)) { // Make sure the active global is populated with data.
      foreach ($entry AS $indexName => $indexData) {
        $indexMetaData = $metaDataDefaults; // Store indexMetaData with the defaults.

        foreach ($indexData AS $metaName => $metaData) {
          switch ($metaName) {
            case 'type':

            switch ($metaData) {
              case 'string':
              $indexMetaData['type'] = 'string';
              break;

              case 'bool':
              $indexMetaData['type'] = 'bool';
              break;

              case 'int':
              $indexMetaData['type'] = 'int';
              break;
            }

            break;

            case 'valid':
            $indexMetaData['valid'] = $metaData;
            break;

            case 'require':
            $indexMetaData['require'] = $metaData;
            break;

            case 'context':
            $indexMetaData['context'] = array(
              'cast' => '',
              'filter' => '',
              'evaltrue' => false,
            );

            foreach ($metaData AS $contextname => $contextdata) {

              switch ($contextname) {
                case 'type': // This is the original typecast, with some special types defined. While GPC variables are best interpretted as strings, this goes further and converts the string to a more proper format.
                switch ($contextdata) {
                  case 'csv': // e.g. "1,2,3" "1,ab3,455"
                  $indexMetaData['context']['cast'] = 'csv';
                  break;

                  case 'array': // e.g. "1=1,2=0,3=0" "1=1,ab3=1,455=0"
                  $indexMetaData['context']['cast'] = 'array';
                  break;

                  case 'bool':
                  $indexMetaData['context']['cast'] = 'bool';
                  break;

                  case 'int':
                  $indexMetaData['context']['cast'] = 'int';
                  break;
                }
                break;

                case 'filter': // This is an additional filter applied to data that uses the "csv" context type (and possibly more in the future).
                switch ($contextdata) {
                  case 'int':
                  $indexMetaData['context']['filter'] = 'int';
                  break;
                }
                break;

                case 'evaltrue': // This specifies whether all subvalus of a context must be true. For instance, assuming we use an integer filter 0 would be removed if this was true.
                $indexMetaData['context']['evaltrue'] = (bool) $contextdata;
                break;
              }
            }
            break;

            case 'default':
            $indexMetaData['default'] = $metaData;
            break;
          }
        }


        if (isset($activeGlobal[$indexName])) { // Only typecast if the global is present.
          switch ($indexMetaData['type']) {
            case 'int':
            $activeGlobal[$indexName] = (int) $activeGlobal[$indexName];
            break;

            case 'bool':
            $activeGlobal[$indexName] = (bool) $activeGlobal[$indexName];
            break;

            case 'string':
            $activeGlobal[$indexName] = (string) $activeGlobal[$indexName];
            break;
          }

          if (isset($indexMetaData['valid'])) { // If a list of valid values is specified...
            if (is_array($indexMetaData['valid'])) { // And if that list is an array...
              if (in_array($activeGlobal[$indexName], $indexMetaData['valid'])) { // And if the value specified is in the list of valid values...
                // Do Nothing; We're Good
              }
              else {
                if ($indexMetaData['require']) { // If the value is required but not valid...
                  throw new Exception('Required data not valid.'); // Throw an exception.
                }
                elseif (isset($indexMetaData['default'])) { // If the value has a default but is not valid...
                  $activeGlobal[$indexName] = $indexMetaData['default']; // Set the value to the default.
                }
              }
            }
            else {
              throw new Exception('Defined valid values do not corrospond to recognized data type (array).'); // Throw an exception since valid values are not properly defined.
            }
          }
        }
        else {
          if ($indexMetaData['require']) { // If the value is required but not specified...
            throw new Exception('Required data not present.'); // Throw an exception.
          }
          elseif (isset($indexMetaData['default'])) { // If the value has a default and is not specified...
            $activeGlobal[$indexName] = $indexMetaData['default']; // Set the value to the default.
          }
          else {
            $activeGlobal[$indexName] = false;
          }
        }

        switch($indexMetaData['context']['cast']) {
          case 'csv':
          $newData[$indexName] = fim_arrayValidate(explode(',', $activeGlobal[$indexName]), $indexMetaData['context']['filter'],($indexMetaData['context']['evaltrue'] ? false : true)); // If a cast is set for a CSV list, explode with a comma seperator, make sure all values corrosponding to the filter (int, bool, or string - the latter pretty much changes nothing), and if evaltrue is true, then the preserveAll flag would be false, and vice-versa.
          break;

          case 'array':
          $arrayParts = explode(',', $activeGlobal[$indexName]);
          $arrayKeys = array();
          $arrayVals = array();

          foreach ($arrayParts AS $arrayEntry) {
            $arrayEntry = explode('=', $arrayEntry);

            if (count($arrayEntry) !== 2) continue;

            $arrayKeys[] = $arrayEntry[0];
            $arrayVals[] = $arrayEntry[1];
          }

          $arrayVals = fim_arrayValidate($arrayVals, $indexMetaData['context']['filter'], ($indexMetaData['context']['evaltrue'] ? false : true));
          $newData[$indexName] = array_combine($arrayKeys, $arrayVals);
          break;

          case 'int':
          if ($indexMetaData['context']['evaltrue']) { // Only include the value if it is true.
            if ((int) $activeGlobal[$indexName]) { // If true/non-zero...
              $newData[$indexName] = (int) $activeGlobal[$indexName]; // Append value as integer-cast.
            }
          }
          else { // Include the value whether true or false.
            $newData[$indexName] = (int) $activeGlobal[$indexName]; // Append value as integer-cast.
          }
          break;

          case 'bool': // I'm not sure what to do here yet, really...
          $trueValues = array('true',1,true,'1');
          $falseValues = array('false',0,false,'0');

          if (in_array($activeGlobal[$indexName], $trueValues,true)) {
            $newData[$indexName] = true;
          }
          elseif (in_array($activeGlobal[$indexName], $falseValues,true)) {
            $newData[$indexName] = false;
          }
          elseif (isset($indexMetaData['default'])) {
            $newData[$indexName] = (bool) $indexMetaData['default'];
          }
          else {
            $newData[$indexName] = false;
          }
          break;

          default: // String or otherwise.
          $newData[$indexName] = (string) $activeGlobal[$indexName]; // Append value as string-cast.
          break;
        }
      }
    }
  }

  return $newData;
}



/**
 * A function equvilent to an IF-statement that returns a true or false value. It is similar to the function in most spreadsheets (EXCEL, LibreOffice CALC, Lotus 123). TRANSITIONAL
 *
 * @param string $condition - The condition that will be evaluated. It must be a string.
 * @param string $true - A string to return if the above condition evals to true.
 * @param string $false - A string to return if the above condition evals to false.
 * @return bool - true on success, false on failure
 * @author Joseph Todd Parsons <josephtparsons@gmail.com>
 */
function fim_iif($condition, $true, $false) {
  global $config;

  if (eval('return ' . stripslashes($condition) . ';')) { // Does the condition eval to true?
    return $true; // Return the true string.
  }
  else {
    return $false; // Return the false string.
  }
}



/**
 * Determines if an array contains an array.
 *
 * @param array $array
 * @return bool - True if the array contains array, false otherwise.
 * @author Joseph Todd Parsons <josephtparsons@gmail.com>
 */
function hasArray($array) {
  global $config;

  foreach ($array AS $key => $value) { // Run through each entry of the array.
    if (is_array($value)) { // If the value is an array, return true.
      return true;
    }
  }

  return false; // Since we haven't already found an array, return false.
}



/**
 * Implements PHP's explode with support for escaped delimeters.
 *
 * @param string delimiter
 * @param string string
 * @return array
 * @author <adrian@bilsoftware.com>
 * @source http://www.php.net/manual/en/function.explode.php#89138
 */
function fim_explodeEscaped($delimiter, $string) {
  global $config;

  $exploded = explode($delimiter, $string);
  $fixed = array();

  for ($k = 0, $l = count($exploded); $k < $l; ++$k) {
    if ($exploded[$k][strlen($exploded[$k]) - 1] == '\\') {
      if ($k != 0 && isset($exploded[$k][strlen($exploded[$k - 1]) - 1])) {
        if ($exploded[$k][strlen($exploded[$k - 1]) - 1] == '\\') {
          $fixed[] = '\\';

          continue;
        }
      }

      if ($k + 1 >= $l) {
        $fixed[] = trim($exploded[$k]);
        break;
      }
      $exploded[$k][strlen($exploded[$k]) - 1] = $delimiter;
      $exploded[$k] .= $exploded[$k + 1];
      array_splice($exploded, $k + 1, 1);
      --$l;
      --$k;
    }
    else {
      $fixed[] = trim($exploded[$k]);
    }
  }
  return $fixed;
}

function fim_errorHandler($errno, $errstr, $errfile, $errline) {
  global $config;

  if (!(error_reporting() & $errno)) { // The error is not to be reported.
    return;
  }

  switch ($errno) {
    case E_USER_ERROR:
    ob_end_clean(); // Clean the output buffer and end it. This means when we show the error in a second, there won't be anything else with it.

    die(nl2br('<fieldset><legend>Unrecoverable Error</legend><strong>Error Text</strong><br />' . $errstr . '<br /><br /><strong>What Should I Do Now?</strong><br />' . ($config['email'] ? 'You may wish to <a href="mailto:' . $config['email'] . '">notify the administration</a> of this error.' : 'No contact was specified for this installation, so try to wait it out.')  . '<br /><br /><strong>Are You The Host?</strong><br />Server errors are often database related. These may result from improper installation or a corrupted database. The documentation may provide clues, however.</fieldset>'));

    if ($config['email']) {
      mail($config['email'], 'FIM3 System Error [' . $_SERVER['SERVER_NAME'] . ']', 'The following error was encountered by the server located at ' . $_SERVER['SERVER_NAME'] . ':<br /><br />' . $errstr);
    }
    break;

    default:
    // Do Nothing
    break;
  }

  return true; // Don't execute PHP internal error handler
}

function fim_flush() {
  flush();

  if (ob_get_level()) {
    ob_fim_flush();
  }
}
?>