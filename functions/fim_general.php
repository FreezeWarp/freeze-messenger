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










/********************************************************
************************ START **************************
******************** IM Functions ***********************
*********************************************************/



/**
 * Determines if a user has permission to do an action in a room.
 *
 * @param array $roomData - An array containing the room's data; indexes roomId, allowedUsers, allowedGroups, moderators, owner, options, defaultPermissions, type, parentalAge, and parentalFlags are required; index roomUsersList is required if type is "private" or "otr".
 * @param array $userData - An array containing the user's data; indexes userId, adminPrivs, userPrivs, parentalAge, and parentalFlags are required.
 * @param string $type - Either "view", "post", "moderate", or "admin", this defines the action the user is trying to do.
 * @param bool $trans - If true, return will be an information array; otherwise bool.
 *
 * @global bool $banned - Whether or not the user is banned outright.
 * @global array $superUsers - The list of superUsers.
 * @global bool $valid - Whether or not the user has a valid login (required for posting, etc.)
 * @global string $sqlPrefix
 *
 * @return mixed - Bool if $trans is false, array if $trans is true.
 *
 * @author Joseph Todd Parsons <josephtparsons@gmail.com>
 */
function fim_hasPermission($roomData, $userData, $type = 'post', $quick = false) {
  global $sqlPrefix, $banned, $loginConfig, $valid, $database, $config, $generalCache;
  
  /* START TRANSITIONAL */
  /* We'll be removing this soon, but I didn't want to do another overhaul yet. */
  if (is_int($roomData)) {
    $roomData = $generalCache->getRooms($roomData);
  }
  
  if (is_int($userData)) {
    $userData = $generalCache->getUsers($roomData);
  }
  /* END TRANSITIONAL */

  /* START COMPILE VERBOSE -- TODO: Should be able to detect based on ID. */
  if (!isset($roomData['type'])) throw new Exception('hasPermission requires roomData[type] to be defined.');
  elseif (!isset($userData['userId'])) throw new Exception('hasPermission requires roomData[type] to be defined.');
  /* END COMPILE VERBOSE */

  if ($roomData['type'] === 'otr' || $roomData['type'] === 'private') { // We are doing this in hasPermission itself to allow for hooks that might, for instance, deny permission to certain users based on certain criteria.
    /* START COMPILE VERBOSE */
    if (!isset($roomData['roomUsersList'])) throw new Exception('hasPermission requires roomData[roomUsersList] to be defined.');
    /* END COMPILE VERBOSE */

    if (in_array($userData['userId'], $roomData['roomUsersList'])) { // The logic with private rooms is fairly self-explanatory: if the user is in the roomUsersList, they're allowed. Otherwise, nope.
      if ($quick) return true;
      else return array(true, '', 0);
    }
    else {
      if ($quick) return false;
      else return array(false, '', 0);
    }
  }
  else {
    /* START COMPILE VERBOSE */
    /* Make Sure All Data is Present */
    if (!$roomData['roomId']) { // If the room is not valid...
      if ($quick) return false;
      else return array(false, 'invalidRoom', 0);
    }
    elseif (!isset($roomData['parentalFlags'], $roomData['parentalAge'])) throw new Exception('hasPermission requires roomData[parentalFlags] and roomData[parentalAge] to be defined.');
    elseif (!isset($roomData['defaultPermissions'], $roomData['options'], $roomData['owner'])) throw new Exception('hasPermission requires roomData[defaultPermissions], roomData[options], and roomData[owner]'); // If the default permissions index is missing, through an exception.
    elseif (!isset($userData['parentalAge'], $userData['parentalFlags'])) throw new Exception('hasPermission requires userData[parentalAge] and userData[parentalFlags] to be defined.');
    elseif (!isset($userData['adminPrivs'])) throw new Exception('hasPermission requires userData[adminPrivs] to be defined.');
    /* END COMPILE VERBOSE */


    /* Initialise Variables */
    $isAdmin = false;
    $isModerator = false;
    $isAllowedUser = false;
    $isAllowedGroup = false;
    $isOwner = false;
    $isRoomDeleted = false;
    $parentalBlock = false;
    $kick = false;
    $allowViewing = false;

    $isAllowedUserOverride = false;

    $reason = ''; // Create an empty string for the reason.
    $type = (array) $type; // Type cast the type as an array.

    $permMap = array('view' => 1, 'post' => 2, 'moderate' => 8, 'admin' => 128); // These are the corrosponding database permissions for each "type".


    /* Get the User's Kick Status */
    if ($userData['userId'] > 0) { // Is their userId non-zero?
      if ($generalCache->getKicks($roomData['roomId'], $userData['userId'])) $kick = true; // We're kicked!
      else $kick = false; // We're not kicked!
    }


    /* Is the User the Room's Owner/Creator */
    if ($roomData['owner'] === $userData['userId']
      && $roomData['owner'] > 0) {
      $isOwner = true;
    }


    /* Is the Room a Private Room or Deleted? */
    if ($roomData['options'] & 4) $isRoomDeleted = true; // The room is deleted.


    /* Allow Viewing? */
    if ($roomData['options'] & 32) $allowViewing = true; // The room allows viewing by unathourised users.


    /* Is the user a super user? */
    if (isset($loginConfig['superUsers'])) {
      if (in_array($userData['userId'], (array) $loginConfig['superUsers']) || $userData['adminPrivs'] & 1) {
	$isAdmin = true;
      }
    }


    /* Is the user banned by parental controls? */
    if ($config['parentalEnabled'] === true) {
      if ($roomData['parentalAge'] > $userData['parentalAge']) $parentalBlock = true;
      elseif (fim_inArray(explode(',', $userData['parentalFlags']), explode(',', $roomData['parentalFlags']))) $parentalBlock = true;
    }


    /* Run Through Each Type Specified (if a string is specified, it will be converted to an array first) */
    foreach ((array) $type AS $type2) {
      if (!in_array($type2, array('post', 'view', 'moderate', 'admin'))) throw new Exception('hasPermission type "' . $type2 . '" unrecognised.'); // Transitional. TODO: Remove

      /* Is the User an Allowed User? */
      foreach(array('user', 'admingroup', 'group') AS $type3) {
        if ($generalCache->getPermissions($roomData['roomId'], $type3, $userData['userId']) & $permMap[$type2]) { $isAllowedUser = true; }
        else { $isAllowedUserOverride = true; break; } // If a group is granted access but a user is forbidden, the user status is considered final. Likewise, if a social group is granted access but an admin group is restricted, the admin group is considered final.
      }
      if (($roomData['defaultPermissions'] & $permMap[$type2]) && !$isAllowedUserOverride) {
        $isAllowedUser = true;
      }


      /* Each Type Has a Unique Set of Conditions */
      if ($type2 === 'post') {
        if ($banned) {                                           $roomValid['post'] = false; $reason = 'banned'; } // admins can disable their own ban
        elseif (!$valid) {                                       $roomValid['post'] = false; $reason = 'invalid'; }
        elseif ($isAdmin || $isOwner || $isModerator) {          $roomValid['post'] = true; }
        elseif ($kick) {                                         $roomValid['post'] = false; $reason = 'kicked'; } // admin overrides this
        elseif ($parentalBlock) {                                $roomValid['view'] = false; $reason = 'parental'; } // admin overrides this
        elseif ($isRoomDeleted) {                                $roomValid['post'] = false; $reason = 'deleted'; } // admin overrides this
        elseif ($isAllowedUser || $isAllowedGroup) {             $roomValid['post'] = true; }
        else {                                                   $roomValid['post'] = false; $reason = 'general'; }
      }

      if ($type2 === 'view') {
        if ($banned) {                                           $roomValid['moderate'] = false; $reason = 'banned';  } // admins can disable their own ban
        elseif ($isAdmin || $isOwner || $isModerator) {          $roomValid['view'] = true; }
        elseif ($isRoomDeleted) {                                $roomValid['view'] = false; $reason = 'deleted'; } // admin overrides this
        elseif ($parentalBlock) {                                $roomValid['view'] = false; $reason = 'parental'; } // admin overrides this
        elseif ($isAllowedUser || $isAllowedGroup) {             $roomValid['view'] = true; }
        elseif ($allowViewing) {                                 $roomValid['view'] = true; }
        else {                                                   $roomValid['view'] = false; $reason = 'general'; }
      }

      if ($type2 === 'moderate') { // parental block and kick do not apply to moderator, ignore
        if ($banned) {                                           $roomValid['moderate'] = false; $reason = 'banned';  } // admins can disable their own ban
        elseif (!$valid) {                                       $roomValid['moderate'] = false; $reason = 'invalid'; }  // TODO: redundant?
        elseif ($isOwner || $isModerator || $isAdmin) {          $roomValid['moderate'] = true;                       }
        else {                                                   $roomValid['moderate'] = false; $reason = 'general'; }
      }

      if ($type2 === 'admin') { // parental block and kick do not apply to admin, ignore
        if ($banned) {                                           $roomValid['admin'] = false; $reason = 'banned';  } // admins can disable their own ban
        elseif (!$valid) {                                       $roomValid['admin'] = false; $reason = 'invalid'; } // TODO: redundant?
        elseif ($isAdmin) {                                      $roomValid['admin'] = true;                       }
        else {                                                   $roomValid['admin'] = false; $reason = 'general'; }
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
}


/**
 * Determine if the active user is a superuser.
 *
 * @return bool - True if the user is super, false otherwise.
 *
 * @author Joseph Todd Parsons <josephtparsons@gmail.com>
 */
function fim_isSuper() {
  global $loginConfig;

  if (in_array(FIM_ACTIVEUSERID, $loginConfig['superUsers'])) return true; // The use of FIM_ACTIVEUSERID instead of $user['userId'] is as a precaution against plugins changing it for whatever reason.
  else return false;
}



/*
* messageRange
* 1 = {
*   0 :
*   100 :
*   200 :
*   300 :
* }
*
* timeRange
* 0 = 0
* 1 = 400
*
*/
function fim_getMessageRange($roomId, $startId, $endId, $startDate, $endDate) {

}










/********************************************************
************************ START **************************
**************** Encoding & Encryption ******************
*********************************************************/


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
function fim_decrypt($message, $index = array('text')) {
  global $salts, $config;

  if (!isset($message['salt'], $message['iv'])) throw new Exception('fim_decrypt requires message[salt] and message[iv]'); // Make sure the proper indexes exist (just in case).

  if ($message['salt'] && $message['iv']) { // Make sure both the salt and the IV are non-false.
    $salt = $salts[$message['salt']]; // Get the proper salt.

    if ($index) { // If indexes are specified...
      foreach ((array) $index AS $index2) { // Run through each index. If the specified index variable is a string instead of an array, we will cast it as an array ("example" becomes array("example")).
	if (!isset($message[$index2])) { // If the index is not in the message, throw an exception.
	  throw new Exception('Index not found: ' . $index2);
	}

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









/********************************************************
************************ START **************************
*********************** Wrappers ************************
*********************************************************/



/**
 * Generates a SHA256 using whatever methods are available. If no valid function can be found, the data will be returned unhashed.
 *
 * @param string $data - The data to encrypt.
 * @return string - Encrypted data.
 * @author Joseph Todd Parsons <josephtparsons@gmail.com>
 */
function fim_sha256($data) {
  global $config;

  if (function_exists('hash') && in_array('sha256', hash_algos())) return hash('sha256', $data); // hash() is available in PHP 5.1.2+, or in PECL Hash 1.1. Algorithms vary, so we must make sure sha256 is one of them.
  elseif (function_exists('mhash') && defined('MHASH_SHA256')) return mhash(MHASH_SHA256, $data); // mhash() is available in pretty much all versions of PHP, but the SHA256 algo may not be available.
  else { // Otherwise, we use a third-party SHA256 library. Expect slowness. [TODO: Test]
    require('functions/sha256.php'); // Require SHA256 class provided by NanoLink.ca.

    $obj = new nanoSha2();
    $shaStr = $obj->hash($data);
  }
}


/**
 * A wrapper for rand and mt_rand, using whichever is available.
 *
 * @param string $data - The data to encrypt.
 * @return string - Encrypted data.
 * @author Joseph Todd Parsons <josephtparsons@gmail.com>
 */
function fim_rand($min, $max) {
  if (function_exists('mt_rand')) return mt_rand($min, $max); // Proper hardware-based rand, actually works.
  elseif (function_exists('rand')) return rand($min, $max); // Standard rand, not well seeded.
  else return $min; // Though it should never happened, applications should still /run/ if no rand function exists. Keep this in mind when using fim_rand.
}



/**
 * Retrieves a hook from the database.
 *
 *
 * @param string $name
 * @return evalcode (string)
 * @author Joseph Todd Parsons <josephtparsons@gmail.com>
 */
function hook($name) {
  global $hooks, $disableHooks;


  if ($disableHooks) return false; // If hooks are disabled, then return false.
  elseif (isset($hooks[$name])) { // If the hook is set...
    if (strlen($hooks[$name]) > 0) return $hook; // If the hook is not empty, return the code to eval.
    else return false; // Otherwise return false.
  }
  else return false; // And if the hook isn't set, return false.
  
  // It doesn't matter whar I say.
  // So long as I sing with in-flec-tion.
  // That makes you feel that I'll convey.
  // Some inner true of vast reflection!
  // But I've said nothing so far.
  // And I can keep it up for s long as it takes.
  // And it don't matter who you are.
  // If I'm doing my job then it's your resolve that breaks.
}







/********************************************************
************************ START **************************
********************* API Functions *********************
*********************************************************/


/**
 * Encodes a string as specifically-formatted XML data, converting "&", "'", '"', "<", and ">" to their equivilent values.
 *
 * @param string $data - The data to be encoded.
 * @return string - Encoded data.
 * @author Joseph Todd Parsons <josephtparsons@gmail.com>
 */
function fim_encodeXml($data) {
  global $config;

  if (!isset($config['encodeXmlEntitiesFind'], $config['encodeXmlEntitiesReplace'])) throw new Exception('Config data invalid: missing config[encodeXmlEntitiesFind] or config[encodeXmlEntitiesReplace]');

  $data = str_replace($config['encodeXmlEntitiesFind'], $config['encodeXmlEntitiesReplace'], $data); // Replace the entities defined in $config (these are usually not changed).
  $data = str_replace("\n", '&#xA;', $data);

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

  if (!isset($config['encodeXmlAttrEntitiesFind'], $config['encodeXmlAttrEntitiesReplace'])) throw new Exception('Config data invalid: missing config[encodeXmlAttrEntitiesFind] or config[encodeXmlAttrEntitiesReplace]');

  $data = str_replace($config['encodeXmlAttrEntitiesFind'], $config['encodeXmlAttrEntitiesReplace'], $data); // Replace the entities defined in $config (these are usually not changed).

  return $data;
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
  header('FIM-API-VERSION: 3b4dev');

  if (isset($_REQUEST['fim3_format'])) {
    switch ($_REQUEST['fim3_format']) {
      case 'phparray':                                                return fim_outputArray($data); break; // print_r
      case 'keys':                                                    return fim_outputKeys($data);  break; // HTML List format for the keys only (documentation thing)
      case 'xml2':          header('Content-type: application/xml');  return fim_outputXml2($data); break; // Compact XML
      case 'xml':           header('Content-type: application/xml');  return fim_outputXml($data); break; // No-Attribute XML (all data expressed as nodes)
      case 'jsonp':         header('Content-type: application/json'); return 'fim3_jsonp.parse(' . fim_outputJson($data) . ')'; break; // Javascript Object Notion for Cross-Origin Requests
      case 'json': default: header('Content-type: application/json'); return fim_outputJson($data); break; // Javascript Object Notion
    }
  }
  else {
    header('Content-type: application/json');

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

  $indent = '';
  $data = '';

  for ($i = 0; $i < $level; $i++) $indent .= '  '; // Indent at beginning.

  foreach ($array AS $key => $value) {
    $key = explode(' ', $key);
    $key = $key[0];

    $data .= "$indent<$key>\n";

    if (is_array($value)) $data .= fim_outputXml($value, $level + 1);
    else {
      if ($value === true)       $value = 'true';
      elseif ($value === false)  $value = 'false';
      elseif (is_string($value)) $value = fim_encodeXml($value);

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

  $indent = '';
  $data = '';

  for ($i = 0; $i < $level; $i++) $indent .= '  ';

  foreach ($array AS $key => $value) {
    $key = explode(' ', $key);
    $key = $key[0];

    if (is_array($value)) {
      if (fim_hasArray($value)) {
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

        foreach ($value AS $key => $value2) $data .= " {$key}=\"" . fim_encodeXmlAttr($value2) . "\"";

        $data .= " />\n";
      }
    }
    else {
      if (empty($value)) $data .= "{$indent}<$key />\n";
      else {
        if ($value === true)       $value = 'true';
        elseif ($value === false)  $value = 'false';
        elseif (is_string($value)) $value = fim_encodeXml($value);

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

  $data = array();
  $indent = '';

  for ($i = 0; $i <= $level; $i++) $indent .= '  ';

  foreach ($array AS $key => $value) {
//    $key = explode(' ', $key);
//    $key = $key[0];

    $datapre = "$indent\"$key\":";

    if (is_array($value)) {
      $data[] = "$datapre  {
" . fim_outputJson($value, $level + 1) . "
$indent}";
    }
    else {
      if ($value === true)       $value = 'true';
      elseif ($value === false)  $value = 'false';
      elseif (is_string($value)) $value = '"' . str_replace("\n", '\n', addcslashes($value,"\"\\")) . '"';

      if ($value == '')          $value = '""';

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

  for ($i = 0; $i < $level; $i++) $indent .= '  ';

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
 * @author Joseph Todd Parsons <josephtparsons@gmail.com>
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
      case 'jsonp':
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







/********************************************************
************************ START **************************
******************** Misc Functions *********************
*********************************************************/


/**
 * A function equvilent to obtaining the index value of an array.
 *
 * @param array $array
 * @param mixed $index
 * @return mixed
 * @author Joseph Todd Parsons <josephtparsons@gmail.com>
 */
function fim_indexValue($array, $index) {
  return $array[$index];
}


/**
 * Determines if any value in an array is found in a seperate array.
 *
 * @param array $needle - The array that contains all values that will be applied to $haystack
 * @param array $haystack - The matching array.
 * @param bool $all - Only return true if /all/ values in $needle are in $haystack.
 * @return bool
 *
 * @author Joseph Todd Parsons <josephtparsons@gmail.com>
 */
function fim_inArray($needle, $haystack, $all = false) {
  if (!$haystack) return false; // If the haystack is not valid, return false.
  elseif (!$needle) return false; // If the needle is not valid, return false.
  else {
    foreach($needle AS $need) { // Run through each entry of the needle
      if ($all) { // All values must be found.
        if (!$need) return false; // If the needle value is false, skip it.
        if (in_array($need, $haystack)) continue; // If the needle is in the haystack, return true.
      }
      else { // Only one value must be found.
        if (!$need) continue; // If the needle value is false, skip it.
        if (in_array($need, $haystack)) return true; // If the needle is in the haystack, return true.
      }
    }

    if ($all) {
      return true; // If we have found all values, return true.
    }
    else {
      return false; // If we haven't found a value, return false.
    }
  }
}


/**
 * Determines if an array contains an array.
 *
 * @param array $array
 * @return bool - True if the array contains array, false otherwise.
 * @author Joseph Todd Parsons <josephtparsons@gmail.com>
 */
function fim_hasArray($array) {
  global $config;

  foreach ($array AS $key => $value) { // Run through each entry of the array.
    if (is_array($value)) return true; // If the value is an array, return true.
  }

  return false; // Since we haven't already found an array, return false.
}


/**
 * Implements PHP's explode with support for escaped delimeters. You can not use as a delimiter or escape character 'µ', 'ñ', or 'ø' (which are used in place of '&', '#', ';' to free up those characters).
 * If the escaepChar occurs twice in a row, it will be understood as having been twice-escaped, and handled accordingly.
 *
 * @param string delimiter
 * @param string string
 * @param string escapeChar - The character that escapes the string.
 * @return string
 */
function fim_explodeEscaped($delimiter, $string, $escapeChar = '\\') {
  $string = str_replace($escapeChar . $escapeChar, fim_encodeEntities($escapeChar), $string);
  $string = str_replace($escapeChar . $delimiter, fim_encodeEntities($delimiter), $string);
  return array_map('fim_decodeEntities', explode($delimiter, $string));
}


/**
 * A function equvilent to an IF-statement that returns a true or false value. It is similar to the function in most spreadsheets (EXCEL, LibreOffice CALC).
 * Note that this function is DEPRECATED for all internal commands, since it really doesn't serve any purpose, but it will be kept for 3rd party plugins to use as documented.
 *
 * @param string $condition - The condition that will be evaluated. It must be a string.
 * @param string $true - A string to return if the above condition evals to true.
 * @param string $false - A string to return if the above condition evals to false.
 * @return bool - true on success, false on failure
 * @author Joseph Todd Parsons <josephtparsons@gmail.com>
 */
function fim_iif($condition, $true, $false) {
  global $config;

  if (eval('return ' . stripslashes($condition) . ';')) return $true; // If the string evals to true, return the true string.
  else return $false; // Return the false string.
}


/**
 * Converts a date of birth to age.
 *
 * @author Joseph Todd Parsons <josephtparsons@gmail.com>
 */
function fim_dobToAge($date) {
  return floor((time() - $date) / (60 * 60 * 24 * 365));
}


/**
 * Pretty Size
 *
 * @param float $size
 * @return string
 * @author Joseph Todd Parsons <josephtparsons@gmail.com>
 */
function fim_formatSize($size) {
  global $config;

  $suffix = 0;

  while ($size > $config['fileIncrementSize']) { // Increase the Byte Prefix, Decrease the Number (1024B = 1KiB)
    $suffix++;
    $size /= $config['fileIncrementSize'];
  }

  return round($size, 2) . $config['fileSuffixes'][$suffix];
}


/**
 * Encode entities using a custom format.
 *
 * @param string string - String to encode.
 * @param array find - An array of characters to replace in the entity string (in most cases, should include only "&", "#", ";").
 * @param array replace - An array of characters to replace with in the entity string (in most cases, this should include characters that would rarely be used for exploding a string).
 * @return string
 */
function fim_encodeEntities($string, $find = array('&', '#', ';'), $replace = array('µ', 'ñ', 'ó')) {
  return str_replace($find, $replace, mb_encode_numericentity($string, array(0x0, 0x10ffff, 0, 0xffffff), "UTF-8"));
}


/**
 * Decode entities using a custom format.
 *
 * @param string string - String to encode.
 * @param array replace - An array of characters to replace in the entity string (in most cases, should include only "&", "#", ";").
 * @param array find - An array of characters to replace with in the entity string (in most cases, this should include characters that would rarely be used for exploding a string).
 * @return string
 */
function fim_decodeEntities($string, $replace = array('µ', 'ñ', 'ó'), $find = array('&', '#', ';')) {
  return mb_decode_numericentity(str_replace($replace, $find, $string), array(0x0, 0x10ffff, 0, 0xffffff), "UTF-8");
}

function fim_startsWith($haystack, $needle) {
  return strpos($haystack, $needle, 0) === 0;
}

function fim_endsWith($haystack, $needle) {
  return strrpos($haystack, $needle, 0) === (strlen($haystack) - strlen($needle));
}











/********************************************************
************************ START **************************
**************** Data Handling Functions ****************
*********************************************************/


/**
 * Converts a request string to an array. The request string must be able to be urldecoded (thus, "%" characters must be urlencoded, though "&" and "=" can be included via escaping).
 *
 * @param string string
 * @return array
 * @author Joseph Todd Parsons <josephtparsons@gmail.com>
 */
function fim_requestBodyToGPC($string) {
  $arrayEntries = explode('&', $string);
  $array = array();

  foreach ($arrayEntries AS $arrayEntry) {
    $arrayEntryParts = explode('=', $arrayEntry);
    $array[urldecode($arrayEntryParts[0])] = urldecode($arrayEntryParts[1]);
  }

  return $array;
}


/**
 * Strict Sanitization of GET/POST/COOKIE Globals
 *
 * @param array data
 * @return array
 * @author Joseph Todd Parsons <josephtparsons@gmail.com>
 */
function fim_sanitizeGPC($type, $data) {
  global $config;

  /* Get The Request Body */
  if ($type === 'p' || $type === 'post' || $type === 'u' || $type === 'put' || $type === 'd' || $type === 'delete') $requestBody = file_get_contents('php://input'); // Only get php://input if we want to. Otherwise, it creates some extra overhead we could do without.
  else $requestBody = '';


  /* Define Defaults */
  $metaDataDefaults = array(
    'cast' => 'string',
    'require' => false,
    'trim' => false,
    'filter' => '',
    'evaltrue' => false,
    
    // Others: min, max, valid, default
  );


  /* Store Request Body */
  if (strlen($requestBody) > 0) { // If a request body exists, we will use it instead of PHP's generated superglobals. This allows for further REST compatibility. We will, however, only use it for GET and POST requests, at the present time.
    switch ($type) {
      case 'p': case 'post': // POST can use a request body; it is ultimately the preferrence of the implementor, and for now we will prefer it as long as a REQUEST body exists. (TODO: Should a REQUEST body ever not exist in this case?)
      if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $activeGlobal = fim_requestBodyToGPC($requestBody);
      }
      break;
      case 'u': case 'put': // PUT __requires__ a request body. It is not currently supported, however.
      if ($_SERVER['REQUEST_METHOD'] === 'PUT') {
        $activeGlobal = $requestBody;
      }
      break;
      case 'd': case 'delete': // DELETE is not currently supported.
      break;
    }
  }
  else { // Request information is stored in superglobals; get that information.
    switch ($type) { // Set the GLOBAL to a local var for processing.
      case 'g': case 'get': $activeGlobal = $_GET; break;
      case 'p': case 'post': $activeGlobal = $_POST; break;
      case 'c': case 'cookie': $activeGlobal = $_COOKIE; break;
      case 'r': case 'request': $activeGlobal = $_REQUEST; break;
      default:
        throw new Exception('Invalid type in fim_sanitizeGPC');
        return false;
      break;
    }
  }

  /* Process Request Body */
  if (!is_array($activeGlobal)) { // Make sure the active global is populated with data.
    $activeGlobal = array();
  }

  foreach ($data AS $indexName => $indexData) {
    $indexMetaData = $metaDataDefaults; // Store indexMetaData with the defaults.
    
    /* Validate Metadata */
    foreach ($indexData AS $metaName => $metaData) {
      if ($metaName === 'default') {
        // Do nothing.
      }
      elseif ($metaName === 'require' || $metaName === 'trim' || $metaName === 'evaltrue') {
        if (!is_bool($metaData)) throw new Exception('Invalid "' . $metaName . '" in data in fim_sanitizeGPC');
      }
      elseif ($metaName === 'valid') {
        if (!is_array($metaData)) throw new Exception('Invalid "' . $metaName . '" in data in fim_sanitizeGPC');
      }
      elseif ($metaName === 'min' || $metaName === 'max') {
        if (!is_numeric($metaData)) throw new Exception('Invalid "' . $metaName . '" in data in fim_sanitizeGPC');
      }
      elseif ($metaName === 'filter') {
        if (!in_array($metaData, array('', 'int', 'bool', 'ascii128', 'alphanum'))) throw new Exception('Invalid "filter" in data in fim_sanitizeGPC');
      }
      elseif ($metaName === 'cast') {
        if (!in_array($metaData, array('int', 'bool', 'string', 'csv', 'array'))) throw new Exception('Invalid "cast" in data in fim_sanitizeGPC');
      }
      else {
        throw new Exception('Unrecognised metadata: ' . $metaName); // TODO: Allow override/etc.
      }
      
      $indexMetaData[$metaName] = $metaData;
    }

    /* Process Global */
    if (isset($activeGlobal[$indexName])) { // Only typecast if the global is present.
      if (isset($indexMetaData['valid'])) { // If a list of valid values is specified...
        if (is_array($indexMetaData['valid'])) { // And if that list is an array...
          if (in_array($activeGlobal[$indexName], $indexMetaData['valid'])) { // And if the value specified is in the list of valid values...
            // Do Nothing; We're Good
          }
          else {
            if ($indexMetaData['require']) throw new Exception('Required data not valid.'); // If the value is required but not valid, throw an exception.
            elseif (isset($indexMetaData['default'])) $activeGlobal[$indexName] = $indexMetaData['default']; // If the value has a default but is not valid, set it to the default.
          }
        }
        else {
          throw new Exception('Defined valid values do not corrospond to recognized data type (array).'); // Throw an exception since valid values are not properly defined.
        }
      }
    }
    else {
      if ($indexMetaData['require']) { // If the value is required but not specified...
        throw new Exception('Required data not present (index ' . $indexName . ').'); // Throw an exception.
      }
      elseif (isset($indexMetaData['default'])) { // If the value has a default and is not specified...
        $activeGlobal[$indexName] = $indexMetaData['default']; // Set the value to the default.
      }
      else {
        continue; // The entry is not set and won't be returned in $request.
      }
    }
    
    if ($indexMetaData['trim']) { // Trim
      $activeGlobal[$indexName] = trim($activeGlobal[$indexName]);
    }

    switch($indexMetaData['cast']) {
      case 'csv': // If a cast is set for a CSV list, explode with a comma seperator, make sure all values corrosponding to the filter (int, bool, or string - the latter pretty much changes nothing), and if evaltrue is true, then the preserveAll flag would be false, and vice-versa.
      $newData[$indexName] = fim_arrayValidate(
        explode(',', $activeGlobal[$indexName]),
        $indexMetaData['filter'],
        ($indexMetaData['evaltrue'] ? false : true),
        (isset($indexMetaData['valid']) ? $indexMetaData['valid'] : false)
      );
      break;

      case 'array':
      $arrayParts = explode(',', $activeGlobal[$indexName]);
      $arrayKeys = array();
      $arrayVals = array();

      foreach ($arrayParts AS $arrayEntry) {
        $arrayEntry = explode('=', $arrayEntry);

        if (count($arrayEntry) !== 2) continue; // Must be two parts to every entry.

        $arrayKeys[] = $arrayEntry[0];
        $arrayVals[] = $arrayEntry[1];
      }

      $arrayVals = fim_arrayValidate(
        $arrayVals,
        $indexMetaData['filter'],
        ($indexMetaData['evaltrue'] ? false : true),
        (isset($indexMetaData['valid']) ? $indexMetaData['valid'] : false)
      );
      $newData[$indexName] = array_combine($arrayKeys, $arrayVals);
      break;

      case 'int':
      if ($indexMetaData['evaltrue']) { // Only include the value if it is true.
        if ((int) $activeGlobal[$indexName]) $newData[$indexName] = (int) $activeGlobal[$indexName]; // If true/non-zero... append value as integer-cast.
      }
      else { // Include the value whether true or false.
        $newData[$indexName] = (int) $activeGlobal[$indexName]; // Append value as integer-cast.
      }

      if (isset($indexMetaData['min'])) {
        if ($newData[$indexName] < $indexMetaData['min']) $newData[$indexName] = $indexMetaData['min']; // Minimum Value
      }
      if (isset($indexMetaData['max'])) {
        if ($newData[$indexName] > $indexMetaData['max']) $newData[$indexName] = $indexMetaData['max']; // Maximum Value
      }
      break;

      case 'bool':
      $newData[$indexName] = fim_cast(
        'bool',
        $activeGlobal[$indexName],
        (isset($indexMetaData['default']) ? $indexMetaData['default'] : null)
      );
      break;

      default: // String or otherwise.
      $newData[$indexName] = (string) $activeGlobal[$indexName]; // Append value as string-cast.

      switch ($indexMetaData['filter']) { // TODO optimise
        case 'ascii128': $newData[$indexName] = preg_replace('/[^(\x20-\x7F)]*/', '', $output); break; // Remove characters outside of ASCII128 range.
        case 'alphanum': $newData[$indexName] = preg_replace('/[^a-zA-Z0-9]*/', '', str_replace(array_keys($config['romanisation']), array_values($config['romanisation']), $output)); break; // Remove characters that are non-alphanumeric. Note that we will try to romanise what we can.
      }
      break;
    }
  }

  return $newData;
}


/**
 * Performs a custom cast, implementing custom logic for boolean casts (and the default logic for all others).
 *
 * @param string cast - Type of cast, either 'bool', 'int', 'float', or 'string'.
 * @param string value - Value to cast.
 * @param string default - Whether to lean true or false with bool casts. Only if a value is exactly true or false will thus value not be used.
 *
 * @author Joseph Todd Parsons <josephtparsons@gmail.com>
 */
function fim_cast($cast, $value, $default = null) {
  switch ($cast) {
    case 'bool':
    $trueValues = array('true', 1, true, '1');
    $falseValues = array('false', 0, false, '0');

    if (in_array($value, $trueValues, true)) { $value = true; } // Strictly matches one of the above true values
    elseif (in_array($value, $falseValues, true)) { $value = false; } // Strictly matches one of the above false values
    elseif (!is_null($default)) { $value = (bool) $default; } // There's a default
    else { $value = false; }
    break;

    case 'int': $value = (int) $value; break;
    case 'float': $value = (float) $value; break;
    case 'string': $value = (string) $value; break;

    default: throw new Exception('Unrecognised cast.'); break;
  }

  return $value;
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
function fim_arrayValidate($array, $type = 'int', $preserveAll = false, $allowedValues = false) {
  $arrayValidated = array(); // Create an empty array we will use to store things.
  if (is_array($array)) { // Make sure the array is an array.
    foreach ($array AS $value) { // Run through each value of the array.
      if (is_array($allowedValues)
        && !in_array($value, $allowedValues)) continue;

      switch ($type) { // What type are we validating to?
        case 'int': // Integer type.
        if ($preserveAll) $arrayValidated[] = (int) $value; // If we preserve false entries, simply cast the variable as an interger.
        else { // If we don't preserve false entries...
          $preValue = (int) $value; // Cast the value

          if ($preValue) { // If it is non-zero, add it to the new array.
            $arrayValidated[] = $preValue;
          }
        }
        break;

        case 'bool':
        $preValue = fim_cast('bool', $value, false);

        if ($preserveAll)           $arrayValidated[] = $preValue; // Add to the array regardless of true/false (arguably what is should be here xD)
        elseif ($preValue === true) $arrayValidated[] = $preValue; // Only add to the array if true.
        break;

        default:
        if ($preserveAll)  $arrayValidated[] = $value; // Add to the array regardless of true/false (arguably what is should be default here xD)
        elseif ($preValue) $arrayValidated[] = $value; // Only add to the array if true.
        break;
      }
    }
  }
  else $arrayValidated = array(); // If its not, we will return an empty array.

  return $arrayValidated; // Return the validated array.
}












/********************************************************
************************ START **************************
******************** Error Handling *********************
*********************************************************/


/**
 * Custom Exception Handler
 *
 * @author Joseph Todd Parsons <josephtparsons@gmail.com>
 */

function fim_exceptionHandler($exception) {
  global $api, $apiRequest, $config;

  ob_end_clean(); // Clean the output buffer and end it. This means that when we show the error in a second, there won't be anything else with it.
  header('HTTP/1.1 500 Internal Server Error'); // When an exception is encountered, we throw an error to tell the server that the software effectively is broken.

  if ($config['displayExceptions']) {
    $errorData = array(
      'string' => $exception->getMessage(),
      'file' => $exception->getFile(),
      'line' => $exception->getLine(),
      'trace' => $exception->getTrace(),
      'contactEmail' => $config['email'],
    );
  }
  else {
    $errorData = array(
      'string' => '',
      'file' => '',
      'line' => 0,
      'trace' => '',
      'contactEmail' => $config['email'],
    );
  }

  if ($api || $apiRequest) { // TODO: I don't know why $api doesn't work. $apiRequest does for now, but this will need to be looked into to.
    echo fim_outputApi(array(
      'exception' => $errorData,
    ));
  }
  else {
    echo(nl2br('<fieldset><legend><strong style="color: #ff0000;">Program Exception</strong></legend><strong>Error Text</strong><br />' . $errorData['string'] . '<br /><strong>Error File</strong><br />' . $errorData['file'] . '<br /><strong>Error Line</strong><br />' . $errorData['line'] . '<br /><strong>Error Trace</strong><br />' . $errorData['trace'] . '<br /><br /><strong>What Should I Do Now?</strong><br />' . ($config['email'] ? 'You may wish to <a href="mailto:' . $config['email'] . '">notify the administration</a> of this error.' : 'No contact was specified for this installation, so try to wait it out.')  . '<br /><br /><strong>Are You The Host?</strong><br />Program exceptions are usually a result of either a bug in the program or a corrupted installation. If you have no idea what is going on, please report the problem on <a href="http://code.google.com/p/freeze-messenger/issues/list">FIM\'s bug tracker.</a></fieldset>'));
  }

  if ($config['email'] && $config['emailExceptions']) {
    mail($config['email'], 'FIM3 System Error [' . $_SERVER['SERVER_NAME'] . ']', 'The following error was encountered by the server located at ' . $_SERVER['SERVER_NAME'] . ':<br /><br />' . $errstr);
  }
  
  if ($config['logExceptionsFile'] && $config['logExceptions']) {
    error_log($exception->getFile() . ', ' . $exception->getLine() . ', ' . $exception->getMessage() . ' TRACE: ' . $exception->getTrace());
  }
}


/**
 * Custom Error Handler
 *
 * @author Joseph Todd Parsons <josephtparsons@gmail.com>
 */

function fim_errorHandler($errno, $errstr, $errfile, $errline) {
  global $config, $api, $apiRequest;

  if (!(error_reporting() & $errno)) { // The error is not to be reported.
    return;
  }

  switch ($errno) {
    case E_USER_ERROR:
    ob_end_clean(); // Clean the output buffer and end it. This means when we show the error in a second, there won't be anything else with it.
    header('HTTP/1.1 500 Internal Server Error');

    if ($api || $apiRequest) { // TODO: I don't know why $api doesn't work. $apiRequest does for now, but this will need to be looked into to.
      echo fim_outputApi(array(
        'exception' => array(
          'string' => $errstr,
          'contactEmail' => $config['email'],
        )
      ));
    }
    else {
      die(nl2br('<fieldset><legend><strong style="color: #ff0000;">Unrecoverable Error</strong></legend><strong>Error Text</strong><br />' . $errstr . '<br /><br /><strong>What Should I Do Now?</strong><br />' . ($config['email'] ? 'You may wish to <a href="mailto:' . $config['email'] . '">notify the administration</a> of this error.' : 'No contact was specified for this installation, so try to wait it out.')  . '<br /><br /><strong>Are You The Host?</strong><br />Server errors are often database related. These may result from improper installation or a corrupted database. The documentation may provide clues, however.</fieldset>'));
    }

    if ($config['email'] && $config['emailErrors']) mail($config['email'], 'FIM3 System Error [' . $_SERVER['SERVER_NAME'] . ']', 'The following error was encountered by the server located at ' . $_SERVER['SERVER_NAME'] . ':<br /><br />' . $errstr);
    break;

    default:
    // Do Nothing
    break;
  }

  return true; // Don't execute PHP internal error handler
}



/** Format structured error data for string output. Additional information may be appended, such as the current time.
 *
 * @param string errorString
 * @param array errorData - An array of error data, formatted as <code>array($parameter => $value, $parameter => $value, ...)</code>.
 *
 * For instance, say an HTTP request could not be completed. You would want to call this function to format the relevant information:
 * <code>
 * trigger_error(E_USER_ERROR, fim_formatErrors(array(
 *   'errorContext' => 'http',
 *   'errorCode' => '404',
 *   'errorString' => 'File Not Found',
 * )));
 * </code>
 */
function fim_formatErrors($errorString, $errorData) {
  $returnString = '';
  
  foreach ($errorData AS $param => $value) {
    $returnString .= "  $param: $value\n";
  }
  
  return "$errorString; Additional Information:\n$returnString";
}


/**
 * Flushes The Output Buffer
 */
function fim_flush() {
  flush();
  
  if (ob_get_level()) ob_flush(); // Flush output buffer if enabled.
}
?>