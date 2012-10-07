<?php
class curlRequest {
  public function __construct($data = array(), $apiFile = '') {
    if (count($data) > 0) {
      $this->setRequestData($data);
    }

    if (strlen($apiFile) > 0) {
      $this->setRequestFile($apiFile);
    }
  }

  public function getRequestData() {
    return $this->requestData;
  }

  public function setRequestData($data) {
    $this->requestData = http_build_query($data);
  }

  public function getRequestFile() {
    return $this->requestFile;
  }

  public function setRequestFile($file) {
    $this->requestFile = $file;
  }

  public function execute() {
    global $installUrl;
    $ch = curl_init($installUrl . $this->requestFile); // $installUrl is automatically generated at installation (if the doamin changes, it will need to be updated).
    curl_setopt($ch, CURLOPT_POST, TRUE);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $this->requestData);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, TRUE); /* obey redirects */
    curl_setopt($ch, CURLOPT_HEADER, FALSE);  /* No HTTP headers */
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);  /* return the data */

    if ($che = curl_error($ch)) {
      curl_close($ch);
      trigger_error('Curl Error: ' . $che, E_USER_ERROR);
      return false;
    }
    else {
      $result = curl_exec($ch);
      curl_close($ch);
      return $result;
    }
  }
}
?>