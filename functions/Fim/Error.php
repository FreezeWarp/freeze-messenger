<?php
/**
 * Created by PhpStorm.
 * User: joseph
 * Date: 16/01/18
 * Time: 04:14
 */

namespace Fim;

class Error
{
    /** @var string The HTTP response string to use for 403 Forbidden responses. */
    const HTTP_403_FORBIDDEN = "HTTP/1.1 403 Forbidden";

    /** @var string The HTTP response string to use for 405 Method Not Allowed responses. */
    const HTTP_405_METHOD_NOT_ALLOWED = "HTTP/1.1 405 Method Not Allowed";

    /** @var string The HTTP response string to use for 429 Too Many Requests responses. */
    const HTTP_429_TOO_MANY = "HTTP/1.1 429 Too Many Requests";

    /** @var string The HTTP response string to use for 500 Internal Server Error responses. */
    const HTTP_500_INTERNAL = "HTTP/1.1 500 Internal Server Error";

    /**
     * @var \Fim\ErrorThrown
     */
    private $instance;

    /**
     * Fim\fimErrorThrown constructor.
     * @param string $code {@link Fim\fimErrorThrown::$code}
     * @param string $string {@link Fim\fimErrorThrown::$string}
     * @param string $return If true, this will store the Exception instance instead of throwing it.
     * @param array $context {@link Fim\fimErrorThrown::$context}
     * @param string $httpError {@link Fim\fimErrorThrown::$httpError}
     */
    public function __construct($code = false, $string = false, $context = array(), $return = false, $httpError = self::HTTP_403_FORBIDDEN) {
        if ($code && !$return)
            throw new \Fim\ErrorThrown($code, $string, $context, $httpError);

        else $this->instance = new \Fim\ErrorThrown($code, $string, $context, $httpError);
    }

    /**
     * Get the data of this error instance, if initialised with $return. See {@link Fim\fimErrorThrown::getArray()}
     * @return array
     */
    public function getArray() : array {
        return $this->instance->getArray();
    }
}