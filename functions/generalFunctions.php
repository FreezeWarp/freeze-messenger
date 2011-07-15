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
* @author Joseph Todd Parsons <josephtparsons@gmail.com>
*/

function fim_inArray($needle,$haystack) {
  if (!$haystack) {
    return false;
  }
  if (!$needle) {
    return false;
  }

  foreach($needle AS $need) {
    if (!$need) {
      continue;
    }

    if (in_array($need,$haystack)) {
      return true;
    }
  }
  return false;
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

function fim_arrayValidate($array,$type = 'int',$preserveAll = false) {
  $arrayValidated = array();

  foreach ($array AS $value) {
    switch ($type) {
      case 'int':
      if ($preserveAll) {
        $arrayValidated[] = (int) $value;
      }
      else {
        $preValue = (int) $value;

        if ($preValue) {
          $arrayValidated[] = $preValue;
        }
      }
      break;
    }
  }

  return $arrayValidated;
}


/**
* Determines if a user has permission to do an action in a room.
*
* @param array $roomData - An array containing the room's data; indexes allowedUsers, allowedGroups, moderators, owner, and options may be used.
* @param array $userData - An array containing the user's data; indexes userId, adminPrivs, and userPrivs may be used.
* @param string $type - Either "know", "view", "post", "moderate", or "admin", this defines the action the user is trying to do.
* @param bool $trans - If true, return will be an information array; otherwise bool.
* @global bool $banned - Whether or not the user is banned outright.
* @global array $superUsers - The list of superUsers.
* @global bool $valid - Whether or not the user has a valid login (required for posting, etc.)
* @global string $sqlPrefix
* @return mixed - Bool if $trans is false, array if $trans is true.
* @author Joseph Todd Parsons <josephtparsons@gmail.com>
*/

function fim_hasPermission($roomData,$userData,$type = 'post',$quick = false) {
  global $sqlPrefix, $banned, $superUsers, $valid, $database, $config, $cachedKicks;

  $isAdmin = false;
  $isModerator = false;
  $isAllowedUser = false;
  $isAllowedGroup = false;
  $isOwner = false;
  $isRoomDeleted = false;
  $kick = false;

  $reason = '';


  /* Make sure all presented data is correct. */
  if (!$roomData['roomId']) {
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


  if (isset($roomData['allowedGroups'])) {
    $allowedGroups = explode(',',$roomData['allowedGroups']);
    foreach ($allowedGroups AS $groupId) {
      if (!$groupId) {
        continue;
      }

      if (strpos($groupId, 'a') === 0) {
        $roomData['allowedAdminGroups'][] = (int) substr($groupId,1);
      }
      else {
        $roomData['allowedSocialGroups'][] = (int) $groupId;
      }
    }
  }


  /* Get the User's Kick Status */
  if (isset($userData['userId'])) {
    if ($userData['userId'] > 0) {
      if (count($cachedKicks) > 0) {
        if (isset($cachedKicks[][])) {
          $kick = true;
        }
        else {
          $kick = false;
        }
      }
      else {
        $kick = $database->select(
          array(
            "{$sqlPrefix}kick" => array(
              'time' => array(
                'context' => 'time',
                'name' => 'kickedOn',
              ),
              'userId' => 'userId',
              'roomid' => 'roomId',
              'length' => 'length',
            ),
          ),
          array(
            'both' => array(
              array(
                'type' => 'gt',
                'left' => array(
                  'type' => 'equation',
                  'value' => '$kickedOn + $length',
                ),
                'right' => array(
                  'type' => 'int',
                  'value' => (int) time(),
                ),
              ),
              array(
                'type' => 'e',
                'left' => array(
                  'type' => 'column',
                  'value' => 'userId',
                ),
                'right' => array(
                  'type' => 'int',
                  'value' => (int) $userData['userId'],
                ),
              ),
              array(
                'type' => 'e',
                'left' => array(
                  'type' => 'column',
                  'value' => 'roomId',
                ),
                'right' => array(
                  'type' => 'int',
                  'value' => (int) $roomData['roomId'],
                ),
              ),
            ),
          )
        );
        $kick = ($kick->getAsArray(false) ? true : false);
      }
    }
  }


  /* Is the User an Allowed User? */
  if (isset($userData['userId']) && isset($roomData['allowedUsers'])) {
    if ((in_array($userData['userId'],explode(',',$roomData['allowedUsers']))
      || $roomData['allowedUsers'] == '*')
    && $roomData['allowedUsers']) {
      $isAllowedUser = true;
    }
  }


  /* Is the User a Moderator of the Room? */
  if (isset($userData['userId']) && isset($roomData['moderators'])) {
    if (in_array($userData['userId'],explode(',',$roomData['moderators']))) {
      $isModerator = true; // The user is one of the chat moderators (and it is not deleted).
    }
  }


  /* Is the User Part of an Allowed Group? */
  if (isset($roomData['allowedSocialGroups']) && isset($roomData['allowedAdminGroups'])) {
    if ((fim_inArray(explode(',',$userData['socialGroups']),$roomData['allowedSocialGroups'])
      || (fim_inArray(explode(',',$userData['allGroups']),$roomData['allowedAdminGroups']))
      || $roomData['allowedGroups'] == '*')) {
      $isAllowedGroup = true;
    }
  }


  /* Is the User the Room's Owner/Creator */
  if (isset($roomData['owner']) && isset($roomData['owner'])) {
    if ($roomData['owner'] == $userData['userId']
      && $roomData['owner'] > 0) {
      $isOwner = true;
    }
  }


  /* Is the Room a Private Room or Deleted? */
  if (isset($roomData['options'])) {
    if ($roomData['options'] & 4) {
      $isRoomDeleted = true; // The room is deleted.
    }

    if ($roomData['options'] & 16) {
      $isPrivateRoom = true;
    }
  }
  else {
    throw new Exception('Room data invalid (options index missing)');
  }


  /* Is the user a super user? */
  if (isset($userData['userId']) && isset($userData['adminPrivs']) && isset($superUsers)) {
    if (is_array($superUsers)) {
      if (in_array($userData['userId'],$superUsers) || $userData['adminPrivs'] & 1) {
        $isAdmin = true;
      }
    }
  }


  if ($type == 'post' || $type == 'all') {
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

  if ($type == 'view' || $type == 'all') {
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

  if ($type == 'moderate' || $type == 'all') {
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

  if ($type == 'admin' || $type == 'all') {
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

  if ($type == 'know' || $type == 'all') {
    if ($banned) {
      $roomValid['know'] = false;
      $reason = 'banned';
    }
    elseif ($kick) {
      $roomValid['know'] = false;
      $reason = 'kicked';
    }
    elseif ($isAdmin) {
      $roomValid['know'] = true;
    }
    elseif ($isRoomDeleted) {
      $roomValid['know'] = false;
      $reason = 'deleted';
    }
    elseif ($isAllowedUser || $isAllowedGroup || $isOwner) {
      $roomValid['know'] = true;
    }
    else {
      $roomValid['know'] = false;
      $reason = 'general';
    }
  }


  if ($quick) {
    return ($type == 'all' ? $roomValid : $roomValid[$type]);
  }
  else {
    return array(
      ($type == 'all' ? $roomValid : $roomValid[$type]),
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
  return str_ireplace(array('%2b','%26','%20','%25','%0a'),array('+','&',' ', '%',"\n"),$str);
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

function fim_decrypt($message,$index = array('apiText','htmlText','rawText')) {
  global $salts;

  if ($message['salt'] && $message['iv']) {
    $salt = $salts[$message['salt']];

    if ($index) {
      if (is_array($index)) {
        foreach ($index AS $index2) {
          if ($message[$index2]) {
            $message[$index2] = rtrim(mcrypt_decrypt(MCRYPT_3DES, $salt, base64_decode($message[$index2]), MCRYPT_MODE_CBC,base64_decode($message['iv'])),"\0");
          }
        }
      }
      else {
        $message[$index] = rtrim(mcrypt_decrypt(MCRYPT_3DES, $salt, base64_decode($message[$index]), MCRYPT_MODE_CBC,base64_decode($message['iv'])),"\0");
      }
    }
  }

  return $message;
}


/**
* Encrypts a string or all values in an array.
*
* @param mixed $data - The data to encrypt.
* @global array $salts - Key-value pairs used for encryption.
* @return array - list($data, $iv, $saltNum)
* @author Joseph Todd Parsons <josephtparsons@gmail.com>
*/

function fim_encrypt($data) {
  global $salts;

  if (!function_exists('mcrypt_encrypt')) {
    return $data;
  }

  $salt = end($salts);
  $saltNum = key($salts);

  $iv_size = mcrypt_get_iv_size(MCRYPT_3DES,MCRYPT_MODE_CBC);
  $iv = base64_encode(mcrypt_create_iv($iv_size, MCRYPT_RAND));

  if (is_array($data)) {
    foreach ($data AS &$data2) {
      $data2 = base64_encode(rtrim(mcrypt_encrypt(MCRYPT_3DES, $salt, $data2, MCRYPT_MODE_CBC, base64_decode($iv)),"\0"));
    }
  }
  else {
    $data = base64_encode(rtrim(mcrypt_encrypt(MCRYPT_3DES, $salt, $data, MCRYPT_MODE_CBC, base64_decode($iv)),"\0"));
  }

  return array($data,$iv,$saltNum);
}

/**
* Generates a SHA256 using whatever methods are available.
*
* @param mixed
*/
function fim_sha256($data) {
  if (function_exists('hash')) {
    return hash('sha256',$data);
  }
  elseif (function_exists('mhash')) {
    return mhash(MHASH_SHA256,$data);
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
  $ref = array(
    '&' => '&amp;', // By placing this first, we avoid accidents!
    '\'' => '&apos;',
    '<' => '&lt;',
    '>' => '&gt;',
    '"' => '&quot;',
  );

  foreach ($ref AS $search => $replace) {
    $data = str_replace($search,$replace,$data);
  }

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
  if ($color[0] == '#') {
    $color = substr($color, 1);
  }

  if (strlen($color) == 6) {
    list($r, $g, $b) = array($color[0] . $color[1], $color[2] . $color[3], $color[4] . $color[5]);
  }
  elseif (strlen($color) == 3) {
    list($r, $g, $b) = array($color[0].$color[0], $color[1].$color[1], $color[2].$color[2]);
  }
  else {
    return false;
  }

  $r = hexdec($r);
  $g = hexdec($g);
  $b = hexdec($b);

  return array($r, $g, $b);
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
  if (is_array($r) && sizeof($r) == 3) list($r, $g, $b) = $r;

  $r = intval($r);
  $g = intval($g);
  $b = intval($b);

  $r = dechex($r < 0 ? 0 : ($r > 255 ? 255 : $r));
  $g = dechex($g < 0 ? 0 : ($g > 255 ? 255 : $g));
  $b = dechex($b < 0 ? 0 : ($b > 255 ? 255 : $b));

  $color = (strlen($r) < 2 ? '0' : '') . $r;
  $color .= (strlen($g) < 2 ? '0' : '') . $g;
  $color .= (strlen($b) < 2 ? '0' : '') . $b;

  return '#' . $color;
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

function fim_date($format,$timestamp = false) {
  global $user;

  $timestamp = ($timestamp ?: time());

  $hourdiff = ((date('Z', $timestamp) / 3600) - (isset($user['timeZone']) ? $user['timeZone'] : 0)) * 3600;

  $timestamp_adjusted = $timestamp - $hourdiff;

  if ($format == false) { // Used for most messages
    $midnight = strtotime("yesterday") - $hourdiff;
    if ($timestamp_adjusted > $midnight) $format = 'g:i:sa';
    else $format = 'm/d/y g:i:sa';
  }

  $returndate = date($format, $timestamp_adjusted);

  return $returndate;
}


/**
* Retrieves a hook from the database.
*
* @param string $name
* @return evalcode (string)
* @author Joseph Todd Parsons <josephtparsons@gmail.com>
*/

function hook($name) {
  global $hooks;
  $hook = $hooks[$name];

  if ($hook) {
    return $hook;
  }
  else {
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

  if (isset($templateVars[$name])) {
    if($templateVars[$name]) {
      $vars = explode(',',$templateVars[$name]);
      foreach ($vars AS $var) {
        $globalVars[] = '$' . $var;
      }
      $globalString = implode(',',$globalVars);

      eval("global $globalString;");
    }


    $template2 = $templates[$name];

    $template2 = parser1($template2,0,false,$globalString);


    $template2 = preg_replace('/(.+)/e','stripslashes("\\1")',$template2);

    return $template2;
  }

  return '';
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

function parser1($text,$offset,$stop = false,$globalString = '') {
  $i = $offset;

  $cValue = false;
  $cValueProc = false;
  $iValue = false;
  $iValueProc = false;
  $str = '';

  while ($i < strlen($text)) {
    $j = $text[$i];

    if ($iValueProc) {
      $str .= iifl($cond,
        (isset($iv[1]) ? $iv[1] : ''),
        (isset($iv[2]) ? $iv[2] : ''),
        ($globalString ? "global $globalString;" : '')
      );
      if ($stop) return array($str,$i);

      $iv = array(1 => '', 2 => '');
      $cond = '';
      $iValueI = 1;
      $iValueProc = false;

      continue;
    }

    elseif ($cValueProc) {
      $str .= container("$cv[1]","$cv[2]");
      if ($stop) return array($str,$i);

      $cv = array(1 => '', 2 => '');
      $cValueProc = false;
      $cond = '';

      continue;
    }

    elseif ($cValue) {
      if (substr($text,$i,2) == '}{' && $cValueI == 1) {
        $cValueI = 2;

        $i += 2;
        continue;
      }

      elseif (substr($text,$i,2) == '}}') {
        $cValue = false;
        $cValueProc = true;
        $i += 2;

        continue;
      }

      elseif (substr($text,$i,13) == '{{container}{') {
        list($nstr,$offset) = parser1($text,$i,true,$globalString);
        $i = $offset;
        $cv[$cValueI] .= $nstr;

        continue;
      }

      elseif (substr($text,$i,6) == '{{if="') {// die('<<<' . $text . '>>>');
        list($nstr,$offset) = parser1($text,$i,true,$globalString);
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
      if ((substr($text,$i,2) == '}{') && ($iValueI == 1)) {
        $iValueI = 2;

        $i += 2;
        continue;
      }

      elseif (substr($text,$i,2) == '}}') {
        $iValue = false;
        $iValueProc = true;
        $i += 2;

        continue;
      }

      elseif (substr($text,$i,13) == '{{container}{') {
        list($nstr,$offset) = parser1($text,$i,true,$globalString);
        $i = $offset;
        $iv[$iValueI] .= $nstr;

        continue;
      }

      elseif (substr($text,$i,6) == '{{if="') {
        list($nstr,$offset) = parser1($text,$i,true,$globalString);
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
      if (substr($text,$i,6) == '{{if="') {
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

      elseif (substr($text,$i,13) == '{{container}{') {
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
    $str .= iifl("$cond","$iv[1]","$iv[2]",($globalString ? "global $globalString;" : ''));
    if ($stop) return array($str,$i);

    $iv = array(1 => '', 2 => '');
    $cond = '';
    $iValueI = 1;
    $iValueProc = false;
  }

  elseif ($cValueProc) {
    $str .= container("$cv[1]","$cv[2]");
    if ($stop) return array($str,$i);

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

function iifl($condition,$true = '',$false = '',$eval = '') {
  global $templates, $phrases, $title, $user, $room, $message, $template, $templateVars; // Lame approach.

  if (strlen($eval) > 0) {
    eval($eval);
  }

  if (eval('return ' . $condition . ';')) {
    return $true;
  }

  return $false;
}


/**
* General Error Handler
*
* @param int $errno
* @param string $errstr
* @param string $errfile
* @param int $errline
* @return true
* @author Joseph Todd Parsons <josephtparsons@gmail.com>
*/

/* Note: errors should be hidden. It's sorta best practice, especially with the API. */
function errorHandler($errno, $errstr, $errfile, $errline) {
  global $errorFile, $installLoc;
  $errorFile = ($errorFile ? $errorFile : $installLoc . 'error_log.txt');

  switch ($errno) {
    case E_USER_ERROR:
    error_log("User Error in $_SERVER[PHP_SELF]; File '$errfile'; Line '$errline': $errstr\n",3,$errorFile);
    die("An error has occured: $errstr. \n\nThe application has terminated.");
    break;

    case E_USER_WARNING:
    error_log("User Warning in $_SERVER[PHP_SELF]; File '$errfile'; Line '$errline': $errstr\n",3,$errorFile);
    break;

    case E_ERROR:
    error_log("System Error in $_SERVER[PHP_SELF]; File '$errfile'; Line '$errline': $errstr\n",3,$errorFile);
    die("An error has occured: $errstr. \n\nThe application has terminated.");
    break;

    case E_WARNING:
    error_log("System Warning in $_SERVER[PHP_SELF]; File '$errfile'; Line '$errline': $errstr\n",3,$errorFile);
    break;
  }

  // Don't execute the internal PHP error handler.
  return true;
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

function container($title,$content,$class = 'page') {

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
  if (isset($_REQUEST['format'])) {
    switch ($_REQUEST['format']) {
      case 'json':
      return fim_outputJson($data);
      break;

      case 'xml':
      default:
      return fim_outputXml($data);
      break;
    }
  }
  else {
    return fim_outputXml($data);
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

function fim_outputXml($array,$level = 0) {
  header('Content-type: application/xml');

  $indent = '';
  $data = '';

  for($i = 0;$i<=$level;$i++) {
    $indent .= '  ';
  }

  foreach ($array AS $key => $value) {
    $key = explode(' ',$key);
    $key = $key[0];

    $data .= "$indent<$key>\n";

    if (is_array($value)) {
      $data .= fim_outputXml($value,$level + 1);
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
* JSON Parser
*
* @param array $array
* @param int $level
* @return string
* @author Joseph Todd Parsons <josephtparsons@gmail.com>
*/

function fim_outputJson($array, $level = 0) {
  header('Content-type: application/json');

  $indent = '';

  for($i = 0;$i<=$level;$i++) {
    $indent .= '  ';
  }

  foreach ($array AS $key => $value) {
    $key = explode(' ',$key);
    $key = $key[0];

    $data .= "$indent\"$key\":";

    if (is_array($value)) {
      $data .= " {
" . fim_outputJson($value,$level + 1) . "
$indent},

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

      $data .= " $value,\n";
    }
  }

  if ($level == 0) {
    return "{
$data
}";
  }
  else {
    return $data;
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
  $indent = '';

  for($i = 0;$i<=$level;$i++) {
    $indent .= '  ';
  }

  foreach ($array AS $key => $value) {
    $key = explode(' ',$key);
    $key = $key[0];

    $data .= "$indent<li>$key</li>\n";

    if (is_array($value)) {
      $data .= $indent . '  <ul>
' . fim_outputKeys($value,$level + 1) . $indent . '</ul>
';
    }
  }

  return $data;
}


/**
* HTML Compact Function So Google's PageSpeed Likes FIM (also great if GZIP isn't available)
*
* @param string $data
* @return string
* @author Joseph Todd Parsons <josephtparsons@gmail.com>
*/

function fim_htmlCompact($data) {
  $data = preg_replace('/\ {2,}/','',$data);
  $data = preg_replace("/(\n|\n\r|\t|\r)/",'',$data);
  $data = preg_replace("/\<\!-- (.+?) --\>/",'',$data);
  $data = preg_replace("/\>(( )+?)\</",'><',$data);
  return $data;
}


/**
* MySQL modLog container
*
* @param string $action
* @param string $data
* @return bool
* @author Joseph Todd Parsons <josephtparsons@gmail.com>
*/

function modLog($action,$data) {
  global $sqlPrefix, $user, $database;

  if ($database->insert(array(
    'userId' => (int) $user['userId'],
    'ip' => $_SERVER['REMOTE_ADDR'],
    'action' => $action,
    'data' => $data,
  ),"{$sqlPrefix}modlog")) {
    return true;
  }
  else {
    return false;
  }
}


/**
* Pretty Size
*
* @param float $size
* @return string
* @author Joseph Todd Parsons <josephtparsons@gmail.com>
*/

function formatSize($size) {

  $fileSuffixes = array(
    'B',
    'KiB',
    'MiB',
    'GiB',
    'PiB',
    'EiB',
    'ZiB',
    'YiB',
  );

  $suffix = 0;

  // Increase the Byte Prefix, Decrease the Number (1024B = 1KiB)
  while ($size > 1024) {
    $suffix++;
    $size /= 1024;
  }

  return $size . $fileSuffixes[$suffix];

}

/**
* Strict Sanitization of GET/POST/COOKIE Globals
*
* @param array data
* @return array
* @author Joseph Todd Parsons <josephtparsons@gmail.com>
*/
function fim_sanitizeGPC($data) {
  /* Get the Content Type Encoding
   * See http://www.xml.com/pub/a/2004/08/11/rest.html for REST-related information on this; main points: should be possible via a header */
  if (isset($_SERVER['CONTENT_TYPE'])) {
    $contentType = explode(';',$_SERVER['CONTENT_TYPE']); // Divide the subsections of the content_type
    $contentType = $contentType[0]; // Get the first of these subsections; RFC 3875 [4.1.3] explains this well, and defines the format as: type "/" subtype *( ";" parameter )
  }
  elseif (isset($_REQUEST['fim3_dataEncoding'])) {
    $contentType = fim_urldecode($_REQUEST['fim3_dataEncoding']); // Gets rid of encoded slashes, if needed.
  }
  else {
    $contentType = false;
  }


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


    /* Unencode the Global Based on the Above Content Type Encoding
    * see http://pseudo-flaw.net/content/web-browsers/form-data-encoding-roundup/ for browser-related issues, etc. with this
    * see http://pseudo-flaw.net/form-data-encoding-roundup/submit.cgi for sample data
    * see http://www.w3.org/TR/html4/interact/forms.html#h-17.13.4 for the HTML specification
    *
    * Note: PHP does NOT support text/plain. More critically, NO ONE should use this mimetype for submitted data. To avoid issues, we will throw an exception if it is the case.
    * Note: The custom method, and the default, is a simplified version of URLEncode that only removes incompatible data ("+", "?", "&", and "%").
    * */

    switch ($contentType) {
      case 'multipart/form-data': // Nothing encoded
      break;

      case 'text/plain': // Spaces converted to "+"
      throw new Exception('Invalid/blacklisted mimetype specified in request: text/plain'); // Throw an exception
      break;

      case 'application/x-www-form-urlencoded': // Everything Encoded
      default:
      foreach ($activeGlobal AS &$value) {
        $value = urldecode($value);
      }
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
              if (in_array($activeGlobal[$indexName],$indexMetaData['valid'])) { // And if the value specified is in the list of valid values...
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
        }

        switch($indexMetaData['context']['cast']) {
          case 'csv':
          $newData[$indexName] = fim_arrayValidate(explode(',',$activeGlobal[$indexName]),$indexMetaData['context']['filter'],($indexMetaData['context']['evaltrue'] ? false : true)); // If a cast is set for a CSV list, explode with a comma seperator, make sure all values corrosponding to the filter (int, bool, or string - the latter pretty much changes nothing), and if evaltrue is true, then the preserveAll flag would be false, and vice-versa.
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

          if (in_array($activeGlobal[$indexName],$trueValues,true)) {
            $newData[$indexName] = true;
          }
          elseif (in_array($activeGlobal[$indexName],$falseValues,true)) {
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

function iif($condition,$true,$false) {
  if (eval('return ' . stripslashes($condition) . ';')) {
    return $true;
  }
  return $false;
}
?>