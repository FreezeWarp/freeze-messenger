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
        global $config;

        $this->email = $config['email'];
        $this->displayBacktrace = $config['displayBacktrace'];
        $this->code = $code;
        $this->string = $string;

        if ($this->code && !$return) $this->trigger(false, $httpError);
    }


    public function trigger($return = false, $httpError = 'HTTP/1.1 403 Forbidden') {
        ob_end_clean(); // Clean the output buffer and end it. This means that when we show the error in a second, there won't be anything else with it.
        header($httpError); // FimError is invoked when the user did something wrong, not us. (At least, it should be. I've been a little inconsistent.)

        $errorData = array_merge((array) $this->context, array(
            'string' => $this->code,
            'details' => (substr($this->string, 0, 1) === '[' || substr($this->string, 0, 1) === '{') ? json_decode($this->string, true) : $this->string,
            'contactEmail' => $this->email,
        ));

        if ($this->displayBacktrace) {
            $backtrace = debug_backtrace(DEBUG_BACKTRACE_PROVIDE_OBJECT);
            array_shift($backtrace); // Omits this function, fimError->trigger, from the backtrace.

            $errorData['file'] = $backtrace[1]['file'];
            $errorData['line'] = $backtrace[1]['line'];
            $errorData['trace'] = $backtrace;
        }


        if ($return) {
            return array(
                'exception' => $errorData,
            );
        }
        else {
            die(new apiData(array(
                'exception' => $errorData,
            )));
        }
    }


    public function value() {
        return $this->trigger(true);
    }
}