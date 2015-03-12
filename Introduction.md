# About FreezeMessenger #

The FreezeMessenger Instant Messenger Software (“FIM” or “FreezeMessenger” as it's typically referred to as) was created to resolve the great number of issues with traditional online redistributable networking clients: a heavy server footprint, the inability to properly moderate, the inn ability to easily extent with other interfaces (even those not on the web at all), a lack of proper data encryption, and more. In doing so it has risen above its author’s expectations of stability, speed, and general feature-set. During its original incarnation the great many features sported today were never planned, yet in a mere matter of months came to shine.

A full history lesson may prove too boring, as may the many spelling and math lessons that one must learn to be able to reproduce the feat contained within. Instead, what matters: FreezeMessenger is capable of running on any vBulletin Forum with PHP 5.3 or higher and MySQL. It will work in nearly any browser (counting the Lite layout, supported browsers include Internet Explorer 6+, Mozilla Firefox 3+, Opera 9+, Konqueror 4.6, Google Chrome, and Apple Safari), on any operating system (Linux, Macintosh, Windows, Haiku, FreeBSD, OpenBSD, and so-on), on every modern computer. Its advanced API enabled any interested developer to create brilliant frontends, with some having created terminal frontends, and others having created good-ol-fashioned MSN/AIM-like frontends. The API even exposes technology not yet utilized in the main Web Interface, such as the ability to show a user’s status.

FreezeMessenger’s other technology feats are numerous: among them its advanced censor support (though to be honest the developer is not overly happy at this specific accomplishment), full database encryption, room creation and advanced permissioning, database file uploads, and even more. The software is licensed entirely under the General Public License Version 3, and full sourcecode can be obtained by contacting Joseph T. Parsons via rehtaew@gmail.com (a later redistributable version will be made available, but for several reasons will not be released until Summer 2011).

A brief Version History and Browser Support Notes can be found below. Other links are also given at the bottom to separate sections of this documentation. I truly hope you find the software as impressive as I do, and if you find a bug or make a change, I would love to incorporate it in the original code. Please, should you be so kind, email me at rehtaew@gmail.com and I will give full credit to your contribution.

## Key Features ##
  * Easy Integration with vBulletin 3+4, PHPBB, and Other Systems
  * Highly Scalable, Easily Running with At Least 1,000,000 Messages
  * Extensive Plugin Support
    * Add Additional Login Backends
    * Add New APIs and Modify the Existing Ones
    * Easily Add and Configure Third-Party Interfaces and Clients
  * Advanced API
    * Support for Both XML & JSON Data Structures
  * Powerful jQueryUI-based Interface Included
    * AJAX-Driven
    * Works with IE8, Firefox 4, Safari 5, Google Chrome/Chromium, and Opera 11
  * Alternative LiteTheIronMode Interface Included¹
    * Light-Weight
    * Intergrates the Themes of vBulletin Forums
    * Works with Nearly All Browsers, Including Mobile

> ¹ Not yet included in the latest beta release.

## Server Requirements ##
  * PHP 5.2+
    * MySQL PHP Extensions¹
    * MCrypt PHP Extension¹
    * Hash PHP Extension¹
    * Multibyte String PHP Extension¹
    * Date/Time Extension¹
    * PCRE Extension¹
    * SimpleXML Extension¹
    * APC Extension²
    * MySQLi Extension¹
  * MySQL³
  * 200MB+ RAM (this may vary; larger implementations may benefit from increasing the level of message caching from the default 100 messages, costing RAM but saving time and CPU)
  * Apache and LigHTTPD both tested; IIS should also work.

¹ Should be provided in the standard PHP 5.2 binary, unless it was compiled differently.

² Should be provided in the standard PHP 5.4 binary, unless it was compiled differently.

³ In the future, several alternative databases will be possible as well.