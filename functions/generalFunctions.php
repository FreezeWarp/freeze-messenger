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

function inArray($needle,$haystack) {
  foreach($needle AS $need) {
    if (in_array($need,$haystack)) {
      return true;
    }
  }
  return false;
}

function hasPermission($roomData,$userData,$type = 'post',$trans = false) { // The below permissions are very hierachle.
  global $sqlPrefix, $banned;
  static $isAdmin, $isModerator, $isAllowedUser, $isAllowedGroup, $isOwner, $isRoomDeleted;

  /* Make sure all presented data is correct. */
  if (!$roomData['id']) {
    return false;
  }

  /* Get the User's Kick Status */
  if ($userData['userId']) {
    $kick = sqlArr("SELECT UNIX_TIMESTAMP(k.time) AS kickedOn, UNIX_TIMESTAMP(k.time) + k.length AS expiresOn, k.id FROM {$sqlPrefix}kick AS k WHERE userId = $userData[userId] AND room = $roomData[id] AND UNIX_TIMESTAMP(NOW()) <= (UNIX_TIMESTAMP(time) + length)");
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

  if ((inArray(explode(',',$userData['membergroupids']),explode(',',$roomData['allowedGroups']))
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

  if ($userData['settings'] & 16) {
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

function userFormat($message, $room, $messageTable = true) {
  global $loginMethod, $cachedUserGroups, $parseGroups, $sqlUserGroupTable, $sqlUserGroupTableCols, $permission;

  if ($message['displaygroupid'] && $parseGroups) { // The "parseGroups" toggle can be set in the configuration or will be set manually in validate.php whenever a login method doesn't use this token.
    if (!$cachedUserGroups[$message['displaygroupid']]) {
      switch ($loginMethod) {
        case 'vbulletin':
        $group = sqlArr("SELECT * FROM {$sqlUserGroupTable} WHERE {$sqlUserGroupTableCols[groupid]} = {$message[displaygroupid]}");
        //print_r($group);
        break;
      }

      $cachedUserGroups[$message['displaygroupid']] = $group;
    }

    $openTag = $cachedUserGroups[$message['displaygroupid']]['opentag'];
    $closeTag = $cachedUserGroups[$message['displaygroupid']]['closetag'];
  }

  $class = ($messageTable ? 'userName userNameTable' : 'userName');
  if ($permission['isModerator'] || $permission['isAdmin'] || $permission['isOwner']) $userAppend = '*';

  return "{$openTag}<span style=\"{$colour}\" class=\"{$class}\" data-userId=\"$message[userId]\">$message[userName]{$userAppend}</span>{$closeTag}";
}

function messageStyle($message) {
  global $enableDF, $user;

  if ($enableDF && (($user['settings'] & 512) == false) && !in_array($message['flag'],array('me','topic','kick'))) {
    if ($message['defaultColour'] && $enableDF['colour']) $style .= "color: rgb($message[defaultColour]); ";
    if ($message['defaultFontface'] && $enableDF['font']) $style .= "font-family: $message[defaultFontface]; ";
    if ($message['defaultHighlight'] && $enableDF['highlight']) $style .= "background-color: rgb($message[defaultHighlight]); ";
    if ($message['defaultFormatting'] && $enableDF['general']) {
      $df = $message['defaultFormatting'];

      if ($df & 256) $style .= "font-weight: bold; ";
      if ($df & 512) $style .= "font-style: italic; ";
    }
  }

  return $style;
}

function vrim_urldecode($str) {
  return str_replace(array('%2b','%26','%20'),array('+','&',"\n"),$str);
}

function vrim_decrypt($message,$index = false) {
  global $salts;

  if ($message['salt'] && $message['iv']) {
    $salt = $salts[$message['salt']];

    if ($index) {
      if (is_array($index)) {
        foreach ($index AS $index2) {
          $message[$index2] = rtrim(mcrypt_decrypt(MCRYPT_3DES, $salt, base64_decode($message[$index2]), MCRYPT_MODE_CBC,base64_decode($message['iv'])),"\0");
        }
      }
      else {
        $message[$index] = rtrim(mcrypt_decrypt(MCRYPT_3DES, $salt, base64_decode($message[$index]), MCRYPT_MODE_CBC,base64_decode($message['iv'])),"\0");
      }
    }
    else {
      if ($message['apiText']) $message['apiText'] = rtrim(mcrypt_decrypt(MCRYPT_3DES, $salt, base64_decode($message['apiText']), MCRYPT_MODE_CBC,base64_decode($message['iv'])),"\0");
      if ($message['htmlText']) $message['htmlText'] = rtrim(mcrypt_decrypt(MCRYPT_3DES, $salt, base64_decode($message['htmlText']), MCRYPT_MODE_CBC,base64_decode($message['iv'])),"\0");
      if ($message['rawText']) $message['rawText'] = rtrim(mcrypt_decrypt(MCRYPT_3DES, $salt, base64_decode($message['rawText']), MCRYPT_MODE_CBC,base64_decode($message['iv'])),"\0");
    }
  }

  return $message;
}

function vrim_encrypt($data) {
  global $salts;

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

function vrim_encodeXML($data) {
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

function vbdate($format,$timestamp = false) {
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

function button($text,$url,$postVars = false) {
  return '<form method="post" action="' . $url . '" style="display: inline;">
  <button type="submit">' . $text . '</button>
</form>';
}

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

function parser1($text,$offset,$stop = false,$globalString) {
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

  return $str;
}

function iifl($condition,$true,$false,$eval) {
  global $templates, $phrases, $title, $user, $room, $message, $template, $templateVars; // Lame approach.
  if($eval) {
    eval($eval);
  }// echo '<<' . $condition;

  if (eval('return ' . $condition . ';')) {
    return $true;
  }
  return $false;
}

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

// This is an experimental function. It is largely just an experiment.
function htmlLight($data) {
  $data = preg_replace('/\ {2,}/','',$data);
  $data = preg_replace("/\n/",'',$data);
  return $data;
}

ob_start(htmlLight);
?>