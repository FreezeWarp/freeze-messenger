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
* @author Joseph Todd Parsons
*/
function fim_inArray($needle,$haystack) {
  foreach($needle AS $need) {
    if (in_array($need,$haystack)) {
      return true;
    }
  }
  return false;
}

/**
* Determines if a user has permission to do an action in a room.
*
* @param array $roomData - An array containing the room's data; indexes allowedUsers, allowedGroups, moderators, owner, and options may be used.
* @param array $userData - An array containing the user's data; indexes userId, adminPrivs, and userPrivs may be used.
* @param string $type - Either "know", "view", "post", "moderate", or "admin", this defines the action the user is trying to do.
* @param bool $trans - If true, return will be an information array; otherwise bool.
* @global bool $banned
* @global array $superUsers
* @return mixed - Bool if $trans is false, array if $trans is true.
* @author Joseph Todd Parsons
*/
function fim_hasPermission($roomData,$userData,$type = 'post',$trans = false) {
  global $sqlPrefix, $banned, $superUsers;
  static $isAdmin, $isModerator, $isAllowedUser, $isAllowedGroup, $isOwner, $isRoomDeleted;

  /* Make sure all presented data is correct. */
  if (!$roomData['roomId']) {
    return false;
  }

  /* Get the User's Kick Status */
  if ($userData['userId']) {
    $kick = sqlArr("SELECT UNIX_TIMESTAMP(k.time) AS kickedOn,
  UNIX_TIMESTAMP(k.time) + k.length AS expiresOn,
  k.id
FROM {$sqlPrefix}kick AS k
WHERE userId = $userData[userId] AND
  roomId = $roomData[roomId] AND
  UNIX_TIMESTAMP(NOW()) <= (UNIX_TIMESTAMP(time) + length)");
  }

  if ((in_array($userData['userId'],explode(',',$roomData['allowedUsers']))
    || $roomData['allowedUsers'] == '*')
  && $roomData['allowedUsers']) {
    $isAllowedUser = true;
  }

  if (in_array($userData['userId'],explode(',',$roomData['moderators']))
  && $roomData['moderators']) {
    $isModerator = true; // The user is one of the chat moderators (and it is not deleted).
  }

  if ((fim_inArray(explode(',',$userData['socialGroups']),explode(',',$roomData['allowedGroups']))
    || $roomData['allowedGroups'] == '*')
  && $roomData['allowedGroups']) {
    $isAllowedGroup = true;
  }

  if ($roomData['owner'] == $userData['userId'] && $roomData['owner'] > 0) {
    $isOwner = true;
  }

  if ($roomData['options'] & 4) {
    $isRoomDeleted = false; // The room is deleted.
  }

  if ($roomData['options'] & 16) {
    $isPrivateRoom = true;
  }

  /* Is the user a super user? */
  if (in_array($userData['userId'],$superUsers) || $userData['adminPrivs'] & 1) {
    $isAdmin = true;
  }

  switch ($type) {
    case 'post':
    if ($banned) {
      $roomValid = false;
      $reason = 'banned';
    }
    elseif ($kick['id'] && !$isAdmin) {
      $roomValid = false;
      $reason = 'kicked';
    }
    elseif ($isAdmin && !$isPrivateRoom) {
      $roomValid = true;
    }
    elseif ($isRoomDeleted) {
      $roomValid = false;
      $reason = 'deleted';
    }
    elseif ($isAllowedUser || $isAllowedGroup || $isOwner) {
      $roomValid = true;
    }
    else {
      $roomValid = false;
      $reason = 'general';
    }
    break;

    case 'view':
    if ($isAdmin && !$isPrivateRoom) {
      $roomValid = true;
    }
    elseif ($isRoomDeleted) {
      $roomValid = false;
      $reason = 'deleted';
    }
    elseif ($isAllowedUser || $isAllowedGroup || $isOwner) {
      $roomValid = true;
    }
    else {
      $roomValid = false;
      $reason = 'general';
    }
    break;

    case 'moderate':
    if ($banned) {
      $roomValid = false;
      $reason = 'banned';
    }
    elseif ($kick['id'] && !$isAdmin) {
      $roomValid = false;
      $reason = 'kicked';
    }
    elseif ($isPrivateRoom) {
      $roomValid = false;
      $reason = 'private';
    }
    elseif ($isOwner || $isModerator || $isAdmin) {
      $roomValid = true;
    }
    else {
      $roomValid = false;
      $reason = 'general';
    }
    break;

    case 'admin':
    if ($banned) {
      $roomValid = false;
      $reason = 'banned';
    }
    elseif ($kick['id']) {
      $roomValid = false;
      $reason = 'kicked';
    }
    elseif ($isPrivateRoom) {
      $roomValid = false;
      $reason = 'private';
    }
    elseif ($isAdmin) {
      $roomValid = true;
    }
    else {
      $roomValid = false;
      $reason = 'general';
    }
    break;

    case 'know':
    if ($banned) {
      $roomValid = false;
      $reason = 'banned';
    }
    elseif ($kick['id']) {
      $roomValid = false;
      $reason = 'kicked';
    }
    elseif ($isAdmin) {
      $roomValid = true;
    }
    elseif ($isRoomDeleted) {
      $roomValid = false;
      $reason = 'deleted';
    }
    elseif ($isAllowedUser || $isAllowedGroup || $isOwner) {
      $roomValid = true;
    }
    else {
      $roomValid = false;
      $reason = 'general';
    }
    break;
  }

  if ($trans) {
    return array(
      $roomValid,
      $reason,
      $kick['expiresOn']
    );
  }

  else {
    return $roomValid;
  }
}


/**
* Decodes a specifically-formatted URL string, converting entities for "+", "&", '%', and new line to their respective string value.
*
* @param string $str - The string to be decoded.
* @return mixed - Bool if $trans is false, array if $trans is true.
* @author Joseph Todd Parsons
*/
function fim_urldecode($str) {
  return str_replace(array('%2b','%26','%20','%25'),array('+','&',"\n", '%'),$str);
}


/**
* Determines if a user has permission to do an action in a room.
*
* @param array $message - An array containing the message data; must include an "iv" index.
* @param mixed $index - An array or string corrosponding to which indexes in the $message should be decrypted.
* @global array $salts
* @return array
* @author Joseph Todd Parsons
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
* @global array $salts
* @return array - list($data, $iv, $saltNum)
* @author Joseph Todd Parsons
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
* Encodes a string as specifically-formatted XML data, converting "&", "'", '"', "<", and ">" to their equivilent values.
*
* @param string $data - The data to be encoded.
* @return string
* @author Joseph Todd Parsons
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

  $hourdiff = (date('Z', $timestamp) / 3600 - $user['timezoneoffset']) * 3600;

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
* @author Joseph Todd Parsons
*/

function hook($name) {
  global $hooks;
  $hook = $hooks[$name];

  if ($hook) {
    return $hook;
  }
  else {
    return 'return false;';
  }
}


/**
* Retrieves a template from the database.
*
* @param string $name
* @return string
* @author Joseph Todd Parsons
*/

function template($name) {
  global $templates, $phrases, $title, $user, $room, $message, $template, $templateVars; // Lame approach.

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


/**
* Parser for template() function.
*
* @param string $text
* @param int $offset
* @param bool $stop
* @param string $globalString
* @return string
* @author Joseph Todd Parsons
*/

function parser1($text,$offset,$stop = false,$globalString = '') {
  $i = $offset;
//  static $cValue, $cValueProc, $iValue, $iValueProc;

  while ($i < strlen($text)) {
    $j = $text[$i];

    if ($iValueProc) {
      $str .= iifl("$cond","$iv[1]","$iv[2]","global $globalString;");
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
        $cv[$cValueI] .= $j;
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
        $iv[$iValueI] .= $j;
      }
    }

    else {
      if (substr($text,$i,6) == '{{if="') {
        $i += 6;
        while ($text[$i] != '"') {
          $cond .= $text[$i];
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
    $str .= iifl("$cond","$iv[1]","$iv[2]","global $globalString;");
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
* @author Joseph Todd Parsons
*/

function iifl($condition,$true,$false,$eval) {
  global $templates, $phrases, $title, $user, $room, $message, $template, $templateVars; // Lame approach.
  if($eval) {
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
* @author Joseph Todd Parsons
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
* @author Joseph Todd Parsons
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
* XML Parser
*
* @param array $array
* @param int $level
* @return string
* @author Joseph Todd Parsons
*/

function fim_outputXml($array,$level = 0) {
  header('Content-type: text/xml');

  $indent = '';

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
* HTML Compact Function So Google's PageSpeed Likes FIM (also great if GZIP isn't available)
*
* @param string $data
* @return string
* @author Joseph Todd Parsons
*/

function fim_htmlCompact($data) {
  $data = preg_replace('/\ {2,}/','',$data);
  $data = preg_replace("/(\n|\n\r|\t|\r)/",'',$data);
  $data = preg_replace("/\<\!-- (.+?) --\>/",'',$data);
  $data = preg_replace("/\>(( )+?)\</",'><',$data);
  return $data;
}
?>