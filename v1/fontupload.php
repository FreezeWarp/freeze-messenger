<?php
/* MySQL Login */
$sqlHost = '10.10.10.1';
$sqlUser = 'vrim10';
$sqlPassword = 'FyRwtruusT94TvMA';
$sqlDatabase = 'vbulletin';
$sqlPrefix = 'vrc_'; // The Prefix of all MySQL Tables, excluding those of vBulletin.

mysql_connect($sqlHost,$sqlUser,$sqlPassword);
mysql_select_db($sqlDatabase);

$data = file_get_contents('fontdata');
$dataRows = explode("\n",$data);
echo '<table><tr><th>Name</th><th>Data</th><th>Family</th></tr>';
foreach ($dataRows AS $row) {
  $dataColumns = explode('","',$row);
  $dataColumns[1] = mysql_real_escape_string($dataColumns[1]);
  $dataColumns[2] = mysql_real_escape_string($dataColumns[2]);
  $dataColumns[3] = mysql_real_escape_string(str_replace('"','',$dataColumns[3]));

  echo "<tr><td>$dataColumns[1]</td><td>$dataColumns[2]</td><td>$dataColumns[3]</td></tr>";

  mysql_query("INSERT INTO {$sqlPrefix}fonts (name, data, category) VALUES ('$dataColumns[1]', '$dataColumns[2]', '$dataColumns[3]')");
  echo mysql_error();
}
echo '</table>';