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

require_once('ApiData.php');
require_once('fimError.php');
require_once('fimError.php');
require('curlRequestMethod.php');
require(__DIR__ . '/../vendor/autoload.php');

/**
 * Performs a structured CURL request.
 *
 * @author Joseph Todd Parsons <josephtparsons@gmail.com>
 */
class curlRequest {
    /**
     * @var string The response populated after a request.
     */
    public $response;

    /**
     * @var string The final HTTP response header populated after a request.
     */
    public $responseHeader;

    /**
     * @var string The file to request. Includes domain.
     */
    public $requestFile = '';

    /**
     * @var array A list of headers returned with the response.
     */
    public $allHeaders = [];

    /**
     * @var array An array of request body parameters for the request.
     */
    public $requestData = '';

    /**
     * Initialises class.
     *
     * @param array $data - Request data (as array).
     * @param array $apiFile - The file to query.
     * @return void
     *
     * @author Joseph Todd Parsons <josephtparsons@gmail.com>
     */
    public function __construct($apiFile = '', $dataHead = array(), $dataBody = array()) {
        if (count($dataBody) > 0) {
            $this->setRequestData($dataBody);
        }

        if (strlen($apiFile) > 0) {
            $this->setRequestFile($apiFile . (count($dataHead) ? '?' . http_build_query($dataHead) : ''));
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
        $this->requestData = $data;
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
    public function execute($method = CurlRequestMethod::GET) {
        $this->guzzle($method);
    }


    public function guzzle($method) {
        $client = new GuzzleHttp\Client([
            'http_errors' => false,
        ]);

        $effectiveUrl = '';

        $queryParams = [
            'on_stats' => function (GuzzleHttp\TransferStats $stats) use (&$effectiveUrl) {
                $effectiveUrl = $stats->getEffectiveUri();
            }
        ];
        if ($this->requestData) {
            $queryParams['form_params'] = $this->requestData;
        }

        $response = $client->request($method, $this->requestFile, $queryParams);

        $this->response = (string) $response->getBody();
        $this->responseHeader = $response->getStatusCode();
        $this->redirectLocation = $effectiveUrl;
    }


    /**
     * Executes the cURL request.
     *
     * @return mixed - cURL response on success, false on failure.
     *
     * @author Joseph Todd Parsons <josephtparsons@gmail.com>
     */
    public function executePOST() {
        return $this->execute(CurlRequestMethod::POST);
    }

    /**
     * Executes the cURL request.
     *
     * @return mixed - cURL response on success, false on failure.
     *
     * @author Joseph Todd Parsons <josephtparsons@gmail.com>
     */
    public function executeGET() {
        return $this->execute(CurlRequestMethod::GET);
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


    public static function quickRunPOST($apiFile, $dataHead, $dataBody) {
        $curl = new curlRequest($apiFile, $dataHead, $dataBody);
        $curl->executePOST();
        return $curl->getAsJson();
    }


    public static function quickRunGET($apiFile, $data) {
        $curl = new curlRequest($apiFile, $data);
        $curl->executeGET();
        return $curl->getAsJson();
    }


    /**
     * Verify whether a given resource exists or not.
     */
    function getAsJson() {
        $json = json_decode($this->response, true);

        if (json_last_error() == JSON_ERROR_NONE) {
            return $json;
        }
        else {
            trigger_error("Invalid JSON from cURL: " . $this->response, E_USER_WARNING);
            return false;
        }
    }
}
?>