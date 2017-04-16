<?php
/* FreezeMessenger Copyright © 2014 Joseph Todd Parsons

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
 **************** Encoding & Encryption ******************
 *********************************************************/


/**
 * Decrypts data.
 *
 * @param mixed $content - The content to decrypt.
 * @param int $keyNum - The key index used during encryption.
 * @param int $iv - The IV used during encryption.
 *
 * @global array $salts - Key-value pairs used for encryption.
 * @return mixed
 * @author Joseph Todd Parsons <josephtparsons@gmail.com>
 */
function fim_decrypt($content, $keyNum, $iv) {
    global $salts, $config;

    if ($keyNum && $iv) { // Make sure both the salt and the IV are non-false.
        $key = str_pad($salts[$keyNum], 256, "."); // Get the proper salt.

        if (is_array($content)) {
            foreach ((array) $content AS &$contentEntry) { // Run through each element in the content.
                $contentEntry = openssl_decrypt( // Decrypt the data.
                    $contentEntry,
                    'AES-256-CTR',
                    $key, // Use the salt we found above.
                    OPENSSL_RAW_DATA | OPENSSL_ZERO_PADDING,
                    $iv
                );
            }
        }
        else {
            $content = openssl_decrypt( // Decrypt the data.
                $content,
                'AES-256-CTR',
                $key, // Use the salt we found above.
                OPENSSL_RAW_DATA | OPENSSL_ZERO_PADDING,
                $iv
            );
        }
    }

    return $content; // Return the original array with the specified indexes unencrypted.
}


/**
 * Encrypts a string or all values in an array. Data is encrypted using 3DES and CBC.
 * The returned data will contain the encrypted $data value (as an array with key->value pairs left intact, or as a string, depending on how it was passed), the base64_encoded IV, and the number of the salt used for encryption (the corrosponding salt needed for unencrypted should be stored in config.php).
 *
 * @param mixed $data - The data to encrypt.
 * @global array $salts - Key-value pairs used for encryption.
 * @return array - list($data, $iv, $saltNum)
 * @author Joseph Todd Parsons <josephtparsons@gmail.com>
 */
define('FIM_ENCRYPT_MESSAGETEXT', 1);
define('FIM_ENCRYPT_FILECONTENT', 2);

function fim_encrypt($data, $type = FIM_ENCRYPT_MESSAGETEXT) {
    global $salts, $encrypt, $encryptUploads;

    $cipher = "AES-256-CTR";

    if ($type === FIM_ENCRYPT_MESSAGETEXT && !$encrypt
        || $type === FIM_ENCRYPT_FILECONTENT && !$encryptUploads
        || !function_exists('openssl_encrypt')
        || !in_array($cipher, openssl_get_cipher_methods()))
        return [$data, '', -1];


    $key = str_pad(end($salts), 256, "."); // Move the file pointer to the last entry in the array (and return its value)
    $keyNum = key($salts); // Get the key/id of the corrosponding salt.

    $iv_size = openssl_cipher_iv_length($cipher); // Get the length of the IV for the method used
    $iv = random_bytes($iv_size);

    if (is_array($data)) { // If $data is an array, we will encrypt each value, and retain the key->value structure.
        $newData = array(); // Create the array, since we will be adding key=>value pairs to it briefly.

        foreach ($data AS $key => $value) { // Run through the data array...
            $newData[$key] = openssl_encrypt( // Encrypt the $value.
                $value, // The value we're encrypting.
                $cipher, // This one is pretttty strong.
                $key, // We use our config-file stored salts as a key.
                OPENSSL_RAW_DATA | OPENSSL_ZERO_PADDING, // No padding, please,
                $iv // We need to use the raw IV, so we decode the earlier encoded value.
            );
        }
    }
    else {
        $newData = openssl_encrypt(
            $data, $cipher, $key, OPENSSL_RAW_DATA | OPENSSL_ZERO_PADDING, $iv
        );
    }

    return array($newData, $iv, $keyNum); // Return the data.
}





/********************************************************
 ************************ START **************************
 *********************** Wrappers ************************
 *********************************************************/



/**
 * Generates a SHA256 using whatever methods are available. If no valid function can be found, the data will be returned unhashed.
 *
 * @param string $data - The data to encrypt.
 * @return string - Encrypted data.
 * @author Joseph Todd Parsons <josephtparsons@gmail.com>
 */
function fim_sha256($data) {
    global $config;

    if (function_exists('hash') && in_array('sha256', hash_algos())) return hash('sha256', $data); // hash() is available in PHP 5.1.2+, or in PECL Hash 1.1. Algorithms vary, so we must make sure sha256 is one of them.
    elseif (function_exists('mhash') && defined('MHASH_SHA256')) return mhash(MHASH_SHA256, $data); // mhash() is available in pretty much all versions of PHP, but the SHA256 algo may not be available.
    else { // Otherwise, we use a third-party SHA256 library. Expect slowness. [TODO: Test]
        require('functions/sha256.php'); // Require SHA256 class provided by NanoLink.ca.

        $obj = new nanoSha2();
        $shaStr = $obj->hash($data);
    }
}


/**
 * A wrapper for rand and mt_rand, using whichever is available.
 *
 * @param string $data - The data to encrypt.
 * @return string - Encrypted data.
 * @author Joseph Todd Parsons <josephtparsons@gmail.com>
 */
function fim_rand($min, $max) {
    if (function_exists('mt_rand')) return mt_rand($min, $max); // Proper hardware-based rand, actually works.
    elseif (function_exists('rand')) return rand($min, $max); // Standard rand, not well seeded.
    else return $min; // Though it should never happened, applications should still /run/ if no rand function exists. Keep this in mind when using fim_rand.
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
function fim_inArray($needle, $haystack, $all = false) {
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
    return floor((time() - $date) / (60 * 60 * 24 * 365)); // Generate an age by taking a unix timestamp and subtracting the timestamp of the user's DOB. Divide to create years.
}


/**
 * Pretty Size
 *
 * @param float $size
 * @return string
 * @author Joseph Todd Parsons <josephtparsons@gmail.com>
 */
function fim_formatSize($size) {
    global $config;

    $suffix = 0;

    while ($size > $config['fileIncrementSize']) { // Increase the Byte Prefix, Decrease the Number (1024B = 1KiB)
        $suffix++;
        $size /= $config['fileIncrementSize'];
    }

    return round($size, 2) . $config['fileSuffixes'][$suffix];
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
    global $config;


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
                    case 'POST':   $newData[$indexName] = 'edit';   break;
                    case 'PUT':    $newData[$indexName] = 'create'; break;
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
                if (!in_array($metaName, array('default', 'require', 'trim', 'evaltrue', 'valid', 'min', 'max', 'filter', 'cast', 'transform', 'bitTable', 'flipTable', 'removeDuplicates')))
                    throw new Exception('Unrecognised metadata: ' . $metaName);

                elseif (($metaName === 'require' || $metaName === 'trim' || $metaName === 'evaltrue')
                    && !is_bool($metaData))
                    throw new Exception('Invalid "' . $metaName . '" in data in fim_sanitizeGPC');

                elseif ($metaName === 'valid' &&
                    !is_array($metaData))
                    throw new Exception('Defined valid values do not correspond to recognized data type (array).');

                elseif (($metaName === 'min' || $metaName === 'max')
                    && !is_numeric($metaData))
                    throw new Exception('Invalid "' . $metaName . '" in data in fim_sanitizeGPC');

                elseif ($metaName === 'filter'
                    && !in_array($metaData, array('', 'int', 'bool', 'string', 'roomId')))
                    throw new Exception('Invalid "filter" in data in fim_sanitizeGPC');

                elseif ($metaName === 'cast'
                    && !in_array($metaData, array('int', 'bool', 'string', 'json', 'list', 'dict', 'ascii128', 'alphanum', 'bitfieldEquation', 'roomId')))
                    throw new Exception("Invalid 'cast' (value = $metaData) in data in fim_sanitizeGPC.");

                elseif ($metaName === 'cast'
                    && $metaData === 'bitfieldEquation'
                    && (!isset($indexData['flipTable']) || !is_array($indexData['flipTable'])))
                    throw new Exception("'bitfieldEquation' cast missing corresponding flipTable parameter in fim_sanitizeGPC.");

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



            /* Set to Default and Perform Validation */
            if (!(isset($indexMetaData['cast']) && $indexMetaData['cast'] === 'bitfieldEquation')) { // bitfieldEquation isn't normally set, so our default-setting is irrelevant for it
                // If the global is provided, check to see if it's valid. If not, throw error.
                if (isset($activeGlobal[$indexName], $indexMetaData['valid'])) {
                    if (isset($indexMetaData['cast']) && $indexMetaData['cast'] === 'list') {
                        if (count(array_diff($activeGlobal[$indexName], $indexMetaData['valid'])) > 0)
                            throw new Exception("Invalid value(s) for '$indexName': " . implode(', ', array_diff($activeGlobal[$indexName], $indexMetaData['valid'])));
                    }

                    elseif (isset($indexMetaData['cast']) && $indexMetaData['cast'] === 'dict')
                        throw new Exception("A 'valid' parameter was specified for '$indexName', but the 'dict' cast type does not support this parameter.");

                    elseif (!in_array($activeGlobal[$indexName], $indexMetaData['valid']))
                        throw new Exception("Invalid value for '$indexName': {$activeGlobal[$indexName]}");
                }

                // If the global is _not_ provided, then use the default if available.
                if (!isset($activeGlobal[$indexName]) &&
                    isset($indexMetaData['default'])) {
                    $activeGlobal[$indexName] = $indexMetaData['default'];
                }

                // Finally, if the global is thus-far unprovided...
                if (!isset($activeGlobal[$indexName])) {
                    if ($indexMetaData['require']) throw new Exception('Required data not present (index ' . $indexName . ').'); // And required, throw an exception.
                    else continue; // And not required, just ignore this global and move on to the next one.
                }
            }



            /* Trim */
            if ($indexMetaData['trim'] === true) $activeGlobal[$indexName] = trim($activeGlobal[$indexName]); // Trim white space.



            /* Casting */
            switch($indexMetaData['cast']) {
            /**
             * Treat as JSON. Still working out the kinks here.
             */
            case 'json':
                $newData[$indexName] = json_decode(
                    $activeGlobal[$indexName],
                    true,
                    $config['jsonDecodeRecursionLimit'],
                    JSON_BIGINT_AS_STRING
                );

                /* Newer Code -- Breaks Conventions Because I'm Not Sure Which Conventions I Want Yet */
                $holder = array();
                foreach ($newData[$indexName] AS $key => $value) {
                    $holder[fim_cast($indexMetaData['filterKey'] ? $indexMetaData['filterKey'] : 'string', $key)] = fim_cast($indexMetaData['filter'] ? $indexMetaData['filter'] : 'string', $key);
                }
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
                if (!is_array($activeGlobal[$indexName])) {
                    throw new Exception("Bad API data: '$indexName' must be array.");
                }

                $arrayFromGlobal = $activeGlobal[$indexName];

                // Apply filters, evaltrue, and valid -- these will cast the datatype, remove falsey entries, and remove entries not on the valid list respectively.
                $newData[$indexName] = fim_arrayValidate(
                    $arrayFromGlobal,
                    ($indexMetaData['filter'] ? $indexMetaData['filter'] : 'string'),
                    ($indexMetaData['evaltrue'] ? false : true),
                    (count($indexMetaData['valid']) ? $indexMetaData['valid'] : false)
                );
            break;


            /*
             * Treat as a list (one-dimensional array).
             * We apply all kinds of filters here, only including truthy values if evaltrue is set, applying casts to the list's contents if filter is set, and tranforming the entire list into a new datatype if transform is set.
             *
             */
            case 'list':
                // Make sure the passed element is an array -- we don't do any conversion to make it an array.
                if (!is_array($activeGlobal[$indexName])) {
                    throw new Exception("Bad API data: '$indexName' must be array.");
                }

                // Remove any array keys.
                $arrayFromGlobal = array_values(
                    $activeGlobal[$indexName]
                );

                // Apply filters, evaltrue, and valid -- these will cast the datatype, remove falsey entries, and remove entries not on the valid list respectively.
                $newData[$indexName] = fim_arrayValidate(
                    $arrayFromGlobal,
                    ($indexMetaData['filter'] ? $indexMetaData['filter'] : 'string'),
                    ($indexMetaData['evaltrue'] ? false : true),
                    (count($indexMetaData['valid']) ? $indexMetaData['valid'] : false)
                );

                // Remove duplicate values from the list if required
                if (isset($indexMetaData['removeDuplicates']) && $indexMetaData['removeDuplicates']) {
                    $newData[$indexName] = array_unique($newData[$indexName]);
                }

                // Transform the list into a single, non-list datatype
                if (isset($indexMetaData['transform'])) {
                    switch ($indexMetaData['transform']) {
                    case 'bitfield':
                        $bitfield = 0;

                        foreach ($newData[$indexName] AS $name) {
                            if (!isset($indexMetaData['bitTable'][$name])) throw new Exception("Bad API data: '$name' is not a recognized value for field '$indexName'");
                            else $bitfield &= $indexMetaData['bitTable'][$name];
                        }

                        $newData[$indexName] = $bitfield;
                    break;

                    case 'csv':
                        $newData[$indexName] = implode(',', $newData[$indexName]);
                    break;

                    default:
                        throw new Exception("Bad transform.");
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
                $newData[$indexName] = fim_cast(
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
                $newData[$indexName] = preg_replace('/[^a-zA-Z0-9]*/', '', str_replace(array_keys($config['romanisation']), array_values($config['romanisation']), $activeGlobal[$indexName]));
            break;


            /* This is a funky one that really helps when dealing with bitfields.
             * First of all, it uniquely has the "flipTable" parameter, which is an array of [bit => name]s.
             * A name is a string pointing to another entry in the activeGlobal, while a bit is the bitvalue that name represents.
             * If the activeGlobal doesn't have the name, we just ignore it. If it does have it, and it evaluates to true, then our string will contain an equation turning that bit on. If it evaluates to false, our equation will try and turn that bit off.
             * This equation can then be used by $database->equation.
             */
            case 'bitfieldEquation':
                global $database;
                $equation = '$' . $indexName;

                foreach ($indexMetaData['flipTable'] AS $bit => $name) {
                    if (!isset($activeGlobal[$name]))
                        continue;

                    elseif ($activeGlobal[$name])
                        $equation .= (' | ' . $bit);

                    else
                        $equation .= (' & ~' . $bit);
                }

                $newData[$indexName] = $equation;
            break;


            /*
             * Basically, cast it to an integer if otherwise looks like one (e.g. the string "123"), keep it as-is if it's a private room ID (e.g. the string "p1,4,90"), or set it to null.
             */
            case 'roomId':
                $newData[$indexName] = fim_cast('roomId', $activeGlobal[$indexName]);
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
            throw new Exception('Required data not present, unset by cast (index ' . $indexName . ').'); // And required, throw an exception.
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

    case 'roomId':
        if (ctype_digit($value))
            $value = (int) $value;
        elseif (!fimRoom::isPrivateRoomId($value))
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

            $preValue = fim_cast($type, $value, false);

            if ($preValue || $preserveAll) $arrayValidated[$key] = $preValue; // Only keep falsey values if preserveAll is true
        }
    }

    return $arrayValidated; // Return the validated array.
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
 *
 * @author Joseph Todd Parsons <josephtparsons@gmail.com>
 */

function fim_exceptionHandler($exception) {
    global $config;

    ob_end_clean(); // Clean the output buffer and end it. This means that when we show the error in a second, there won't be anything else with it.
    header('HTTP/1.1 500 Internal Server Error'); // When an exception is encountered, we throw an error to tell the server that the software effectively is broken.

    $errorData = array(
        'string' => $exception->getMessage(),
        'contactEmail' => $config['email'],
    );

    if ($config['displayBacktrace']) {
        $errorData['file'] = $exception->getFile();
        $errorData['line'] = $exception->getLine();
        $errorData['trace'] = $exception->getTrace();
    }

    echo new apiData(array(
        'exception' => $errorData,
    ));
}



/**
 * Flushes The Output Buffer
 */
function fim_flush() {
    echo str_repeat(' ', 4 * 1024); // TODO: Config

    if (ob_get_level()) ob_flush(); // Flush output buffer if enabled.
    flush();

}


function fim_removeNullValues(array &$a) {
    foreach ($a AS $key => $value) {
        if (is_null($value)) unset($a[$key]);
    }
}
?>