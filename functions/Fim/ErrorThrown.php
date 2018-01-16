<?php
/**
 * Created by PhpStorm.
 * User: joseph
 * Date: 16/01/18
 * Time: 04:12
 */

namespace Fim;
use Exception;

/**
 * This is the counterpart to Fim\fimError that is actually thrown. It is separate from Fim\fimError to allow either throwing or returning an instance of this.
 */
class ErrorThrown extends Exception
{
    /**
     * @var string A short code uniquely identifying the error.
     */
    protected $code;

    /**
     * @var string A longer description of the error. Typically used to inform either the developer or the client user, depending on the type of error.
     */
    protected $string;

    /**
     * @var array Additional information about the error. May detail valid parameters such as to fix the error, etc.
     */
    protected $context = [];

    /**
     * @var string The HTTP header to use when returning an HTTP response.
     */
    protected $httpError;

    /**
     * Fim\fimErrorThrown constructor.
     *
     * @param string $code      {@link Fim\fimErrorThrown::$code}
     * @param string $string    {@link Fim\fimErrorThrown::$string}
     * @param array  $context   {@link Fim\fimErrorThrown::$context}
     * @param string $httpError {@link Fim\fimErrorThrown::$httpError}
     */
    public function __construct($code = '', $string = '', $context = [], $httpError = 'HTTP/1.1 403 Forbidden')
    {
        $this->code = $code;
        $this->string = $string;
        $this->context = (array)$context;
        $this->httpError = $httpError;
    }

    /**
     * @return string {@link Fim\fimErrorThrown::$string}
     */
    public function getString(): string
    {
        return $this->string;
    }

    /**
     * @return array {@link Fim\fimErrorThrown::$context}
     */
    public function getContext(): array
    {
        return $this->context;
    }

    /**
     * @return string {@link Fim\fimErrorThrown::$httpError}
     */
    public function getHttpError(): string
    {
        return $this->httpError;
    }


    public function getArray(): array
    {
        return [
            'string'  => $this->code,
            'details' => $this->string,
            'context' => $this->context,
        ];
    }
}