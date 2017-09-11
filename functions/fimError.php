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

/**
 * An error class that is intended to communicate the error to a client user or client developer. In some cases, it may be used to communicate errors to the FreezeMessenger developer, but such communication usually uses normally exceptions.
 */
class fimError extends Exception {
    const HTTP_403_FORBIDDEN = "HTTP/1.1 403 Forbidden";
    const HTTP_429_TOO_MANY = "HTTP/1.1 429 Too Many Requests";
    const HTTP_500_INTERNAL = "HTTP/1.1 500 Internal Server Error";
    /**
     * fimErrorThrown constructor.
     * @param string $code {@link fimErrorThrown::$code}
     * @param string $string {@link fimErrorThrown::$string}
     * @param string $return If true, this will return the Exception instance instead of throwing it.
     * @param array $context {@link fimErrorThrown::$context}
     * @param string $httpError {@link fimErrorThrown::$httpError}
     */
    public function __construct($code = false, $string = false, $context = array(), $return = false, $httpError = fimError::HTTP_403_FORBIDDEN) {
        if ($code && !$return) throw new fimErrorThrown($code, $string, $context, $httpError);
        else return new fimErrorThrown($code, $string, $context, $httpError);
    }
}


/**
 * This is the counterpart to fimError that is actually thrown. It is separate from fimError to allow either throwing or returning an instance of this.
 */
class fimErrorThrown extends Exception {
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
     * fimErrorThrown constructor.
     * @param string $code {@link fimErrorThrown::$code}
     * @param string $string {@link fimErrorThrown::$string}
     * @param array $context {@link fimErrorThrown::$context}
     * @param string $httpError {@link fimErrorThrown::$httpError}
     */
    public function __construct($code = '', $string = '', $context = array(), $httpError = 'HTTP/1.1 403 Forbidden') {
        $this->code = $code;
        $this->string = $string;
        $this->context = (array) $context;
        $this->httpError = $httpError;
    }

    /**
     * @return string {@link fimErrorThrown::$string}
     */
    public function getString(): string {
        return $this->string;
    }

    /**
     * @return array {@link fimErrorThrown::$context}
     */
    public function getContext(): array {
        return $this->context;
    }

    /**
     * @return string {@link fimErrorThrown::$httpError}
     */
    public function getHttpError(): string {
        return $this->httpError;
    }
}