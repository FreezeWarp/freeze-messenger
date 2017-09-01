<?php
class CurlRequestMethod {
    const __default = self::GET;

    const GET = 1;
    const POST = 2;
    const PUT = 3;
    const DELETE = 4;

    public static function toString($method) {
        switch ($method) {
            case CurlRequestMethod::GET: return 'GET'; break;
            case CurlRequestMethod::POST: return 'POST'; break;
            case CurlRequestMethod::PUT: return 'PUT'; break;
            case CurlRequestMethod::DELETE: return 'DELETE'; break;
        }
    }
}