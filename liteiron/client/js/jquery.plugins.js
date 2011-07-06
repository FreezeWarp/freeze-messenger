/* START PHPJS Libraries
 * See http://phpjs.org/ For More Information */

function utf8_decode (str_data) {
  // http://kevin.vanzonneveld.net
  // +   original by: Webtoolkit.info (http://www.webtoolkit.info/)
  // +    input by: Aman Gupta
  // +   improved by: Kevin van Zonneveld (http://kevin.vanzonneveld.net)
  // +   improved by: Norman "zEh" Fuchs
  // +   bugfixed by: hitwork
  // +   bugfixed by: Onno Marsman
  // +    input by: Brett Zamir (http://brett-zamir.me)
  // +   bugfixed by: Kevin van Zonneveld (http://kevin.vanzonneveld.net)
  // *   example 1: utf8_decode('Kevin van Zonneveld');
  // *   returns 1: 'Kevin van Zonneveld'
  var tmp_arr = [],
    i = 0,
    ac = 0,
    c1 = 0,
    c2 = 0,
    c3 = 0;

  str_data += '';

  while (i < str_data.length) {
    c1 = str_data.charCodeAt(i);
    if (c1 < 128) {
      tmp_arr[ac++] = String.fromCharCode(c1);
      i++;
    } else if (c1 > 191 && c1 < 224) {
      c2 = str_data.charCodeAt(i + 1);
      tmp_arr[ac++] = String.fromCharCode(((c1 & 31) << 6) | (c2 & 63));
      i += 2;
    } else {
      c2 = str_data.charCodeAt(i + 1);
      c3 = str_data.charCodeAt(i + 2);
      tmp_arr[ac++] = String.fromCharCode(((c1 & 15) << 12) | ((c2 & 63) << 6) | (c3 & 63));
      i += 3;
    }
  }

  return tmp_arr.join('');
}


function utf8_encode (argString) {
  // http://kevin.vanzonneveld.net
  // +   original by: Webtoolkit.info (http://www.webtoolkit.info/)
  // +   improved by: Kevin van Zonneveld (http://kevin.vanzonneveld.net)
  // +   improved by: sowberry
  // +  tweaked by: Jack
  // +   bugfixed by: Onno Marsman
  // +   improved by: Yves Sucaet
  // +   bugfixed by: Onno Marsman
  // +   bugfixed by: Ulrich
  // +   bugfixed by: Rafal Kukawski
  // *   example 1: utf8_encode('Kevin van Zonneveld');
  // *   returns 1: 'Kevin van Zonneveld'

  if (argString === null || typeof argString === "undefined") {
    return "";
  }

  var string = (argString + ''); // .replace(/\r\n/g, "\n").replace(/\r/g, "\n");
  var utftext = "",
    start, end, stringl = 0;

  start = end = 0;
  stringl = string.length;
  for (var n = 0; n < stringl; n++) {
    var c1 = string.charCodeAt(n);
    var enc = null;

    if (c1 < 128) {
      end++;
    } else if (c1 > 127 && c1 < 2048) {
      enc = String.fromCharCode((c1 >> 6) | 192) + String.fromCharCode((c1 & 63) | 128);
    } else {
      enc = String.fromCharCode((c1 >> 12) | 224) + String.fromCharCode(((c1 >> 6) & 63) | 128) + String.fromCharCode((c1 & 63) | 128);
    }
    if (enc !== null) {
      if (end > start) {
        utftext += string.slice(start, end);
      }
      utftext += enc;
      start = end = n + 1;
    }
  }

  if (end > start) {
    utftext += string.slice(start, stringl);
  }

  return utftext;
}


function base64_encode (data) {
  // http://kevin.vanzonneveld.net
  // +   original by: Tyler Akins (http://rumkin.com)
  // +   improved by: Bayron Guevara
  // +   improved by: Thunder.m
  // +   improved by: Kevin van Zonneveld (http://kevin.vanzonneveld.net)
  // +   bugfixed by: Pellentesque Malesuada
  // +   improved by: Kevin van Zonneveld (http://kevin.vanzonneveld.net)
  // -  depends on: utf8_encode
  // *   example 1: base64_encode('Kevin van Zonneveld');
  // *   returns 1: 'S2V2aW4gdmFuIFpvbm5ldmVsZA=='
  // mozilla has this native
  // - but breaks in 2.0.0.12!
  //if (typeof this.window['atob'] == 'function') {
  //  return atob(data);
  //}
  var b64 = "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789+/=";
  var o1, o2, o3, h1, h2, h3, h4, bits, i = 0,
    ac = 0,
    enc = "",
    tmp_arr = [];

  if (!data) {
    return data;
  }

  data = this.utf8_encode(data + '');

  do { // pack three octets into four hexets
    o1 = data.charCodeAt(i++);
    o2 = data.charCodeAt(i++);
    o3 = data.charCodeAt(i++);

    bits = o1 << 16 | o2 << 8 | o3;

    h1 = bits >> 18 & 0x3f;
    h2 = bits >> 12 & 0x3f;
    h3 = bits >> 6 & 0x3f;
    h4 = bits & 0x3f;

    // use hexets to index into b64, and append result to encoded string
    tmp_arr[ac++] = b64.charAt(h1) + b64.charAt(h2) + b64.charAt(h3) + b64.charAt(h4);
  } while (i < data.length);

  enc = tmp_arr.join('');

  switch (data.length % 3) {
  case 1:
    enc = enc.slice(0, -2) + '==';
    break;
  case 2:
    enc = enc.slice(0, -1) + '=';
    break;
  }

  return enc;
}


function base64_decode (data) {
  // http://kevin.vanzonneveld.net
  // +   original by: Tyler Akins (http://rumkin.com)
  // +   improved by: Thunder.m
  // +    input by: Aman Gupta
  // +   improved by: Kevin van Zonneveld (http://kevin.vanzonneveld.net)
  // +   bugfixed by: Onno Marsman
  // +   bugfixed by: Pellentesque Malesuada
  // +   improved by: Kevin van Zonneveld (http://kevin.vanzonneveld.net)
  // +    input by: Brett Zamir (http://brett-zamir.me)
  // +   bugfixed by: Kevin van Zonneveld (http://kevin.vanzonneveld.net)
  // -  depends on: utf8_decode
  // *   example 1: base64_decode('S2V2aW4gdmFuIFpvbm5ldmVsZA==');
  // *   returns 1: 'Kevin van Zonneveld'
  // mozilla has this native
  // - but breaks in 2.0.0.12!
  //if (typeof this.window['btoa'] == 'function') {
  //  return btoa(data);
  //}
  var b64 = "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789+/=";
  var o1, o2, o3, h1, h2, h3, h4, bits, i = 0,
    ac = 0,
    dec = "",
    tmp_arr = [];

  if (!data) {
    return data;
  }

  data += '';

  do { // unpack four hexets into three octets using index points in b64
    h1 = b64.indexOf(data.charAt(i++));
    h2 = b64.indexOf(data.charAt(i++));
    h3 = b64.indexOf(data.charAt(i++));
    h4 = b64.indexOf(data.charAt(i++));

    bits = h1 << 18 | h2 << 12 | h3 << 6 | h4;

    o1 = bits >> 16 & 0xff;
    o2 = bits >> 8 & 0xff;
    o3 = bits & 0xff;

    if (h3 == 64) {
      tmp_arr[ac++] = String.fromCharCode(o1);
    } else if (h4 == 64) {
      tmp_arr[ac++] = String.fromCharCode(o1, o2);
    } else {
      tmp_arr[ac++] = String.fromCharCode(o1, o2, o3);
    }
  } while (i < data.length);

  dec = tmp_arr.join('');
  dec = this.utf8_decode(dec);

  return dec;
}


function md5 (str) {
  // http://kevin.vanzonneveld.net
  // +   original by: Webtoolkit.info (http://www.webtoolkit.info/)
  // + namespaced by: Michael White (http://getsprink.com)
  // +  tweaked by: Jack
  // +   improved by: Kevin van Zonneveld (http://kevin.vanzonneveld.net)
  // +    input by: Brett Zamir (http://brett-zamir.me)
  // +   bugfixed by: Kevin van Zonneveld (http://kevin.vanzonneveld.net)
  // -  depends on: utf8_encode
  // *   example 1: md5('Kevin van Zonneveld');
  // *   returns 1: '6e658d4bfcb59cc13f96c14450ac40b9'
  var xl;

  var rotateLeft = function (lValue, iShiftBits) {
    return (lValue << iShiftBits) | (lValue >>> (32 - iShiftBits));
  };

  var addUnsigned = function (lX, lY) {
    var lX4, lY4, lX8, lY8, lResult;
    lX8 = (lX & 0x80000000);
    lY8 = (lY & 0x80000000);
    lX4 = (lX & 0x40000000);
    lY4 = (lY & 0x40000000);
    lResult = (lX & 0x3FFFFFFF) + (lY & 0x3FFFFFFF);
    if (lX4 & lY4) {
      return (lResult ^ 0x80000000 ^ lX8 ^ lY8);
    }
    if (lX4 | lY4) {
      if (lResult & 0x40000000) {
        return (lResult ^ 0xC0000000 ^ lX8 ^ lY8);
      } else {
        return (lResult ^ 0x40000000 ^ lX8 ^ lY8);
      }
    } else {
      return (lResult ^ lX8 ^ lY8);
    }
  };

  var _F = function (x, y, z) {
    return (x & y) | ((~x) & z);
  };
  var _G = function (x, y, z) {
    return (x & z) | (y & (~z));
  };
  var _H = function (x, y, z) {
    return (x ^ y ^ z);
  };
  var _I = function (x, y, z) {
    return (y ^ (x | (~z)));
  };

  var _FF = function (a, b, c, d, x, s, ac) {
    a = addUnsigned(a, addUnsigned(addUnsigned(_F(b, c, d), x), ac));
    return addUnsigned(rotateLeft(a, s), b);
  };

  var _GG = function (a, b, c, d, x, s, ac) {
    a = addUnsigned(a, addUnsigned(addUnsigned(_G(b, c, d), x), ac));
    return addUnsigned(rotateLeft(a, s), b);
  };

  var _HH = function (a, b, c, d, x, s, ac) {
    a = addUnsigned(a, addUnsigned(addUnsigned(_H(b, c, d), x), ac));
    return addUnsigned(rotateLeft(a, s), b);
  };

  var _II = function (a, b, c, d, x, s, ac) {
    a = addUnsigned(a, addUnsigned(addUnsigned(_I(b, c, d), x), ac));
    return addUnsigned(rotateLeft(a, s), b);
  };

  var convertToWordArray = function (str) {
    var lWordCount;
    var lMessageLength = str.length;
    var lNumberOfWords_temp1 = lMessageLength + 8;
    var lNumberOfWords_temp2 = (lNumberOfWords_temp1 - (lNumberOfWords_temp1 % 64)) / 64;
    var lNumberOfWords = (lNumberOfWords_temp2 + 1) * 16;
    var lWordArray = new Array(lNumberOfWords - 1);
    var lBytePosition = 0;
    var lByteCount = 0;
    while (lByteCount < lMessageLength) {
      lWordCount = (lByteCount - (lByteCount % 4)) / 4;
      lBytePosition = (lByteCount % 4) * 8;
      lWordArray[lWordCount] = (lWordArray[lWordCount] | (str.charCodeAt(lByteCount) << lBytePosition));
      lByteCount++;
    }
    lWordCount = (lByteCount - (lByteCount % 4)) / 4;
    lBytePosition = (lByteCount % 4) * 8;
    lWordArray[lWordCount] = lWordArray[lWordCount] | (0x80 << lBytePosition);
    lWordArray[lNumberOfWords - 2] = lMessageLength << 3;
    lWordArray[lNumberOfWords - 1] = lMessageLength >>> 29;
    return lWordArray;
  };

  var wordToHex = function (lValue) {
    var wordToHexValue = "",
      wordToHexValue_temp = "",
      lByte, lCount;
    for (lCount = 0; lCount <= 3; lCount++) {
      lByte = (lValue >>> (lCount * 8)) & 255;
      wordToHexValue_temp = "0" + lByte.toString(16);
      wordToHexValue = wordToHexValue + wordToHexValue_temp.substr(wordToHexValue_temp.length - 2, 2);
    }
    return wordToHexValue;
  };

  var x = [],
    k, AA, BB, CC, DD, a, b, c, d, S11 = 7,
    S12 = 12,
    S13 = 17,
    S14 = 22,
    S21 = 5,
    S22 = 9,
    S23 = 14,
    S24 = 20,
    S31 = 4,
    S32 = 11,
    S33 = 16,
    S34 = 23,
    S41 = 6,
    S42 = 10,
    S43 = 15,
    S44 = 21;

  str = this.utf8_encode(str);
  x = convertToWordArray(str);
  a = 0x67452301;
  b = 0xEFCDAB89;
  c = 0x98BADCFE;
  d = 0x10325476;

  xl = x.length;
  for (k = 0; k < xl; k += 16) {
    AA = a;
    BB = b;
    CC = c;
    DD = d;
    a = _FF(a, b, c, d, x[k + 0], S11, 0xD76AA478);
    d = _FF(d, a, b, c, x[k + 1], S12, 0xE8C7B756);
    c = _FF(c, d, a, b, x[k + 2], S13, 0x242070DB);
    b = _FF(b, c, d, a, x[k + 3], S14, 0xC1BDCEEE);
    a = _FF(a, b, c, d, x[k + 4], S11, 0xF57C0FAF);
    d = _FF(d, a, b, c, x[k + 5], S12, 0x4787C62A);
    c = _FF(c, d, a, b, x[k + 6], S13, 0xA8304613);
    b = _FF(b, c, d, a, x[k + 7], S14, 0xFD469501);
    a = _FF(a, b, c, d, x[k + 8], S11, 0x698098D8);
    d = _FF(d, a, b, c, x[k + 9], S12, 0x8B44F7AF);
    c = _FF(c, d, a, b, x[k + 10], S13, 0xFFFF5BB1);
    b = _FF(b, c, d, a, x[k + 11], S14, 0x895CD7BE);
    a = _FF(a, b, c, d, x[k + 12], S11, 0x6B901122);
    d = _FF(d, a, b, c, x[k + 13], S12, 0xFD987193);
    c = _FF(c, d, a, b, x[k + 14], S13, 0xA679438E);
    b = _FF(b, c, d, a, x[k + 15], S14, 0x49B40821);
    a = _GG(a, b, c, d, x[k + 1], S21, 0xF61E2562);
    d = _GG(d, a, b, c, x[k + 6], S22, 0xC040B340);
    c = _GG(c, d, a, b, x[k + 11], S23, 0x265E5A51);
    b = _GG(b, c, d, a, x[k + 0], S24, 0xE9B6C7AA);
    a = _GG(a, b, c, d, x[k + 5], S21, 0xD62F105D);
    d = _GG(d, a, b, c, x[k + 10], S22, 0x2441453);
    c = _GG(c, d, a, b, x[k + 15], S23, 0xD8A1E681);
    b = _GG(b, c, d, a, x[k + 4], S24, 0xE7D3FBC8);
    a = _GG(a, b, c, d, x[k + 9], S21, 0x21E1CDE6);
    d = _GG(d, a, b, c, x[k + 14], S22, 0xC33707D6);
    c = _GG(c, d, a, b, x[k + 3], S23, 0xF4D50D87);
    b = _GG(b, c, d, a, x[k + 8], S24, 0x455A14ED);
    a = _GG(a, b, c, d, x[k + 13], S21, 0xA9E3E905);
    d = _GG(d, a, b, c, x[k + 2], S22, 0xFCEFA3F8);
    c = _GG(c, d, a, b, x[k + 7], S23, 0x676F02D9);
    b = _GG(b, c, d, a, x[k + 12], S24, 0x8D2A4C8A);
    a = _HH(a, b, c, d, x[k + 5], S31, 0xFFFA3942);
    d = _HH(d, a, b, c, x[k + 8], S32, 0x8771F681);
    c = _HH(c, d, a, b, x[k + 11], S33, 0x6D9D6122);
    b = _HH(b, c, d, a, x[k + 14], S34, 0xFDE5380C);
    a = _HH(a, b, c, d, x[k + 1], S31, 0xA4BEEA44);
    d = _HH(d, a, b, c, x[k + 4], S32, 0x4BDECFA9);
    c = _HH(c, d, a, b, x[k + 7], S33, 0xF6BB4B60);
    b = _HH(b, c, d, a, x[k + 10], S34, 0xBEBFBC70);
    a = _HH(a, b, c, d, x[k + 13], S31, 0x289B7EC6);
    d = _HH(d, a, b, c, x[k + 0], S32, 0xEAA127FA);
    c = _HH(c, d, a, b, x[k + 3], S33, 0xD4EF3085);
    b = _HH(b, c, d, a, x[k + 6], S34, 0x4881D05);
    a = _HH(a, b, c, d, x[k + 9], S31, 0xD9D4D039);
    d = _HH(d, a, b, c, x[k + 12], S32, 0xE6DB99E5);
    c = _HH(c, d, a, b, x[k + 15], S33, 0x1FA27CF8);
    b = _HH(b, c, d, a, x[k + 2], S34, 0xC4AC5665);
    a = _II(a, b, c, d, x[k + 0], S41, 0xF4292244);
    d = _II(d, a, b, c, x[k + 7], S42, 0x432AFF97);
    c = _II(c, d, a, b, x[k + 14], S43, 0xAB9423A7);
    b = _II(b, c, d, a, x[k + 5], S44, 0xFC93A039);
    a = _II(a, b, c, d, x[k + 12], S41, 0x655B59C3);
    d = _II(d, a, b, c, x[k + 3], S42, 0x8F0CCC92);
    c = _II(c, d, a, b, x[k + 10], S43, 0xFFEFF47D);
    b = _II(b, c, d, a, x[k + 1], S44, 0x85845DD1);
    a = _II(a, b, c, d, x[k + 8], S41, 0x6FA87E4F);
    d = _II(d, a, b, c, x[k + 15], S42, 0xFE2CE6E0);
    c = _II(c, d, a, b, x[k + 6], S43, 0xA3014314);
    b = _II(b, c, d, a, x[k + 13], S44, 0x4E0811A1);
    a = _II(a, b, c, d, x[k + 4], S41, 0xF7537E82);
    d = _II(d, a, b, c, x[k + 11], S42, 0xBD3AF235);
    c = _II(c, d, a, b, x[k + 2], S43, 0x2AD7D2BB);
    b = _II(b, c, d, a, x[k + 9], S44, 0xEB86D391);
    a = addUnsigned(a, AA);
    b = addUnsigned(b, BB);
    c = addUnsigned(c, CC);
    d = addUnsigned(d, DD);
  }

  var temp = wordToHex(a) + wordToHex(b) + wordToHex(c) + wordToHex(d);

  return temp.toLowerCase();
}

/* End PHPJS Libraries */










/* START jQuery Cookie Function */

/**
 * Create a cookie with the given name and value and other optional parameters.
 *
 * @example $.cookie('the_cookie', 'the_value');
 * @desc Set the value of a cookie.
 * @example $.cookie('the_cookie', 'the_value', { expires: 7, path: '/', domain: 'jquery.com', secure: true });
 * @desc Create a cookie with all available options.
 * @example $.cookie('the_cookie', 'the_value');
 * @desc Create a session cookie.
 * @example $.cookie('the_cookie', null);
 * @desc Delete a cookie by passing null as value. Keep in mind that you have to use the same path and domain
 *       used when the cookie was set.
 *
 * @param String name The name of the cookie.
 * @param String value The value of the cookie.
 * @param Object options An object literal containing key/value pairs to provide optional cookie attributes.
 * @option Number|Date expires Either an integer specifying the expiration date from now on in days or a Date object.
 *                             If a negative value is specified (e.g. a date in the past), the cookie will be deleted.
 *                             If set to null or omitted, the cookie will be a session cookie and will not be retained
 *                             when the the browser exits.
 * @option String path The value of the path atribute of the cookie (default: path of page that created the cookie).
 * @option String domain The value of the domain attribute of the cookie (default: domain of page that created the cookie).
 * @option Boolean secure If true, the secure attribute of the cookie will be set and the cookie transmission will
 *                        require a secure protocol (like HTTPS).
 * @type undefined
 *
 * @name $.cookie
 * @cat Plugins/Cookie
 * @author Klaus Hartl/klaus.hartl@stilbuero.de
 */

/**
 * Get the value of a cookie with the given name.
 *
 * @example $.cookie('the_cookie');
 * @desc Get the value of a cookie.
 *
 * @param String name The name of the cookie.
 * @return The value of the cookie.
 * @type String
 *
 * @name $.cookie
 * @cat Plugins/Cookie
 * @author Klaus Hartl/klaus.hartl@stilbuero.de
 */
jQuery.cookie = function(name, value, options) {
  if (typeof value != 'undefined') { // name and value given, set cookie
    options = options || {};
    if (value === null) {
      value = '';
      options.expires = -1;
    }
    var expires = '';
    if (options.expires && (typeof options.expires == 'number' || options.expires.toUTCString)) {
      var date;
      if (typeof options.expires == 'number') {
        date = new Date();
        date.setTime(date.getTime() + (options.expires * 24 * 60 * 60 * 1000));
      } else {
        date = options.expires;
      }
      expires = '; expires=' + date.toUTCString(); // use expires attribute, max-age is not supported by IE
    }
    // CAUTION: Needed to parenthesize options.path and options.domain
    // in the following expressions, otherwise they evaluate to undefined
    // in the packed version for some reason...
    var path = options.path ? '; path=' + (options.path) : '';
    var domain = options.domain ? '; domain=' + (options.domain) : '';
    var secure = options.secure ? '; secure' : '';
    document.cookie = [name, '=', encodeURIComponent(value), expires, path, domain, secure].join('');
  } else { // only name given, get cookie
    var cookieValue = null;
    if (document.cookie && document.cookie != '') {
      var cookies = document.cookie.split(';');
      for (var i = 0; i < cookies.length; i++) {
        var cookie = jQuery.trim(cookies[i]);
        // Does this cookie string begin with the name we want?
        if (cookie.substring(0, name.length + 1) == (name + '=')) {
          cookieValue = decodeURIComponent(cookie.substring(name.length + 1));
          break;
        }
      }
    }
    return cookieValue;
  }
};

/* END jQuery Cookie Functon */









/* START jQuery Non-Obfusicating Alert Box (Generic Implementation) */

/**
 * jGrowl 1.2.5
 *
 * Dual licensed under the MIT (http://www.opensource.org/licenses/mit-license.php)
 * and GPL (http://www.opensource.org/licenses/gpl-license.php) licenses.
 *
 * Written by Stan Lemon <stosh1985@gmail.com>
 * Last updated: 2009.12.15
 *
 * jGrowl is a jQuery plugin implementing unobtrusive userland notifications.  These
 * notifications function similarly to the Growl Framework available for
 * Mac OS X (http://growl.info).
 *
 * To Do:
 * - Move library settings to containers and allow them to be changed per container
 */

/* EZPZ Tooltip v1.0 Modified June 23rd, 2011
 * Joseph T. Parsons
 *
 * Eliminate code unneeded for FIM. */
(function($) {
  /** jGrowl Wrapper - Establish a base jGrowl Container for compatibility with older releases. **/
  $.jGrowl = function(m, o) {
    // To maintain compatibility with older version that only supported one instance we'll create the base container.
    if ($('#jGrowl').size() == 0)
      $('<div id="jGrowl"></div>').addClass((o && o.position) ? o.position : $.jGrowl.defaults.position).appendTo('body');

    // Create a notification on the container.
    $('#jGrowl').jGrowl(m,o);
  };


  /** Raise jGrowl Notification on a jGrowl Container **/
  $.fn.jGrowl = function(m, o) {
    if ($.isFunction(this.each)) {
      var args = arguments;

      return this.each(function() {
        var self = this;

        /** Create a jGrowl Instance on the Container if it does not exist **/
        if ($(this).data('jGrowl.instance') == undefined) {
          $(this).data('jGrowl.instance', $.extend(new $.fn.jGrowl(), { notifications: [], element: null, interval: null }));
          $(this).data('jGrowl.instance').startup(this);
        }

        /** Optionally call jGrowl instance methods, or just raise a normal notification **/
        if ($.isFunction($(this).data('jGrowl.instance')[m])) {
          $(this).data('jGrowl.instance')[m].apply($(this).data('jGrowl.instance'), $.makeArray(args).slice(1));
        } else {
          $(this).data('jGrowl.instance').create(m, o);
        }
      });
    };
  };

  $.extend($.fn.jGrowl.prototype, {

    /** Default JGrowl Settings **/
    defaults: {
      pool:       0,
      header:     '',
      group:      '',
      sticky:     false,
      position:         'top-right',
      glue:       'after',
      theme:      'default',
      themeState:     'highlight',
      corners:    '10px',
      check:      250,
      life:       3000,
      closeDuration:  'normal',
      openDuration:   'normal',
      easing:     'swing',
      closer:     true,
      closeTemplate: '&times;',
      closerTemplate: '<div>[ close all ]</div>',
      log:        function(e,m,o) {},
      beforeOpen:     function(e,m,o) {},
      afterOpen:        function(e,m,o) {},
      open:       function(e,m,o) {},
      beforeClose:    function(e,m,o) {},
      close:      function(e,m,o) {},
      animateOpen:    {
        opacity:  'show'
      },
      animateClose:   {
        opacity:  'hide'
      }
    },

    notifications: [],

    /** jGrowl Container Node **/
    element:  null,

    /** Interval Function **/
    interval:   null,

    /** Create a Notification **/
    create:   function(message, o) {
      var o = $.extend({}, this.defaults, o);

      this.notifications.push({ message: message, options: o });

      o.log.apply(this.element, [this.element,message,o]);
    },

    render:     function(notification) {
      var self = this;
      var message = notification.message;
      var o = notification.options;

      var notification = $(
        '<div class="jGrowl-notification ' + o.themeState + ' ui-corner-all' +
        ((o.group != undefined && o.group != '') ? ' ' + o.group : '') + '">' +
        '<div class="jGrowl-close">' + o.closeTemplate + '</div>' +
        '<div class="jGrowl-header">' + o.header + '</div>' +
        '<div class="jGrowl-message">' + message + '</div></div>'
      ).data("jGrowl", o).addClass(o.theme).children('div.jGrowl-close').bind("click.jGrowl", function() {
        $(this).parent().trigger('jGrowl.close');
      }).parent();


      /** Notification Actions **/
      $(notification).bind("mouseover.jGrowl", function() {
        $('div.jGrowl-notification', self.element).data("jGrowl.pause", true);
      }).bind("mouseout.jGrowl", function() {
        $('div.jGrowl-notification', self.element).data("jGrowl.pause", false);
      }).bind('jGrowl.beforeOpen', function() {
        if (o.beforeOpen.apply(notification, [notification,message,o,self.element]) != false) {
          $(this).trigger('jGrowl.open');
        }
      }).bind('jGrowl.open', function() {
        if (o.open.apply(notification, [notification,message,o,self.element]) != false) {
          if (o.glue == 'after') {
            $('div.jGrowl-notification:last', self.element).after(notification);
          } else {
            $('div.jGrowl-notification:first', self.element).before(notification);
          }

          $(this).animate(o.animateOpen, o.openDuration, o.easing, function() {
            // Fixes some anti-aliasing issues with IE filters.
            if ($.browser.msie && (parseInt($(this).css('opacity'), 10) === 1 || parseInt($(this).css('opacity'), 10) === 0))
              this.style.removeAttribute('filter');

            $(this).data("jGrowl").created = new Date();

            $(this).trigger('jGrowl.afterOpen');
          });
        }
      }).bind('jGrowl.afterOpen', function() {
        o.afterOpen.apply(notification, [notification,message,o,self.element]);
      }).bind('jGrowl.beforeClose', function() {
        if (o.beforeClose.apply(notification, [notification,message,o,self.element]) != false)
          $(this).trigger('jGrowl.close');
      }).bind('jGrowl.close', function() {
        // Pause the notification, lest during the course of animation another close event gets called.
        $(this).data('jGrowl.pause', true);
        $(this).animate(o.animateClose, o.closeDuration, o.easing, function() {
          $(this).remove();
          var close = o.close.apply(notification, [notification,message,o,self.element]);

          if ($.isFunction(close))
            close.apply(notification, [notification,message,o,self.element]);
        });
      }).trigger('jGrowl.beforeOpen');

      /** Optional Corners Plugin **/
      if (o.corners != '' && $.fn.corner != undefined) $(notification).corner(o.corners);

      /** Add a Global Closer if more than one notification exists **/
      if ($('div.jGrowl-notification:parent', self.element).size() > 1 &&
         $('div.jGrowl-closer', self.element).size() == 0 && this.defaults.closer != false) {
        $(this.defaults.closerTemplate).addClass('jGrowl-closer ui-state-highlight ui-corner-all').addClass(this.defaults.theme)
          .appendTo(self.element).animate(this.defaults.animateOpen, this.defaults.speed, this.defaults.easing)
          .bind("click.jGrowl", function() {
            $(this).siblings().trigger("jGrowl.beforeClose");

            if ($.isFunction(self.defaults.closer)) {
              self.defaults.closer.apply($(this).parent()[0], [$(this).parent()[0]]);
            }
          });
      };
    },

    /** Update the jGrowl Container, removing old jGrowl notifications **/
    update:  function() {
      $(this.element).find('div.jGrowl-notification:parent').each(function() {
        if ($(this).data("jGrowl") != undefined && $(this).data("jGrowl").created != undefined &&
           ($(this).data("jGrowl").created.getTime() + parseInt($(this).data("jGrowl").life))  < (new Date()).getTime() &&
           $(this).data("jGrowl").sticky != true &&
           ($(this).data("jGrowl.pause") == undefined || $(this).data("jGrowl.pause") != true)) {

          // Pause the notification, lest during the course of animation another close event gets called.
          $(this).trigger('jGrowl.beforeClose');
        }
      });

      if (this.notifications.length > 0 &&
         (this.defaults.pool == 0 || $(this.element).find('div.jGrowl-notification:parent').size() < this.defaults.pool))
        this.render(this.notifications.shift());

      if ($(this.element).find('div.jGrowl-notification:parent').size() < 2) {
        $(this.element).find('div.jGrowl-closer').animate(this.defaults.animateClose, this.defaults.speed, this.defaults.easing, function() {
          $(this).remove();
        });
      }
    },

    /** Setup the jGrowl Notification Container **/
    startup:  function(e) {
      this.element = $(e).addClass('jGrowl').append('<div class="jGrowl-notification"></div>');
      this.interval = setInterval(function() {
        $(e).data('jGrowl.instance').update();
      }, parseInt(this.defaults.check));

      if ($.browser.msie && parseInt($.browser.version) < 7 && !window["XMLHttpRequest"]) {
        $(this.element).addClass('ie6');
      }
    },

    /** Shutdown jGrowl, removing it and clearing the interval **/
    shutdown:   function() {
      $(this.element).removeClass('jGrowl').find('div.jGrowl-notification').remove();
      clearInterval(this.interval);
    },

    close:  function() {
      $(this.element).find('div.jGrowl-notification').each(function(){
        $(this).trigger('jGrowl.beforeClose');
      });
    }
  });

  /** Reference the Defaults Object for compatibility with older versions of jGrowl **/
  $.jGrowl.defaults = $.fn.jGrowl.prototype.defaults;

})(jQuery);

/* END jQuery Non-Obfusicating Alert Box (Generic Implementation) */










/* START Beepy */

/*
 * RIFFWAVE.js v0.02 - Audio encoder for HTML5 <audio> elements.
 * Copyright (C) 2011 Pedro Ladaria <pedro.ladaria at Gmail dot com>
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * version 2 as published by the Free Software Foundation.
 * The full license is available at http://www.gnu.org/licenses/gpl.html
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 *
 * Changelog:
 *
 * 0.01 - First release
 * 0.02 - New faster base64 encoding
 *
 */

var riffwave = {
  base64 : {
    chars: "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789+/=",
    encLookup: [],

    init: function() {
      for (var i=0; i<4096; i++) {
        this.encLookup[i] = this.chars[i >> 6] + this.chars[i & 0x3F];
      }
    },

    encode: function(src) {
      var len = src.length;
      var dst = '';
      var i = 0;
      while (len > 2) {
        n = (src[i] << 16) | (src[i+1]<<8) | src[i+2];
        dst+= this.encLookup[n >> 12] + this.encLookup[n & 0xFFF];
        len-= 3;
        i+= 3;
      }

      if (len > 0) {
        var n1= (src[i] & 0xFC) >> 2;
        var n2= (src[i] & 0x03) << 4;
        if (len > 1) n2 |= (src[++i] & 0xF0) >> 4;
        dst+= this.chars[n1];
        dst+= this.chars[n2];
        if (len == 2) {
          var n3= (src[i++] & 0x0F) << 2;
          n3 |= (src[i] & 0xC0) >> 6;
          dst+= this.chars[n3];
        }
        if (len == 1) dst+= '=';
        dst+= '=';
      }
      return dst;
    } // end Encode
  },

  create : function(data) {
    this.data = [];        // Byte array containing audio samples
    this.wav = [];         // Array containing the generated wave file
    this.dataURI = '';     // http://en.wikipedia.org/wiki/Data_URI_scheme

    this.header = {                         // OFFS SIZE NOTES
      chunkId      : [0x52,0x49,0x46,0x46], // 0    4    "RIFF" = 0x52494646
      chunkSize    : 0,                     // 4    4    36+SubChunk2Size = 4+(8+SubChunk1Size)+(8+SubChunk2Size)
      format       : [0x57,0x41,0x56,0x45], // 8    4    "WAVE" = 0x57415645
      subChunk1Id  : [0x66,0x6d,0x74,0x20], // 12   4    "fmt " = 0x666d7420
      subChunk1Size: 16,                    // 16   4    16 for PCM
      audioFormat  : 1,                     // 20   2    PCM = 1
      numChannels  : 1,                     // 22   2    Mono = 1, Stereo = 2, etc.
      sampleRate   : 8000,                  // 24   4    8000, 44100, etc
      byteRate     : 0,                     // 28   4    SampleRate*NumChannels*BitsPerSample/8
      blockAlign   : 0,                     // 32   2    NumChannels*BitsPerSample/8
      bitsPerSample: 8,                     // 34   2    8 bits = 8, 16 bits = 16, etc...
      subChunk2Id  : [0x64,0x61,0x74,0x61], // 36   4    "data" = 0x64617461
      subChunk2Size: 0                      // 40   4    data size = NumSamples*NumChannels*BitsPerSample/8
    };

    function u32ToArray(i) { return [i&0xFF, (i>>8)&0xFF, (i>>16)&0xFF, (i>>24)&0xFF]; }

    function u16ToArray(i) { return [i&0xFF, (i>>8)&0xFF]; }

    this.Make = function(data) {
      if (data instanceof Array) this.data = data;
      this.header.byteRate = (this.header.sampleRate * this.header.numChannels * this.header.bitsPerSample) >> 3;
      this.header.blockAlign = (this.header.numChannels * this.header.bitsPerSample) >> 3;
      this.header.subChunk2Size = this.data.length;
      this.header.chunkSize = 36 + this.header.subChunk2Size;

      this.wav = this.header.chunkId.concat(
        u32ToArray(this.header.chunkSize),
        this.header.format,
        this.header.subChunk1Id,
        u32ToArray(this.header.subChunk1Size),
        u16ToArray(this.header.audioFormat),
        u16ToArray(this.header.numChannels),
        u32ToArray(this.header.sampleRate),
        u32ToArray(this.header.byteRate),
        u16ToArray(this.header.blockAlign),
        u16ToArray(this.header.bitsPerSample),
        this.header.subChunk2Id,
        u32ToArray(this.header.subChunk2Size),
        this.data
      );
      this.dataURI = 'data:audio/wav;base64,' + riffwave.base64.encode(this.wav);
    };

    if (data instanceof Array) {
      this.Make(data);
    }
  },

  play : function() {
    try {
      var effect = [];

      for (var i = 0; i < 5000; i++) {
        effect[i] = 64 + Math.round(32 * (Math.cos(i * i / 2000) + Math.sin(i * i / 4000)));
      }

      riffwave.base64.init();
      var wave3 = new riffwave.create();

      wave3.header.sampleRate = 22000;
      wave3.Make(effect);

      var audio = new Audio(wave3.dataURI);
      audio.volume = (window.volume ? window.volume : .5);
      audio.play();

      console.log('Audio Trigger Played');

      return true;
    }
    catch(err) {
      console.log('Audio Trigger Failed:' + err);

      return false;
    }
  }
};