<?php

/**
 * Class fimError
 *
 * Note: fimError can not be caught. It is intended to display data to the API.
 */
class fimError extends Exception {
  public function __construct($code = false, $string = false, $context = array()) {
    global $config;

    $this->email = $config['email'];
    $this->displayBacktrace = $config['displayBacktrace'];

    if ($code) $this->trigger($code, $string, $context);
  }


  public function trigger($code, $string = '', $context = array()) {
    ob_end_clean(); // Clean the output buffer and end it. This means that when we show the error in a second, there won't be anything else with it.
    header('HTTP/1.1 500 Internal Server Error'); // When an exception is encountered, we throw an error to tell the server that the software effectively is broken.

    $errorData = array_merge($context, array(
      'string' => $code,
      'details' => (substr($string, 0, 1) === '[' || substr($string, 0, 1) === '{') ? json_decode($string) : $string,
      'contactEmail' => $this->email,
    ));

    if ($this->displayBacktrace) {
      $backtrace = debug_backtrace();

      $errorData['file'] = $backtrace[1]['file'];
      $errorData['line'] = $backtrace[1]['line'];
      $errorData['trace'] = $backtrace;
    }



    die(new apiData(array(
      'exception' => $errorData,
    )));
  }
}