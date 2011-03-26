<?php
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
?>