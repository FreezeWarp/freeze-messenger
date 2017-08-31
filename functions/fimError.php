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
 * Class fimError
 *
 * Note: fimError can not be caught. It is intended to display data to the API.
 */
class fimError extends Exception {
    public function __construct($code = false, $string = false, $context = array(), $return = false, $httpError = 'HTTP/1.1 403 Forbidden') {
        if ($code && !$return) throw new fimErrorThrown($code, $string, $context, $httpError);
    }
}

class fimErrorThrown extends Exception {
    protected $code;
    protected $string;
    protected $context;
    protected $httpError;

    public function __construct($code = '', $string = '', $context = array(), $httpError = 'HTTP/1.1 403 Forbidden') {
        $this->code = $code;
        $this->string = $string;
        $this->context = $context;
        $this->httpError = $httpError;
    }

    /**
     * @return string
     */
    public function isCode(): string {
        return $this->code;
    }

    /**
     * @return string
     */
    public function getString(): string {
        return $this->string;
    }

    /**
     * @return array
     */
    public function getContext(): array {
        return $this->context;
    }

    /**
     * @return string
     */
    public function getHttpError(): string {
        return $this->httpError;
    }
}