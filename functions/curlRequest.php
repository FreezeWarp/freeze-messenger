<?php
/* FreezeMessenger Copyright © 2017 Joseph Todd Parsons

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

require_once('apiData.php');
require_once('fimError.php');

/**
 * Performs a structured CURL request.
 *
 * @author Joseph Todd Parsons <josephtparsons@gmail.com>
 */
class curlRequest {
    public $response;

    /**
     * Initialises class.
     *
     * @param array $data - Request data (as array).
     * @param array $apiFile - The file to query.
     * @return void
     *
     * @author Joseph Todd Parsons <josephtparsons@gmail.com>
     */
    public function __construct($data = array(), $apiFile = '') {
        if (count($data) > 0) {
            $this->setRequestData($data);
        }

        if (strlen($apiFile) > 0) {
            $this->setRequestFile($apiFile);
        }
    }

    /**
     * Returns the request data.
     *
     * @return array - Request data.
     *
     * @author Joseph Todd Parsons <josephtparsons@gmail.com>
     */
    public function getRequestData() {
        return $this->requestData;
    }

    /**
     * Sets the request data.
     *
     * @param array $data - Request data.
     * @return void
     *
     * @author Joseph Todd Parsons <josephtparsons@gmail.com>
     */
    public function setRequestData($data) {
        $this->requestData = http_build_query($data);
    }

    /**
     * Returns the request file.
     *
     * @return string - Request file.
     *
     * @author Joseph Todd Parsons <josephtparsons@gmail.com>
     */
    public function getRequestFile() {
        return $this->requestFile;
    }

    /**
     * Sets the request file.
     *
     * @param string $file - Request file.
     * @return void
     *
     * @author Joseph Todd Parsons <josephtparsons@gmail.com>
     */
    public function setRequestFile($file) {
        $this->requestFile = $file;
    }

    /**
     * Executes the cURL request.
     *
     * @return mixed - cURL response on success, false on failure.
     *
     * @author Joseph Todd Parsons <josephtparsons@gmail.com>
     */
    public function execute() {
        global $installUrl;

        if (function_exists('curl_init')) {
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
                $this->response = curl_exec($ch);

                if (curl_getinfo($ch, CURLINFO_HTTP_CODE) === 200) {
                    curl_close($ch);
                    return $this->response;
                }
                else {
                    curl_close($ch);
                    return false;
                }
            }
        }

        elseif (function_exists('fsockopen')) {
            $errno = false;
            $errstr = false;

            $urlData = parse_url($installUrl . $this->requestFile); // parse the given URL

            $fp = fsockopen($urlData['host'], 80, $errno, $errstr); // open a socket connection on port 80

            if ($fp) {
                // send the request headers:
                fputs($fp, "POST " . $urlData['path'] . " HTTP/1.1\r\n");
                fputs($fp, "Host: " . $urlData['host'] . "\r\n");

                fputs($fp, "Content-type: application/x-www-form-urlencoded\r\n");
                fputs($fp, "Content-length: ". strlen($this->requestData) ."\r\n");
                fputs($fp, "Connection: close\r\n\r\n");
                fputs($fp, $this->requestData);

                $result = '';

                while(!feof($fp)) {
                    $result .= fgets($fp, 128); // receive the results of the request
                }
            }

            // close the socket connection:
            fclose($fp);

            if ($errno) {
                trigger_error('FSock Error: ' . $errstr);
                return false;
            }
            else {
                // split the result header from the content
                $result = explode("\r\n\r\n", $result, 2); // Return headers in [0], return content in [1].
                $this->response = isset($result[1]) ? $result[1] : '';

                // return as structured array:
                return $this->response;
            }
        }

        else {
            throw new Exception('fim_curl: no compatible PHP function found. Please enable fsock or curl.');
        }
    }


    /**
     * Verify whether a given resource exists or not.
     */
    public static function exists($file) {
        global $config;

        if (function_exists('curl_init')) {
            $ch = curl_init($file); // $installUrl is automatically generated at installation (if the doamin changes, it will need to be updated).
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, TRUE); /* obey redirects */
            curl_setopt($ch, CURLOPT_USERAGENT, $config['curlUA']);
            curl_setopt($ch, CURLOPT_NOBODY, true);
            curl_exec($ch);

            $retcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            return $retcode === 200;
        }
        else {
            /* TODO: redirect handling (borrow from MirrorReader) */
            $file_headers = @get_headers($file);
            return ($file_headers && $file_headers[0] !== 'HTTP/1.1 404 Not Found');
        }
    }


    function getAsJson() {
        $json = json_decode($this->response, true);

        if (json_last_error() == JSON_ERROR_NONE) {
            return $json;
        }
        else {
            return false;
            // throw new fimError("cURLBadJson", "Invalid JSON from cURL (Error: '" . json_last_error() . "')'")
        }
    }
}
?>