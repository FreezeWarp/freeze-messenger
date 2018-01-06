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


/********************************************************
 ************************ START **************************
 *********************** Wrappers ************************
 *********************************************************/

use Fim\Room;



/**
 * Generates a SHA256 using whatever methods are available. If no valid function can be found, an empty string will be returned (...don't use this for password hashin).
 *
 * @param string $data The data to hash.
 * @return string Hashed data.
 */
function fim_sha256($data) {
    // hash() is available in PHP 5.1.2+, or in PECL Hash 1.1. Algorithms vary, so we must make sure sha256 is one of them.
    if (function_exists('hash')
        && in_array('sha256', hash_algos()))
        return hash('sha256', $data);

    // mhash() is available in pretty much all versions of PHP, but the SHA256 algo may not be available.
    elseif (function_exists('mhash')
        && defined('MHASH_SHA256'))
        return mhash(MHASH_SHA256, $data);

    // Otherwise, return empty string.
    else {
        return '';
    }
}


/**
 * A wrapper for rand and mt_rand, using whichever is available (or returning $min if neither is).
 *
 * @param int $min The minimum value.
 * @param int $max The maximum value.

 * @return int A value between $min and $max, hopefully choosen randomly.
 */
function fim_rand($min, $max) {
    // Proper hardware-based rand, actually works.
    if (function_exists('mt_rand'))
        return mt_rand($min, $max);

    // Standard rand, not well seeded.
    elseif (function_exists('rand'))
        return rand($min, $max);

    // Though it should never happened, applications should still /run/ if no rand function exists. Keep this in mind when using fim_rand.
    else
        return $min;
}








/********************************************************
 ************************ START **************************
 ******************** Misc Functions *********************
 *********************************************************/
/**
 * Determines if any value in an array is found in a seperate array.
 *
 * @param array $needle - The array that contains all values that will be applied to $haystack
 * @param array $haystack - The matching array.
 * @param bool $all - Only return true if /all/ values in $needle are in $haystack.
 * @return bool
 *
 * @author Joseph Todd Parsons <josephtparsons@gmail.com>
 */
function fim_inArray(array $needle, array $haystack, $all = false) {
    if (!$haystack) return false; // If the haystack is not valid, return false.
    elseif (!$needle) return false; // If the needle is not valid, return false.
    else {
        foreach($needle AS $need) { // Run through each entry of the needle
            if ($all) { // All values must be found.
                if (!$need) return false; // If the needle value is false, skip it.
                if (in_array($need, $haystack)) continue; // If the needle is in the haystack, return true.
            }
            else { // Only one value must be found.
                if (!$need) continue; // If the needle value is false, skip it.
                if (in_array($need, $haystack)) return true; // If the needle is in the haystack, return true.
            }
        }

        if ($all) {
            return true; // If we have found all values, return true.
        }
        else {
            return false; // If we haven't found a value, return false.
        }
    }
}


/**
 * Converts a date of birth to age.
 *
 * @author Joseph Todd Parsons <josephtparsons@gmail.com>
 */
function fim_dobToAge($date) {
    $dateTime = new DateTime();
    $dateTime->setTimestamp($date);

    $dateTimeNow = new DateTime("now");

    return (int) $dateTimeNow->diff($dateTime)->format("%y"); // Generate an age by taking a unix timestamp and subtracting the timestamp of the user's DOB. Divide to create years.
}


function fim_startsWith($haystack, $needle) {
    return strpos($haystack, $needle, 0) === 0;
}

function fim_endsWith($haystack, $needle) {
    return strrpos($haystack, $needle, 0) === (strlen($haystack) - strlen($needle));
}











/********************************************************
 ************************ START **************************
 **************** Data Handling Functions ****************
 *********************************************************/

/**
 * Strict Sanitization of GET/POST/COOKIE Globals
 *
 * @param array data
 * @return array
 * @author Joseph Todd Parsons <josephtparsons@gmail.com>
 */
function fim_sanitizeGPC($type, $data) {
    $newData = [];


    /* Define Defaults */
    $metaDataDefaults = array(
        'cast' => 'string',
        'require' => false,
        'trim' => false,
        'filter' => '',
        'evaltrue' => false,

        // Others: min, max, valid, default
    );



    /* Store/Parse Request Body */
    $requestBody = [];
    if ($_SERVER['REQUEST_METHOD'] === 'PUT' && in_array($type, array('p', 'post'))) {
        parse_str(file_get_contents('php://input'), $requestBody);
    }

    switch ($type) { // Set the GLOBAL to a local var for processing.
        case 'g': case 'get': $activeGlobal = $_GET; break;
        case 'p': case 'post': $activeGlobal = array_merge($_POST, $requestBody); break;
        case 'r': case 'request': $activeGlobal = array_merge($_REQUEST, $requestBody); break;
        default:
            throw new Exception('Invalid type in fim_sanitizeGPC');
            return false;
        break;
    }


    if (!is_array($activeGlobal)) $activeGlobal = array(); // Make sure the active global is populated with data.


    /* Process Active Global */
    foreach ($data AS $indexName => $indexData) {
        if ($indexName === '_action') {
            if (!isset($activeGlobal[$indexName])) {
                switch ($_SERVER['REQUEST_METHOD']) {
                    case 'GET':    $newData[$indexName] = 'get';    break;
                    case 'POST':   $newData[$indexName] = 'create'; break;
                    case 'PUT':    $newData[$indexName] = 'edit';   break;
                    case 'DELETE': $newData[$indexName] = 'delete'; break;
                }
            }
            else {
                $newData[$indexName] = $activeGlobal[$indexName];
            }
        }

        else {
            /* Validate Metadata */
            foreach ($indexData AS $metaName => $metaData) {
                if (!in_array($metaName, array('default', 'require', 'trim', 'evaltrue', 'valid', 'min', 'max', 'minLength', 'maxLength', 'filter', 'cast', 'transform', 'bitTable', 'flipTable', 'removeDuplicates', 'conflict', 'source')))
                    throw new Exception('Unrecognised metadata: ' . $metaName);

                elseif (($metaName === 'require' || $metaName === 'trim' || $metaName === 'evaltrue')
                    && !is_bool($metaData))
                    throw new Exception('Invalid "' . $metaName . '" in data in fim_sanitizeGPC');

                elseif ($metaName === 'valid' &&
                    !is_array($metaData))
                    throw new Exception('Defined valid value does not correspond to recognized data type (array).');

                elseif ($metaName === 'conflict' &&
                    !is_array($metaData))
                    throw new Exception('Defined conflict value does not correspond to recognized data type (array).');

                elseif (($metaName === 'min' || $metaName === 'max')
                    && !is_numeric($metaData))
                    throw new Exception('Invalid "' . $metaName . '" in data in fim_sanitizeGPC');

                elseif ($metaName === 'cast'
                    && !in_array($metaData, array('int', 'bool', 'string', 'json', 'list', 'dict', 'alphanum', 'bitfieldShift', 'roomId')))
                    throw new Exception("Invalid 'cast' (value = $metaData) in data in fim_sanitizeGPC.");

                elseif ($metaName === 'cast'
                    && $metaData === 'bitfieldShift'
                    && (!isset($indexData['flipTable']) || !is_array($indexData['flipTable'])))
                    throw new Exception("'bitfieldShift' cast missing corresponding flipTable or source parameter in fim_sanitizeGPC.");

                elseif ($metaName === 'transform'
                    && $metaData === 'bitfield'
                    && (!isset($indexData['bitTable']) || !is_array($indexData['bitTable'])))
                    throw new Exception("'bitfield' transform missing corresponding bitTable parameter in fim_sanitizeGPC.");

                elseif ($metaName === 'transform'
                    && !in_array($metaData, array('bitfield', 'csv')))
                    throw new Exception("Invalid 'transform' (value = $metaData) in data in fim_sanitizeGPC: valid options are 'bitfield'");

                elseif ($metaName === 'transform'
                    && $indexData['cast'] !== 'list')
                    throw new Exception("'transform' used on non-list in fim_sanitizeGPC");
            }



            $indexMetaData = array_merge($metaDataDefaults, $indexData); // Store indexMetaData with the defaults.



            if (isset($activeGlobal[$indexName])) {
                /* Check for conflicting directives. */
                if (isset($indexMetaData['conflict'])) {
                    foreach ($indexMetaData['conflict'] AS $conflict) {
                        if (isset($activeGlobal[$conflict])) {
                            $conflictArray = [$conflict, $indexName];
                            new fimError(fim_arrayOfPropertiesImplode($conflictArray) . 'Conflict');
                        }
                    }
                }

                /* Trim White Space */
                if ($indexMetaData['trim'] === true)
                    $activeGlobal[$indexName] = trim($activeGlobal[$indexName]);
            }


            /* Set to Default and Perform Validation */
            if (!(isset($indexMetaData['cast']) && $indexMetaData['cast'] === 'bitfieldShift')) { // bitfieldShift isn't normally set, so our default-setting is irrelevant for it
                // If the global is provided, check to see if it's valid. If not, throw error.
                if (isset($activeGlobal[$indexName], $indexMetaData['valid'])) {
                    if (isset($indexMetaData['cast']) && $indexMetaData['cast'] === 'list') {
                        if (count(array_diff($activeGlobal[$indexName], $indexMetaData['valid'])) > 0)
                            new fimError("{$indexName}InvalidValues", "Invalid value(s) for API parameter '$indexName': " . implode(', ', array_diff($activeGlobal[$indexName], $indexMetaData['valid'])));
                    }

                    elseif (isset($indexMetaData['cast']) && $indexMetaData['cast'] === 'dict')
                        throw new Exception("A 'valid' parameter was specified for '$indexName', but the 'dict' cast type does not support this parameter.");

                    elseif (!in_array($activeGlobal[$indexName], $indexMetaData['valid'])) {
                        if (isset($indexMetaData['default']))
                            $activeGlobal[$indexName] = $indexMetaData['default'];

                        else
                            new fimError("{$indexName}InvalidValue", "'{$activeGlobal[$indexName]}' is an invalid value for API parameter '$indexName'.");
                    }
                }

                // If the global is _not_ provided, then use the default if available.
                if (!isset($activeGlobal[$indexName]) &&
                    isset($indexMetaData['default'])) {
                    $activeGlobal[$indexName] = $indexMetaData['default'];
                }

                // Finally, if the global is thus-far unprovided...
                if (!isset($activeGlobal[$indexName])) {
                    if ($indexMetaData['require']) // And required, throw an exception.
                        new fimError("{$indexName}Required", "API parameter '$indexName' is required but was not provided.");
                    else // And not required, just ignore this global and move on to the next one.
                        continue;
                }
            }



            /* Casting */
            switch($indexMetaData['cast']) {
                /**
                 * Treat as JSON. Still working out the kinks here.
                 */
                case 'json':
                    $newData[$indexName] = json_decode(
                        $activeGlobal[$indexName],
                        true,
                        \Fim\Config::$jsonDecodeRecursionLimit,
                        JSON_BIGINT_AS_STRING
                    );
                break;


                /*
                 * Treat as an associative (two-dimensional) array.
                 * Most of list's parameters are omitted here, though the following still apply:
                 ** "filter" will cast values
                 ** "evaltrue" will remove array values (not keys) that are falsey
                 ** "valid" will remove any array value (not key) that is not present in the valid list.
                 */
                case 'dict':
                    // Make sure the passed element is an array -- we don't do any conversion to make it one.
                    if (!is_array($activeGlobal[$indexName]))
                        throw new fimError("{$indexName}NotArray", "API parameter '$indexName' must be an array.");

                    $arrayFromGlobal = $activeGlobal[$indexName];

                    // Apply filters, evaltrue, and valid -- these will cast the datatype, remove falsey entries, and remove entries not on the valid list respectively.
                    $newData[$indexName] = fim_arrayValidate(
                        $arrayFromGlobal,
                        ($indexMetaData['filter'] ? $indexMetaData['filter'] : 'string'),
                        ($indexMetaData['evaltrue'] ? false : true),
                        (isset($indexMetaData['valid']) && count($indexMetaData['valid']) > 0 ? $indexMetaData['valid'] : false)
                    );
                break;


                /*
                 * Treat as a list (one-dimensional array).
                 * We apply all kinds of filters here, only including truthy values if evaltrue is set, applying casts to the list's contents if filter is set, and tranforming the entire list into a new datatype if transform is set.
                 *
                 */
                case 'list':
                    // Make sure the passed element is an array -- we don't do any conversion to make it an array.
                    if (!is_array($activeGlobal[$indexName]))
                        new fimError("{$indexName}NotArray",  "{$indexName} must be an array.");

                    // Remove any array keys.
                    $arrayFromGlobal = array_values(
                        $activeGlobal[$indexName]
                    );

                    // Apply filters, evaltrue, and valid -- these will cast the datatype, remove falsey entries, and remove entries not on the valid list respectively.
                    $newData[$indexName] = fim_arrayValidate(
                        $arrayFromGlobal,
                        ($indexMetaData['filter'] ? $indexMetaData['filter'] : 'string'),
                        ($indexMetaData['evaltrue'] ? false : true),
                        (isset($indexMetaData['valid']) ? $indexMetaData['valid'] : false)
                    );

                    // Remove duplicate values from the list if required
                    if (isset($indexMetaData['removeDuplicates']) && $indexMetaData['removeDuplicates']) {
                        $newData[$indexName] = array_unique($newData[$indexName]);
                    }

                    // Detect maximum length
                    if (isset($indexMetaData['max']) && count($newData[$indexName]) > $indexMetaData['max'])
                        new fimError("{$indexName}MaxValues", "You have passed too many values for $indexName; most allowed is {$indexMetaData['max']}.");

                    // Transform the list into a single, non-list datatype
                    if (isset($indexMetaData['transform'])) {
                        switch ($indexMetaData['transform']) {
                            case 'bitfield':
                                $bitfield = 0;

                                foreach ($newData[$indexName] AS $name) {
                                    if (!$name)
                                        continue; // Allow empty values.
                                    elseif (!isset($indexMetaData['bitTable'][$name]))
                                        new fimError("{$indexName}UnknownValue", "'$name' is not a recognized value for API parameter '$indexName'");
                                    else
                                        $bitfield |= $indexMetaData['bitTable'][$name];
                                }

                                $newData[$indexName] = $bitfield;
                            break;

                            case 'csv':
                                $newData[$indexName] = implode(',', $newData[$indexName]);
                            break;

                            default:
                                throw new Exception("Unrecognised list transformation in data passed to fim_sanitizeGPC.");
                            break;
                        }
                    }
                break;


                /*
                 * Treat as an integer. When evaltrue is set, we only include the value in $newData if it is truthy (according to PHP's own logic after cast to an int).
                 * We also apply min/maxes here -- if the value exceeds max, it is set to max, and if it is under the min, it is set to the min.
                 */
                case 'int':
                    if (!$indexMetaData['evaltrue'] || (int) $activeGlobal[$indexName]) // If evaltrue is true, only include the value if it's true.
                        $newData[$indexName] = (int) $activeGlobal[$indexName];

                    if (isset($indexMetaData['min']) &&
                        $newData[$indexName] < $indexMetaData['min']) $newData[$indexName] = $indexMetaData['min']; // Minimum Value
                    elseif (isset($indexMetaData['max']) &&
                        $newData[$indexName] > $indexMetaData['max']) $newData[$indexName] = $indexMetaData['max']; // Maximum Value
                break;


                /*
                 * Treat as a bool, according to fim_cast's boolean logic -- which only treats a very small subset of truthy values as true.
                 * If we have a default, and the cast value is unrecognised by fim_cast (e.g. 2 is neither seen as true nor false), then it will set to default. Otherwise, it will set to null.
                 */
                case 'bool':
                    $newData[$indexName] = @fim_cast(
                        'bool',
                        $activeGlobal[$indexName],
                        (isset($indexMetaData['default']) ? $indexMetaData['default'] : null)
                    );
                break;


                /*
                 * Remove characters outside of the ASCII128 range.
                 */
                case 'ascii128':
                    $newData[$indexName] = preg_replace('/[^(\x20-\x7F)]*/', '', $activeGlobal[$indexName]);
                break;


                /*
                 * Remove characters that are non-alphanumeric. Note that we will try to romanise what we can, based on the $config directive romanisation.
                 */
                case 'alphanum':
                    $newData[$indexName] = preg_replace('/[^a-zA-Z0-9]*/', '', str_replace(array_keys(\Fim\Config::$romanisation), array_values(\Fim\Config::$romanisation), $activeGlobal[$indexName]));
                break;


                /* This is a funky one that really helps when dealing with bitfields.
                 * First of all, it uniquely has the "flipTable" parameter, which is an array of [bit => name]s.
                 * A name is a string pointing to another entry in the activeGlobal, while a bit is the bitvalue that name represents.
                 * If the activeGlobal doesn't have the name, we just ignore it. If it does have it, and it evaluates to true, then our string will contain an equation turning that bit on. If it evaluates to false, our equation will try and turn that bit off.
                 * This equation can then be used by $database->equation.
                 */
                case 'bitfieldShift':
                    $source = $indexMetaData['source'];

                    foreach ($indexMetaData['flipTable'] AS $bit => $name) {

                        if (!isset($activeGlobal[$name]))
                            continue;

                        elseif (@fim_cast(
                            'bool',
                            $activeGlobal[$name]
                        ))
                            $source |= $bit;

                        else
                            $source &= ~$bit;
                    }

                    $newData[$indexName] = $source;
                break;


                /*
                 * Basically, cast it to an integer if otherwise looks like one (e.g. the string "123"), keep it as-is if it's a private room ID (e.g. the string "p1,4,90"), or set it to null.
                 */
                case 'roomId':
                    $newData[$indexName] = @fim_cast('roomId', $activeGlobal[$indexName]);

                    if (!$newData[$indexName]) {
                        new fimError('roomIdInvalid', 'The room ID is not valid.');
                    }
                break;


                /*
                 * Treat as a string.
                 */
                default:
                    $newData[$indexName] = (string) $activeGlobal[$indexName]; // Append value as string-cast.
                break;
            }
        }


        /* If a required value was previously set, but was then unset by the above casts, we through an error. */
        if (!isset($newData[$indexName]) && $indexMetaData['require'])
            new fimError("{$indexName}Invalid", "API parameter '{$indexName}' is required, but the passed value is not valid."); // And required, throw an exception.
    }

    return $newData;
}


/**
 * Performs a custom cast, implementing custom logic for boolean casts (and the default logic for all others).
 *
 * @param string cast - Type of cast, either 'bool', 'int', 'float', or 'string'.
 * @param string value - Value to cast.
 * @param string default - Whether to lean true or false with bool casts. Only if a value is exactly true or false will thus value not be used.
 *
 * @author Joseph Todd Parsons <josephtparsons@gmail.com>
 */
function fim_cast($cast, $value, $default = null) {
    switch ($cast) {
        case 'bool':
            $trueValues = array('true', 1, true, '1');
            $falseValues = array('false', 0, false, '0');

            if (in_array($value, $trueValues, true)) { $value = true; } // Strictly matches one of the above true values
            elseif (in_array($value, $falseValues, true)) { $value = false; } // Strictly matches one of the above false values
            elseif (!is_null($default)) { $value = (bool) $default; } // There's a default
            else { $value = false; }
        break;

        case 'int': $value = (int) $value; break;
        case 'float': $value = (float) $value; break;
        case 'string': $value = (string) $value; break;
        case 'array': $value = (array) $value; break;

        case 'roomId':
            if (ctype_digit($value))
                $value = (int) $value;
            elseif (!Room::isPrivateRoomId($value))
                $value = null;
        break;

        default: throw new Exception('Unrecognised cast in fim_cast: ' . $cast); break;
    }

    return $value;
}


/**
 * Returns a "safe" array based on parameters.
 *
 * @param array $array - The array to be processed.
 * @param string $type - The variable type all entries in the returned array should corrospond to.
 * @param bool $preserveAll - Whether false, 0, and empty strings should be returned as a part of the array.
 * @return array
 * @author Joseph Todd Parsons <josephtparsons@gmail.com>
 */
function fim_arrayValidate($array, $type = 'int', $preserveAll = false, $allowedValues = false) {
    $arrayValidated = array(); // Create an empty array we will use to store things.

    if (is_array($array)) { // Make sure the array is an array.
        foreach ($array AS $key => $value) { // Run through each value of the array.
            if (is_array($allowedValues)
                && !in_array($value, $allowedValues)) continue;

            $preValue = @fim_cast($type, $value, false);

            if ($preValue || $preserveAll) $arrayValidated[$key] = $preValue; // Only keep falsey values if preserveAll is true
        }
    }

    return $arrayValidated; // Return the validated array.
}


/**
 * Implodes an array to be a consistent string properties list, with all properties alphabetised and all but the first property name capitalised.
 * For instance, arrayOfPropertiesImplode(["userId", "userName", "id"]) = "idUserIdUserName"
 *
 * @param $array array The source array.
 *
 * @return string The source array as a string of properties.
 */
function fim_arrayOfPropertiesImplode(&$array) {
    sort($array);
    $array = array_map(function($index, $value) {
        if ($index > 0) return ucfirst($value);
        return $value;
    }, array_keys($array), array_values($array));
    return implode('', $array);
}


/**
 * Filters an array to just contain the key-value pairs identified by the keys parameter.
 *
 * @param array $array The source array.
 * @param array $keys The keys to filter by.
 *
 * @return array An array containing only the key-value pairs with a key in $keys.
 */
function fim_arrayFilterKeys(array $array, array $keys) : array {
    $newArray = [];

    foreach ($array AS $key => $value) {
        if (in_array($key, $keys)) $newArray[$key] = $value;
    }

    return $newArray;
}


function fim_dbCastArrayEntry(array &$array, $keys, $cast) {
    foreach ((array) $keys AS $key) {
        if (isset($array[$key]))
           $array[$key] = new \Database\Type($cast, $array[$key]);
    }

    return $array;
}

function fim_castArrayEntry(array $array, $keys, $cast) {
    foreach ((array) $keys AS $key) {
        if (isset($array[$key])) {
            $array[$key] = new $cast($array[$key]);
        }
    }

    return $array;
}


/**
 * Tranforms and object into an array consisting only of the specified keys.
 *
 * @param object $object The source object.
 * @param array $keys The keys/object properties to filter by.
 *
 * @return array An array containing only the key-value pairs with a key in $keys, where value is the object's property value.
 */
function fim_objectArrayFilterKeys($object, array $keys) : array {
    $newArray = [];

    foreach ($keys AS $key) {
        $newArray[$key] = $object->{$key};
    }

    return $newArray;
}


/**
 * Join a string with a natural language conjunction at the end.
 * Derived from https://gist.github.com/dan-sprog/e01b8712d6538510dd9c
 */
function fim_naturalLanguageJoin(string $glue, array $list, string $conjunction = 'and') {
    $last = array_pop($list);
    if ($list) {
        return implode($glue, $list) . ' ' . $conjunction . ' ' . $last;
    }
    return $last;
}


/**
 * Acts like PHP's explode, but will return an empty array ([] instead of [""]) if passed an empty string or otherwise falsey value.
 *
 * @param string $separator
 * @param string $list
 * @return array
 */
function fim_emptyExplode(string $separator, $list) {
    return $list ? explode($separator, $list) : [];
}










/********************************************************
 ************************ START **************************
 ******************** Error Handling *********************
 *********************************************************/


/**
 * Custom exception handler. In general, all classes and functions are going to use exceptions so that they can be caught. But, the lazy coder that I am, I don't normally bother catching them -- these errors will hopefully give a user enough information if I can't be bothered.
 * TODO: allow arrays from fimError
 *
 * @author Joseph Todd Parsons <josephtparsons@gmail.com>
 */

function fim_exceptionHandler($exception) {
    $errorData = array(
        'contactEmail' => \Fim\Config::$email,
    );
    //ob_end_clean(); // Clean the output buffer and end it. This means that when we show the error in a second, there won't be anything else with it.

    if ($exception instanceof fimErrorThrown) {
        header($exception->getHttpError()); // FimError is invoked when the user did something wrong, not us. (At least, it should be. I've been a little inconsistent.)

        $errorData['string'] = $exception->getCode();
        $errorData['details'] = $exception->getString();
        $errorData['other'] = $exception->getContext();

        if (\Fim\Config::$displayBacktrace) {
            $backtrace = debug_backtrace(DEBUG_BACKTRACE_PROVIDE_OBJECT);
            //array_shift($backtrace); // Omits this function, fimError->trigger, from the backtrace.

            $errorData['file'] = ($backtrace[1] ?? $backtrace[0])['file'] ?? '';
            $errorData['line'] = ($backtrace[1] ?? $backtrace[0])['line'] ?? '';
            $errorData['trace'] = $backtrace;
        }
    }
    else {
        header(fimError::HTTP_500_INTERNAL); // When an exception is encountered, we throw an error to tell the server that the software effectively is broken.

        $errorData = array_merge($errorData, array(
            'string' => $exception->getMessage(),
            'contactEmail' => \Fim\Config::$email,
        ));

        if (\Fim\Config::$displayBacktrace) {
            $errorData['file'] = $exception->getFile();
            $errorData['line'] = $exception->getLine();
            $errorData['trace'] = $exception->getTrace();
        }
    }

    echo new Http\ApiData(array(
        'exception' => $errorData,
    ));
    die();
}



/**
 * Flushes The Output Buffer
 */
function fim_flush() {
    echo str_repeat(' ', 1024 * \Fim\Config::$outputFlushPaddingKilobytes); // Send padding, to make sure browsers receive stuff

    @ob_flush(); // Flush PHP output buffer
    flush(); // Flush webserver write buffers

}


/**
 * Remove null values from the given array.
 *
 * @param array $a
 *
 * @return array
 */
function fim_removeNullValues(array &$a) {
    foreach ($a AS $key => $value) {
        if (is_null($value)) unset($a[$key]);
    }

    return $a;
}


/**
 * Finds the nearest valid parental age valid to one's own age.
 *
 * @param $age
 *
 * @return mixed
 */
function fim_nearestAge($age) {
    $ages = \Fim\Config::$parentalAges;
    sort($ages);

    foreach ($ages AS $i => $a) {
        if ($a > $age) return $ages[$i - 1] ?? $ages[0];
    }

    return array_pop($ages);
}



/**
 * Renders a <select> HTML element with the given contents.
 *
 * @param string $selectName The "name" attribute for the <select>
 * @param array $selectArray An array containing the <option>s -- array keys will be used for the "value" attribute, and array values will be used for the text.
 * @param string $selectedItem The array key, if any, corresponding to the default <option> (the one with the "select" attribute).
 *
 * @return string Containing HTML of <select><option />...</select>
 */
function fimHtml_buildSelect($selectName, $selectArray, $selectedItem) {
    $code = "<select class='form-control' name=\"$selectName\">";

    foreach ($selectArray AS $key => $value) {
        $code .= "<option value=\"$key\"" . ($key == $selectedItem ? ' selected="selected"' : '') . ">$value</option>";
    }

    $code .= '</select>';

    return $code;
}
?>