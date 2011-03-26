<?php
function inArray($needle,$haystack) {
  foreach($needle AS $need) {
    if (in_array($need,$haystack)) {
      return true;
    }
  }
  return false;
}

function hasPermission($roomData,$userData,$type = 'post') { // The below permissions are very hierachle.
  global $sqlPrefix, $banned;

  if (!$roomData['id']) {
    return false;
  }

  if ($userData['userid']) {
    $kick = sqlArr("SELECT * FROM {$sqlPrefix}kick WHERE userid = $userData[userid] AND room = $roomData[id] AND UNIX_TIMESTAMP(NOW()) <= (UNIX_TIMESTAMP(time) + length)");
  }

  switch ($type) {
    case 'post':
    if ($banned) $roomValid = false; // The user is banned.
    elseif (($userData['settings'] & 16) && (($roomData['options'] & 16) == false)) $roomValid = true; // The user is an admin.
    elseif ($roomData['options'] & 4) $roomValid = false; // The room is deleted.
    elseif ($roomData['owner'] == $userData['userid'] && $roomData['owner'] > 0)  $roomValid = true; // The users owns the room (and it is not deleted).
    elseif (in_array($userData['userid'],explode(',',$roomData['moderators']))) $roomValid = true; // The user is one of the chat moderators (and it is not deleted).
    elseif ($kick['id']) $roomValid = false;
    elseif (in_array($userData['userid'],explode(',',$roomData['allowedUsers'])) || $roomData['allowedUsers'] == '*') $roomValid = true; // The user is in the allowed users column (and it is not deleted).
    elseif ((inArray(explode(',',$userData['membergroupids']),explode(',',$roomData['allowedGroups'])) || $roomData['allowedGroups'] == '*') && ($roomData['allowedGroups'] != '')) $roomValid = true; // The user is a part of a group that is in the allowed groups (and it is not deleted).
    else $roomValid = false; // The user is not allowed either via being an owner, moderator (for the chat itself or the forums),
    break;

    case 'view':
    if (($userData['settings'] & 16) && (($roomData['options'] & 16) == false)) $roomValid = true; // The user is an admin.
    elseif ($roomData['owner'] == $userData['userid'] && $roomData['owner'] > 0)  $roomValid = true; // The users owns the room.
    elseif ($roomData['options'] & 4) $roomValid = false; // The room is deleted.
    elseif ((in_array($userData['userid'],explode(',',$roomData['moderators']))) && $roomData['moderators']) $roomValid = true; // The user is one of the chat moderators (and it is not deleted).
    //elseif ($kick['id']) $roomValid = false;
    elseif ((in_array($userData['userid'],explode(',',$roomData['allowedUsers'])) || $roomData['allowedUsers'] == '*') && $roomData['allowedUsers']) $roomValid = true; // The user is in the allowed users column (and it is not deleted).
    elseif ((inArray(explode(',',$userData['membergroupids']),explode(',',$roomData['allowedGroups'])) || $roomData['allowedGroups'] == '*') && ($roomData['allowedGroups'] != '')) $roomValid = true; // The user is a part of a group that is in the allowed groups (and it is not deleted).
    else $roomValid = false; // The user is not allowed either via being an owner, moderator (for the chat itself or the forums),
    break;

    case 'moderate':
    if ($banned) $roomValid = false; // The user is banned.
    elseif (($userData['settings'] & 16) && (($roomData['options'] & 16) == false)) $roomValid = true; // The user is an admin.
    elseif ($roomData['owner'] == $userData['userid'] && $roomData['owner'] > 0)  $roomValid = true; // The users owns the room (and it is not deleted).
    elseif (in_array($userData['userid'],explode(',',$roomData['moderators']))) $roomValid = true; // The user is one of the chat moderators (and it is not deleted).
    else $roomValid = false; // The user is not allowed either via being an owner, moderator (for the chat itself or the forums),
    break;

    case 'know':
    if ($userData['settings'] & 16) $roomValid = true; // The user is an admin.
    elseif ($roomData['owner'] == $userData['userid'] && $roomData['owner'] > 0)  $roomValid = true; // The users owns the room.
    elseif ((in_array($userData['userid'],explode(',',$roomData['moderators']))) && $roomData['moderators']) $roomValid = true; // The user is one of the chat moderators.
    elseif ((in_array($userData['userid'],explode(',',$roomData['allowedUsers'])) || $roomData['allowedUsers'] == '*') && $roomData['allowedUsers']) $roomValid = true; // The user is in the allowed users column (and it is not deleted).
    elseif ((inArray(explode(',',$userData['membergroupids']),explode(',',$roomData['allowedGroups'])) || $roomData['allowedGroups'] == '*') && ($roomData['allowedGroups'] != '')) $roomValid = true; // The user is a part of a group that is in the allowed groups (and it is not deleted).
    else $roomValid = false; // The user is not allowed either via being an owner, moderator (for the chat itself or the forums),
    break;
  }

  return $roomValid;
}

function displayGroupToColour($id) {
  switch ($id) {
    case 5: return '0,170,0'; break;
    case 6: return '170,0,0'; break;
    case 7: return '0,0,255'; break;
    case 23: return '255,140,0'; break;
    case 24: return '255,140,0'; break;
    case 25: return '0,127,127'; break;
    case 30: return '255,0,0'; break;
    case 36: return '170,0,170'; break;
    default: return '0,0,0'; break;
  }
}

function userFormat($message, $room, $messageTable = true) {
  $colour = 'color: rgb(' . displayGroupToColour($message['displaygroupid']) . '); ';
  $class = ($messageTable ? 'username usernameTable' : 'username');
  if (in_array($message['userid'],explode(',',$room['moderators'])) || $message['usersettings'] & 16) $userAppend = '*';

  return "<a href=\"http://www.victoryroad.net/member.php?u=$message[userid]\" class=\"{$class}\" data-userid=\"$message[userid]\">
  <span style=\"{$colour}\">$message[username]{$userAppend}</span>
</a>";
}

function messageStyle($message) {
  global $enableDF;

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
  return str_replace(array('%2b','%26'),array('+','&'),$str);
}

function vrim_decrypt($message) {
  if ($message['salt'] && $message['iv']) {
    switch ($message['salt']) {
      case 101: $salt = 'Fr33d0m*'; break;
      default: return $message; break;
    }

    $message['vbText'] = rtrim(mcrypt_decrypt(MCRYPT_3DES, $salt, base64_decode($message['vbText']), MCRYPT_MODE_CBC,base64_decode($message['iv'])),"\0");
    $message['htmlText'] = rtrim(mcrypt_decrypt(MCRYPT_3DES, $salt, base64_decode($message['htmlText']), MCRYPT_MODE_CBC,base64_decode($message['iv'])),"\0");
    $message['rawText'] = rtrim(mcrypt_decrypt(MCRYPT_3DES, $salt, base64_decode($message['rawText']), MCRYPT_MODE_CBC,base64_decode($message['iv'])),"\0");
  }

  return $message;
}

function html2rgb($color) {
  if ($color[0] == '#') $color = substr($color, 1);

  if (strlen($color) == 6) list($r, $g, $b) = array($color[0] . $color[1], $color[2] . $color[3], $color[4] . $color[5]);
  elseif (strlen($color) == 3) list($r, $g, $b) = array($color[0].$color[0], $color[1].$color[1], $color[2].$color[2]);
  else return false;

  $r = hexdec($r);
  $g = hexdec($g);
  $b = hexdec($b);

  return array($r, $g, $b);
}

function rgb2html($r, $g=-1, $b=-1) {
  if (is_array($r) && sizeof($r) == 3) list($r, $g, $b) = $r;

  $r = intval($r);
  $g = intval($g);
  $b = intval($b);

  $r = dechex($r < 0 ? 0 : ($r > 255 ? 255 : $r));
  $g = dechex($g < 0 ? 0 : ($g > 255 ? 255 : $g));
  $b = dechex($b < 0 ? 0 : ($b > 255 ? 255 : $b));

  $color = (strlen($r) < 2?'0':'').$r;
  $color .= (strlen($g) < 2?'0':'').$g;
  $color .= (strlen($b) < 2?'0':'').$b;
  return '#' . $color;
}
?>