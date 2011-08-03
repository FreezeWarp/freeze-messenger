<?php
/**
* Database auth plug-in for phpBB3
*
* Authentication plug-ins is largely down to Sergey Kanareykin, our thanks to him.
*
* This is for authentication via the integrated user table
*
* @package login
* @version $Id$
* @copyright (c) 2005 phpBB Group
* @license http://opensource.org/licenses/gpl-license.php GNU Public License
*
*/

function phpbb_hash_crypt_private($password, $setting, &$itoa64) {
  $itoa64 = './0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz';
  $output = '*';

  // Check for correct hash
  if (substr($setting, 0, 3) != '$H$') {
    return $output;
  }

  $count_log2 = strpos($itoa64, $setting[3]);

  if ($count_log2 < 7 || $count_log2 > 30) {
    return $output;
  }

  $count = 1 << $count_log2;
  $salt = substr($setting, 4, 8);

  if (strlen($salt) != 8) {
    return $output;
  }

  $hash = md5($salt . $password, true);
  do {
    $hash = md5($hash . $password, true);
  } while (--$count);

  $output = substr($setting, 0, 12);
  $output .= phpbb_hash_encode64($hash, 16, $itoa64);

  return $output;
}

function phpbb_hash_encode64($input, $count, &$itoa64) {
  $output = '';
  $i = 0;

  do {
    $value = ord($input[$i++]);
    $output .= $itoa64[$value & 0x3f];

    if ($i < $count) {
      $value |= ord($input[$i]) << 8;
    }

    $output .= $itoa64[($value >> 6) & 0x3f];

    if ($i++ >= $count) {
      break;
    }

    if ($i < $count) {
      $value |= ord($input[$i]) << 16;
    }

    $output .= $itoa64[($value >> 12) & 0x3f];

    if ($i++ >= $count) {
      break;
    }

    $output .= $itoa64[($value >> 18) & 0x3f];
  } while ($i < $count);

  return $output;
}

function phpbb_check_hash($password, $hash) {
  if (strlen($hash) == 34) {
    return (phpbb_hash_crypt_private($password, $hash, $itoa64) === $hash) ? true : false;
  }

  return (md5($password) === $hash) ? true : false;
}
?>