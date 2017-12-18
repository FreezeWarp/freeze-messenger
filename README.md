FreezeMessenger
====================
Copyright © 2017 Joseph T. Parsons <josephtparsons@gmail.com>

Licensed under the GPLv3 (see LICENSE)

![FreezeMessenger, Development Screenshot](https://raw.githubusercontent.com/FreezeWarp/freeze-messenger/master/screenshot1.png "FreezeMessenger, Development Screenshot")

Headline Functionality
----------------------
  * Event streams provide functionality for getting new messages, being informed of new messages in watched rooms, tracking which users are currently typing a message (and when users go offline), and more. Plus, most of this functionality works if event streams are disabled.
  * Kafka, Redis, and Postgres can be used for the lowest possible event stream latency.
  * Users may, if allowed, create their own rooms and open direct messages with other users. Rooms can permissioned to only allow specific users and specific usergroups.
  * Rooms can be age- and content- restricted. Messages can also be censored on a per-room basis.
  * Files are easily uploaded, and thumbnails are generated for inline message display. In the browser frontend, images can even be pasted from the clipboard. Importantly, the administrator has full control over which files can be uploaded and how much space any user can use.
  * Integration with PHPBB and vBulletin is supported, as is single-sign on with Google, Twitter, Facebook, Steam, and Reddit.
  * In addition to fallback disk caching, APC, Memcached, and Redis can all be used for blazing fast cache performance, used to optimise the performance for checking usage rights, flood detection, and more.
  * Compatible with MySQL, PostgreSQL, and SQL Server databases. Supports fulltext message searching with all three.

Requirements
---------------------
### Minimum
  * PHP 5.6 (PHP 7 is required in the nightlies; beginning with Release Candidate 1, a transpiler will be used to support PHP 5.6 in releases)
  * MySQL, PostgreSQL, **or** SQL Server
  * PHP's [MBString](http://php.net/manual/en/book.mbstring.php) and [DOM](http://php.net/manual/en/book.dom.php) libraries
  
### Recommended
  * APC, APCu, Memcached, or Redis for optimal caching.
  * Redis or Kafka, for optimal message streaming. (If using SQL Server, this is especially helpful.)

  
Installation
---------------------
1.   To upload, use FTP or another means of uploading the files here.
2.   To install, navigate to the directory install/ and proceed from there.
