**Draft - This is a very early draft of the API docs. There are likely _numerous_ glitches both in the documentation itself and in the API. If you feel brave enough to try it now, report issues at http://code.google.com/p/freeze-messenger/issues/list using the ` Component-API` flag.**

# Introduction #

The FIM Application Programming Interface, or API, is designed for easy Client-to-Server communication with the FreezeMessenger Server Backend. It is not, nor should it be confused with, a proper Instant Messenger Protocol, which exchanges information through a network in its own unique transport. In reality, the FIM API is merely a pipe through the Hypertext Transfer Protocol, or HTTP (and if supported by the server HTTPS), and should for the most part be treated as such.

In general, all communications made through the HTTP will attempt to conform as well as possible to the RESTful standards, however there are considerable short-comings that make it largely impossible to achieve this (it will be worked on more in future versions, as well). For instance, the PUT and DELETE methods are not used, due to lackluster browser support that was a must, and instead POST will be the sole method for all destructive queries, and GET for all nondestructive queries. A full list of similar considerations can be found at the bottom of this document.

The overwhelming goal of the API is to provide as comprehensive as possible access to all backend features of FreezeMessenger, ranging from getting messages to uploading files. It is not perfect, will likely change time-to-time in the future, and unfortunately may not be able to do everything one would in an ideal world want. However, server backends are able to implement comprehensives plugins into the API to extend its functionality to a much greater degree. Outside of this, by default no support exists for performing administrative tasks in the API; these will instead need to be implemented by first- and third-party interfaces.

The choice of JSON as the default format for the API comes from its human readability, easy parsing, and support for complex data structures. However, the entire API is also available in the XML format (and in theory in other third-party created formats) that may be easier to use on certain platorms. It is, however, less tested and may have slightly more bugs as a result of different poorly placed assumptions on the part of the API developers.

The XML and JSON APIs are not full of tricks, but know that the XML variation conforms solely to the XML 1.0 specification with the text/xml content-type. Both APIs solely rely on Unicode (UTF-8), and will express formatted data using (to the greatest extent possible) in XHTML 5-compliant code.

Finally, the entirety of this documentation is official and offered by the API’s developers, but at this time is still in early work in progress (as the API itself is). It is subject to change at any time, and as such it is recommended that XML data is parsed such that the order of sister nodes and parameters do not matter (as they shouldn’t in any standards parser), that unknown nodes and parameters are allowed, and that in the case of a missing node the client application is able to ignore it whenever reasonable.

The author retains all copyrights of the document text, diagrams, and related content. This documentation may not be reproduced, transmitted, or sold without express written consent of the author at the present time. Write to josephtparsons [at](at.md) gmail.com.

## Reading the Documentation ##

Each command, starting with “Establishing a Login”, lists its script location in parenthesis after the title, its directives in a separate heading entitled “Directives”, and its data tree under “Structured Data Tree”. Examples are generally not given.

All requests must be made using a valid login. In future versions of the API (starting with version 4), all requests made without a login will automatically fail. As of now, requests should only be made without a valid session hash if the user is anonymous (though they will still not be granted any POST priviledges).

## Understanding the API ##

The API is HTTP-driven and uses a fairly conventional means of transferring data. In Javascript, the API can be accessed easily with AJAX, while in PHP cURL will usually do the trick. Still, understand that certain things are not necessarily conventional, but should be consistent.

Additionally, all data will need to be properly encoded before being sent. This means that data will need to be sent using `urlencoding`. The following symbols must be escaped in all transferred data (others will usually be decoded as well):
| + | %2b |
|:--|:----|
| & | %26 |
| % | %25 |
| `space` | %20 |
| `newline` | %0a |

The following content-types are acceptable:
  * `application/x-www-form-urlencoded`
  * `multipart/form-data`

Alternatively, one can transmit data using the POST or GET content body (a payload), in which case data must be URL encoded like above (or by escaping + and & to \+ and \&). This is still experimental, but is at least partially supported.

Encryption is not currently supported, though HTTPS should work; however, it is up to the user to know if a server supports HTTPS or not.

Finally, standard caveats do apply. Notably, all directives are case sensitive, arrays must be sent in JSON format (as noted below), and so-on.

## Standard Directives ##

The following standard directives can be used in some or all of the pages, and may be required:

  * Session Hash (`fim3_sessionHash`) - Obtained in “Establishing a Login”, this is a temporary string which corresponds to a particular login. It MUST be specified for all requests made by a client.
  * User ID (`fim3_userId`) - The corrosponding user for the session hash. It is required for a valid session.
  * Format (`fim3_format`) - The API format to return the structured data in. “json” is default but subject to change, while several other options do exist:
    1. xml - Requests will be output using the Extensible Markup Language.
    1. xml2 - Requests will be output using the Extensible Markup Language. Elements that contain only key-ordered pairs will be output in a single XML element via attributes.
    1. json - Requests will be output using the Javascript Object Notation. This means a smaller transfer.
    1. jsonp - Data is returned using the json format above, wrapped with the function "fim3\_jsonp.parse()". This allows for cross-origin JSONP requests.
    1. keys - (DO NOT USE) Outputs the XML keys as an HTML-formatted list. Used for documentation.
    1. phparray - (DO NOT USE) Outputs the XML data via print\_r.

## Getting a Session Token ##
To get a session token, one must send a request to the validate.php API (see below).

## Considerations ##
  * The API version is stored in the header FIM-API-VERSION of every request. The current value is "3b4dev"; future versions will follow the pattern "3.1a1dev", "3.1a1", "3.1b1dev", "3.1b1", "3.1rc1dev", "3.1rc2", "3.1".
  * File upload via the PUT method should be possible before the final release of FIM3.

# Provided API Wrappers #
Currently, a jQuery API wrapper is available. It significantly reduces the time required to use the API if jQuery is used.

It's code is part of WebPro and will be released separately shortly.