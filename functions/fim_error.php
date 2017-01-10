<?php

/**
 * Class fimError
 *
 * Note: fimError can not be caught. It is intended to display data to the API.
 */
class fimError extends Exception {
    public function __construct($code = false, $string = false, $context = array(), $return = false) {
        global $config;

        $this->email = $config['email'];
        $this->displayBacktrace = $config['displayBacktrace'];
        $this->code = $code;
        $this->string = $string;

        if ($this->code && !$return) $this->trigger();
    }


    public function trigger($return = false) {
        ob_end_clean(); // Clean the output buffer and end it. This means that when we show the error in a second, there won't be anything else with it.
        header('HTTP/1.1 403 Forbidden'); // FimError is invoked when the user did something wrong, not us. (At least, it should be. I've been a little inconsistent.)

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