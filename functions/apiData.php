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

class apiData {
    private $format;
    private $data;
    private $xmlEntitiesFind;
    private $xmlEntitiesReplace;
    private $xmlAttrEntitiesFind;
    private $xmlAttrEntitiesReplace;
    public $jsonDepthLimit = 15;


    public function __construct($data = false, $format = false) {
        $this->replaceData($data);
        $this->format = $format ?: (isset($_REQUEST['fim3_format']) ? $_REQUEST['fim3_format'] : 'json');
    }


    public function setXMLEntities($find, $replace, $attrFind, $attrReplace) {
        $this->xmlEntitiesFind = $find;
        $this->xmlEntitiesReplace = $replace;
        $this->xmlAttrEntitiesFind = $attrFind;
        $this->xmlAttrEntitiesReplace = $attrReplace;
    }


    public function replaceData($data) {
        $this->data = $data;
    }


    /**
     * API Layer
     *
     * @return string
     * @author Joseph Todd Parsons <josephtparsons@gmail.com>
     */
    public function __toString() {
        header('FIM-API-VERSION: 3b4dev');

        switch ($this->format) {
            case 'phparray':                                                return $this->outputArray($this->data); break; // print_r
            case 'keys':                                                    return $this->outputKeys($this->data);  break; // HTML List format for the keys only (documentation thing)
            case 'jsonp':         header('Content-type: application/json'); return 'fim3_jsonp.parse(' . $this->outputJson($this->data) . ')'; break; // Javascript Object Notion for Cross-Origin Requests
            case 'json': default: header('Content-type: application/json'); return $this->outputJson($this->data);  break; // Javascript Object Notion
        }
    }


    /**
     * Encodes a string as specifically-formatted XML data, converting "&", "'", '"', "<", and ">" to their equivilent values.
     *
     * @param string $data - The data to be encoded.
     * @return string - Encoded data.
     * @author Joseph Todd Parsons <josephtparsons@gmail.com>
     */
    private function encodeXml($data) {
        return str_replace("\n", '&#xA;', str_replace($this->xmlEntitiesFind, $this->xmlEntitiesReplace, $data));
    }


    /**
     * Encodes a string as specifically-formatted XML data attribute.
     *
     * @param string $data - The data to be encoded.
     * @return string - Encoded data.
     * @author Joseph Todd Parsons <josephtparsons@gmail.com>
     */
    private function encodeXmlAttr($data) {
        return str_replace($this->xmlAttrEntitiesFind, $this->xmlAttrEntitiesReplace, $data); // Replace the entities defined in $config (these are usually not changed).
    }


    /**
     * JSON Parser
     *
     * @param array $array
     * @param int $level
     * @return string
     * @author Joseph Todd Parsons <josephtparsons@gmail.com>
     */
    private function outputJson($array, $depth = 0) {
        if ($depth > $this->jsonDepthLimit) return '"<<cut>>"';

        $data = array();

        foreach ($array AS $key => $value) {
            $data[] = '"' . $key . '":' . $this->formatJsonValue($value, $depth + 1);
        }

        return '{'. implode(",", $data) . '}';
    }


    private function outputJsonArray($array, $depth = 0) {
        if ($depth > $this->jsonDepthLimit) return '"<<cut>>"';

        $data = array();

        foreach ($array AS $value)
            $data[] = $this->formatJsonValue($value, $depth + 1);

        return '['. implode(",", $data) . ']';
    }


    function formatJsonValue($value, $depth = 0) {
        if ($depth > $this->jsonDepthLimit) return '"<<cut>>"';

        if (is_array($value)) {
            // http://stackoverflow.com/a/5969617
            for (reset($value); is_int(key($value)); next($value));
            if (is_null(key($value))) // The array is not associative (well, doesn't have non-numeric keys)
                return $this->outputJsonArray($value, $depth + 1);
            else
                return $this->outputJson($value, $depth + 1);
        }

        elseif (is_object($value) && get_class($value) === 'apiOutputDict') {
            $values = $value->getArray();

            if (count($values)) {
                foreach ($values AS $key => &$v) $v = "\"$key\": " . $this->formatJsonValue($v, $depth + 1);
                return '{' . implode(',', $values) . '}';
            }
            else {
                return '[]';
            }
        }
        elseif (is_object($value) && get_class($value) === 'apiOutputList') {
            $values = $value->getArray();

            if (count($values)) {
                foreach ($values AS &$v) $v = $this->formatJsonValue($v, $depth + 1);
                return '[' . implode(',', $values) . ']';
            }
            else {
                return '[]';
            }
        }
        elseif (is_object($value))
            return $this->formatJsonValue(get_object_vars($value), $depth + 1);
        elseif ($value === true)
            return 'true';
        elseif ($value === false)
            return 'false';
        elseif (is_string($value)) {
            // mb_convert_encoding removes non-UTF8 characters, ensuring that json_encode doesn't fail.
            return json_encode(function_exists("mb_convert_encoding") ? mb_convert_encoding($value, "UTF-8", "UTF-8") : $value, JSON_PARTIAL_OUTPUT_ON_ERROR, 1);
        }
        elseif (is_int($value) || is_float($value))
            return $value;
        elseif ($value == '')
            return '""';
        //else
        //    die('Unrecognised value type:' . gettype($value)); // We die() instead of throwing here in order to avoid recursion with the stacktrace.
    }


    /**
     * Key Parser
     *
     * @param array $array
     * @param int $level
     * @return string
     * @author Joseph Todd Parsons <josephtparsons@gmail.com>
     */
    private function outputKeys($array, $level = 0) { // Used only for creating documentation.
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
    private function outputArray($array) {
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

class apiOutputDict {
    private $array;

    function __construct($array) {
        $this->array = $array;
    }

    function getArray() {
        return $this->array;
    }
}
?>