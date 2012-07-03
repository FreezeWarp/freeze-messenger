<?php

// Note: This is a wrapper for the API, more or less. Because of this, no data sanitiation is neccessary - the API handles it best.

$ch = curl_init($installUrl . "api/sendUser.php");
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, 'userName=' . $_POST['userName']);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1); /* obey redirects */
curl_setopt($ch, CURLOPT_HEADER, 0);  /* No HTTP headers */
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);  /* return the data */

$result = curl_exec($ch);

curl_close($ch);

?>