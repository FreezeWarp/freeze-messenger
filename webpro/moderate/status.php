<?php
if (!defined('WEBPRO_INMOD')) {
  die();
}
else {
  if ($user['adminDefs']['modCore']) {
    echo container('System Requirements & Status','<ul>
      <li>Database</li>
      <ul>
        <li>MySQL 5.0.5+</li>
        <li>MySQLi Extension (' . (extension_loaded('mysqli') ? 'Looks Good' : '<strong>Not Detected</strong>') . ')</li>
      </ul>
      <li>PHP 5.2+ (' . (floatval(phpversion()) > 5.2 ? 'Looks Good' : 'Not Detected - Version ' . phpversion() . ' Installed') . ')</li>
      <ul>
        <li>MySQL Extension (' . (extension_loaded('mysql') ? 'Looks Good' : '<strong>Not Detected</strong>') . ')</li>
        <li>Hash Extension (' . (extension_loaded('hash') ? 'Looks Good' : '<strong>Not Detected</strong>') . ')</li>
        <li>Date/Time Extension (' . (extension_loaded('date') ? 'Looks Good' : '<strong>Not Detected</strong>') . ')</li>
        <li>MCrypt Extension (' . (extension_loaded('mcrypt') ? 'Looks Good' : '<strong>Not Detected</strong>') . ')</li>
        <li>PCRE Extension (' . (extension_loaded('pcre') ? 'Looks Good' : '<strong>Not Detected</strong>') . ')</li>
        <li>Multibyte String Extension (' . (extension_loaded('mbstring') ? 'Looks Good' : '<strong>Not Detected</strong>') . ')</li>
        <li>SimpleXML Extension (' . (extension_loaded('simplexml') ? 'Looks Good' : '<strong>Not Detected</strong>') . ')</li>
        <li>APC Extension (' . (extension_loaded('apc') ? 'Looks Good' : '<strong>Not Detected</strong>') . ')</li>
      </ul>
    </ul>');
  }
  else {
    echo 'You do not have permission to view system info.';
  }
}
?>