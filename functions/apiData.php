<?php
class apiData {
  private $format;
  private $data;
  private $xmlEntitiesFind;
  private $xmlEntitiesReplace;
  private $xmlAttrEntitiesFind;
  private $xmlAttrEntitiesReplace;


  function __construct($data = false, $format = false) {
    $this->replaceData($data);
    $this->format = $format ? $format : $_REQUEST['fim3_format'];
  }


  function setXMLEntities($find, $replace, $attrFind, $attrReplace) {
    $this->xmlEntitiesFind = $find;
    $this->xmlEntitiesReplace = $replace;
    $this->xmlAttrEntitiesFind = $attrFind;
    $this->xmlAttrEntitiesReplace = $attrReplace;
  }


  function replaceData($data) {
    $this->data = $data;
  }


  /**
   * Encodes a string as specifically-formatted XML data, converting "&", "'", '"', "<", and ">" to their equivilent values.
   *
   * @param string $data - The data to be encoded.
   * @return string - Encoded data.
   * @author Joseph Todd Parsons <josephtparsons@gmail.com>
   */
  function encodeXml($data) {
    return str_replace("\n", '&#xA;', str_replace($this->xmlEntitiesFind, $this->xmlEntitiesReplace, $data));
  }


  /**
   * Encodes a string as specifically-formatted XML data attribute.
   *
   * @param string $data - The data to be encoded.
   * @return string - Encoded data.
   * @author Joseph Todd Parsons <josephtparsons@gmail.com>
   */
  function encodeXmlAttr($data) {
    return str_replace($this->xmlAttrEntitiesFind, $this->xmlAttrEntitiesReplace, $data); // Replace the entities defined in $config (these are usually not changed).
  }


  /**
   * API Layer
   *
   * @param array $array
   * @return string
   * @author Joseph Todd Parsons <josephtparsons@gmail.com>
   */
  function output() {
    header('FIM-API-VERSION: 3b4dev');

    switch ($this->format) {
      case 'phparray':                                                return $this->outputArray($this->data); break; // print_r
      case 'keys':                                                    return $this->outputKeys($this->data);  break; // HTML List format for the keys only (documentation thing)
      case 'jsonp':         header('Content-type: application/json'); return 'fim3_jsonp.parse(' . $this->outputJson($this->data) . ')'; break; // Javascript Object Notion for Cross-Origin Requests
      case 'json': default: header('Content-type: application/json'); return $this->outputJson($this->data);  break; // Javascript Object Notion
    }
  }


  /**
   * JSON Parser
   *
   * @param array $array
   * @param int $level
   * @return string
   * @author Joseph Todd Parsons <josephtparsons@gmail.com>
   */
  function outputJson($array) {
    $data = array();

    foreach ($array AS $key => $value) {
      $data[] = '"' . $key . '":' . $this->formatJsonValue($value);
    }

    return '{'. implode(",", $data) . '}';
  }


  function formatJsonValue($value) {
    if (is_array($value)) return $this->outputJson($value);
    elseif (is_object($value) && get_class($value) === 'apiOutputList') {
      $values = $value->getArray();
      foreach ($values AS &$v) $v = $this->formatJsonValue($v);
      return '[' . implode(',', $values) . ']';
    }
    elseif ($value === true)   return 'true';
    elseif ($value === false)  return 'false';
    elseif (is_string($value)) return '"' . str_replace("\n", '\n', addcslashes($value,"\"\\")) . '"';
    elseif (is_int($value) || is_float($value))    return $value;
    elseif ($value == '')      return '""';
    else throw new Exception('Unrecognised value.'); // Note: Uh... this could get caught by itself. Maybe just die()?
  }


  /**
   * Key Parser
   *
   * @param array $array
   * @param int $level
   * @return string
   * @author Joseph Todd Parsons <josephtparsons@gmail.com>
   */
  function outputKeys($array, $level = 0) { // Used only for creating documentation.
    $indent = '';
    $data = '';

    for ($i = 0; $i < $level; $i++) $indent .= '  ';

    foreach ($array AS $key => $value) {
      $key = explode(' ', $key);
      $key = $key[0];

      $data .= "$indent<li>$key</li>\n";

      if (is_array($value)) {
        $data .= $indent . '  <ul>
  ' . $this->outputKeys($value, $level + 1) . $indent . '</ul>
  ';
      }
    }

    return $data;
  }


  /**
   * Output Using print_r
   * @param array $array
   * @author Joseph Todd Parsons <josephtparsons@gmail.com>
   */
  function outputArray($array) {
    print_r($array, true);
  }
}

class apiOutputList {
  private $array;

  function __construct($array) {
    $this->array = $array;
  }

  function getArray() {
    return $this->array;
  }
}
?>