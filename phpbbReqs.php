<?php
function phpbb_hash($password) {
  $itoa64 = './0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz';

  $random_state = unique_id();
  $random = '';
  $count = 6;

  if (($fh = @fopen('/dev/urandom', 'rb'))) {
    $random = fread($fh, $count);
    fclose($fh);
  }

  if (strlen($random) < $count) {
    $random = '';

    for ($i = 0; $i < $count; $i += 16) {
      $random_state = md5(unique_id() . $random_state);
      $random .= pack('H*', md5($random_state));
    }
    $random = substr($random, 0, $count);
  }

  $hash = _hash_crypt_private($password, _hash_gensalt_private($random, $itoa64), $itoa64);

  if (strlen($hash) == 34) {
    return $hash;
  }

  return md5($password);
}



function _hash_crypt_private($password, $setting, &$itoa64) {
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
  $output .= _hash_encode64($hash, 16, $itoa64);

  return $output;
}

function _hash_encode64($input, $count, &$itoa64) {
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
    return (_hash_crypt_private($password, $hash, $itoa64) === $hash) ? true : false;
  }

  return (md5($password) === $hash) ? true : false;
}


if ($apiRequest) {
  if ($_SERVER['HTTP_REFERER'] && $installUrl) {
    if (strstr($_SERVER['HTTP_REFERER'],$installUrl)) {
      $apiRequestCheck = false;
    }
  }

  if ($apiRequestCheck !== false) {
    if (!$enableForeignApi) {
      die('Foreign API Disabled');
    }
    elseif ($insecureApi) {
      $apiRequestCheck = false;
    }
    else {
      $apiRequestCheck = true;
    }
  }
}
else {
  $apiRequestCheck = false;
}

function fim_generateSession() {
  global $salts;

  $salt = end($salts);

  if (function_exists('mt_rand')) {
    $rand = mt_rand(1,100000000);
  }
  elseif (function_exists('rand')) {
    $rand = rand(1,100000000);
  }

  /* The algorithm below may not be ideal. It is intended to minimize the ability to guess a hash via a bruteforce mechanism. To do so, we both require that the userId is correct later on (though doing so would make little difference if a hacker is attempting to breach a certain user) and that they know the salt used in the system (without knowing this, it is impossible to attempt to attack by guessing the uniqid - something that is possible). The additional rand is most likely redundant, but for the sake of paranoi it doesn't neccissarly hurt. */

  if (function_exists('hash')) {
    return uniqid('',true) . hash('sha256',hash('sha256',$rand) . $salt);
  }
  else {
    return uniqid('',true) . md5(md5($rand) . $salt);
  }
}

function fim_generatePassword($password) {
  global $salts;

  $salt = end($salts);

  /* Similar to generateSession, the algorthim used below is possibly inferrior, but still will withstand most basic methods, including rainbow tables and in many cases bruteforce (though this may not be true if an attacker is able to gain access to the associated config.php file; in this case, it still will be impossible to decipher anything more advanced than dictionary passwords). */

  if (function_exists('hash')) {
    return hash('sha256',hash('sha256',$password) . $salt);
  }
  else {
    return md5(md5($password) . $salt);
  }
}



///* Process Functions for Each Forum  *///

/* User should be array, password md5sum of plaintext. */
function processVBulletin($user,$password) {
  global $forumPrefix, $sqlUserTable, $sqlUserTableCols;

  if (!$user[$sqlUserTableCols['userId']]) {
    return false;
  }

  if ($user['password'] === md5($password . $user['salt'])) { // The password matches.
    global $user; // Make sure accessible elsewhere.
    return true;
  }

  else {
    return false;
  }
}

function processPHPBB($user, $password) {
  global $forumPrefix, $brokenUsers, $sqlUserTable, $sqlUserTableCols;

  if (!$user[$sqlUserTableCols['userId']]) {
    return false;
  }
  elseif (in_array($user['user_id'],$brokenUsers)) {
    return false;
  }

  if (phpbb_check_hash($password, $user['user_password'])) {
    return true;
  }
  else {
    return false;
  }
}

function processVanilla($user, $password) {
  global $tablePrefix, $sqlUserTable, $sqlUserTableCols;

  if (!$user[$sqlUserTableCols['userId']]) {
    return false;
  }
  else {

  }
}

function processLogin($user, $password) {
  global $loginMethod;

  switch ($loginMethod) {
    case 'vbulletin':
    return processVBulletin($user, $password);
    break;

    case 'phpbb':
    return processPHPBB($user, $password);
    break;

    case 'vanilla':
    return processVanilla($user, $password);
    break;
  }
}
?>