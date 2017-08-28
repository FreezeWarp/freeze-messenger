<?php
use PHPUnit\Framework\TestCase;
require('functions/curlRequest.php');

class DataTest extends TestCase
{
    public static $host = 'http://localhost/freeze-messenger';
    public static $sessionToken;

    public static function setUpBeforeClass() {
        $login = curlRequest::quickRunPOST(self::$host . "/validate.php", [], [
            "username" => "admin",
            "password"=> "admin",
            "client_id" => "WebPro",
            "grant_type" => "password"
        ]);
        self::$sessionToken = $login['login']['access_token'];
    }
    /**
     * @dataProvider additionProvider
     */
    public function testRun($actual, $expected)
    {
        $this->assertEquals($actual, $expected);
    }

    public function additionProvider()
    {
        return [
            'Getting Messages, No Login'  => [curlRequest::quickRunGET(self::$host . "/api/message.php", []), ['exception', 'string'], 'noLogin'],
            'Getting Messages, No Room ID' => [curlRequest::quickRunGET(self::$host . "/api/message.php", ["access_token" => self::$sessionToken])['exception']['string'], 'noRoom'],
        ];
    }
}
?>