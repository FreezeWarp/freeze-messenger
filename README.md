FreezeMessenger
====================
Copyright © 2017 Joseph T. Parsons <josephtparsons@gmail.com>

Licensed under the GPLv3 (see LICENSE)

![FreezeMessenger, Development Screenshot](https://raw.githubusercontent.com/FreezeWarp/freeze-messenger/master/artifacts/screenshot1.png "FreezeMessenger, WebPro, Development Screenshot")
_(A screenshot of the WebPro frontend.)_

![FreezeMessenger, Development Screenshot](https://raw.githubusercontent.com/FreezeWarp/freeze-messenger/master/artifacts/admin_screenshot.png "FreezeMessenger, Admin Control Panel, Development Screenshot")
_(A screenshot of the admin control panel.)_

Headline Functionality
======================

-   Flexible frontends: all core functionality is implemented through a REST-like API, meaning anyone can write their own FreezeMessenger frontends. FreezeMessenger currently comes with two frontends: the in-browser "WebPro", and the desktop Java client.
-   Event streams provide functionality for getting new messages, being informed of new messages in watched rooms, tracking which users are currently typing a message (and when users go offline), and more. Plus, most of this functionality works if event streams are disabled.
-   Redis and Postgres can be used for the lowest possible event stream latency.
-   Users may, if allowed, create their own rooms and open direct messages with other users.
-   Rooms can permissioned to only allow specific users and specific usergroups.
-   Rooms can be age- and content- restricted. Messages can also be censored on a per-room basis.
-   Files are easily uploaded, and thumbnails are generated for inline message display. In the browser frontend, images can even be pasted from the clipboard. Importantly, the administrator has full control over which files can be uploaded and how much space any user can use.
-   Flood controls limit how many rooms a user can create, how many files a user can upload, how many messages a user can post in a given period, and even how many API calls of different types a user is allowed.
-   Integration with PHPBB and vBulletin is supported, as is single-sign on with Google, Twitter, Facebook, Steam, and Reddit.
-   In addition to fallback disk caching, APC, Memcached, and Redis can all be used for blazing fast cache performance, used to optimise the performance for checking usage rights, flood detection, and more.
-   Compatible with MySQL, PostgreSQL, and SQL Server databases. Supports fulltext message searching with all three.

Requirements
============
### Minimum
  * PHP 5.6 (PHP 7 is required in the current betas; beginning with Release Candidate 1, a transpiler will be used to support PHP 5.6 in releases)
  * MySQL, PostgreSQL, **or** SQL Server
  * PHP's [DOM](http://php.net/manual/en/book.dom.php) library
  
### Recommended
  * APC, APCu, Memcached, or Redis for optimal caching.
  * Redis or Kafka, for optimal message streaming. (If using SQL Server, this is especially helpful.)
  * Apache is currently the best supported server, though Nginx and Windows should also work. MySQL and PostgreSQL are both performant, but SQL Server is still generally on the slow side.

  
Installation
============

### From Releases
1.   [Download the latest release.](https://github.com/FreezeWarp/freeze-messenger/releases/tag/v1.0-beta.1)
2.   Unzip the latest release into `[messengerDirectory]`, which is whatever directory FreezeMessenger should be accessible from.
3.   To install, navigate to the `https://yoursite.com/[messengerDirectory]/install/` and proceed from there.

### From Git
1.   Clone the Git repository: `git clone https://github.com/FreezeWarp/freeze-messenger.git [messengerDirectory]`, where `[messengerDirectory]` is where you want FreezeMessenger to be accessible from.
2.   Initiate the Git submodules: run `git submodule init` followed by `git submodule update` from inside `[messengerDirectory]`
3.   To install, navigate to the directory `https://yoursite.com/[messengerDirectory]/install/` and proceed from there.


Configuration
=============

FreezeMessenger configuration is located in two places. The config.php file (located in the root FreezeMessenger directory) is used to define how FreezeMessenger interfaces with other software. This is the file that sets up the login servers, cache servers, and messaging servers that FreezeMessenger can use.

The Configuration Editor, located in the WebPro Admin Control Panel (and stored in the database), defines how FreezeMessenger itself works. You can use it to fine tune permissions, flood settings, defaults, and so-on.

config.php
----------

If you are only running a small chat server, you will not need to edit config.php. Once your server starts growing, however, you may wish to look at it, and change the following settings to suit your needs:

-   __Master/Replication Database Settings__: If desired, you can set a different set of login information for a replication database server. FreezeMessenger will use the replication server whenever it is retrieving information that can be time delayed. It will only ever write to the master.

-   __Cache Servers__: By default, the FreezeMessenger Installer will automatically enable disk caching, as well as either APC or APCu caching if your server supports them. However, Memcached and Redis are also supported, and you can enable them by editing config.php.

-   __Stream Servers__: By default, FreezeMessenger will use the PgSQL streaming method (see below) if Postgres is used for its database, but you can manually enable the PgSQL streaming method by editing config.php. Likewise, the Redis and Kafka streaming methods can be enabled by editing config.php.

-   __Login Servers__: You will have an opportunity to configure all supported login servers when you first install FreezeMessenger, but you can also update and add Login Server API keys by editing config.php.

Admin Control Panel Configuration Editor
--------------------
[See below.](#configuration-editor)


Common Installation Problems
============================

### It takes a very long time to receive messages.
This is most commonly a problem with message streaming (enabled by default), and has three origins:

1. Certain hosts (like GoDaddy) seem to buffer output, meaning that the server waits to tell clients about new messages until it has received several messages (or after about a minute). We are still looking into the exact cause of this issue, but you can work around it by setting [`$outputFlushPaddingKilobytes`](http://josephtparsons.com/messenger/docs/classes/classes/Fim.Config.html#property_outputFlushPaddingKilobytes) to a high number (around 1,000) in the [admin control panel](#configuration-editor).

2. Secondly, message streaming, while very fast, is not efficient on most hosts. By default, often only around 50 users will be able to connect to the server at once; in some cases, this number can be even lower. We are still looking into ways of addressing this issue; you can try setting [`$serverSentEvents`](http://josephtparsons.com/messenger/docs/classes/classes/Fim.Config.html#property_serverSentEvents) to false in the [admin control panel](#configuration-editor), which will disable streaming entirely.

3. Thirdly, like above, it is possible to run out of memory to start new scripts if many connections currently exist. In gen
eral, a typical FreezeMessenger API request will require about 1 megabyte of memory; a long-lasting streaming connection may require 2-3 megabytes. In general, you should have around 5 megabytes times the number of maximum concurrent users of memory available available to FreezeMessenger. (This doesn't include overhead introduced by Apache, Nginx, FastCGI, etc.)

### The list of currently active users appears inaccurate.

On MySQL systems, the [`ping`](http://josephtparsons.com/messenger/docs/database.htm#ping) table is stored in memory. On default systems, only about 500,000 rows can exist in this table; after this, new records will simply be dropped, and the list of active users could, in theory, stop updating normally. To address this situation, increase the MySQL [max_heap_table_size](https://dev.mysql.com/doc/refman/5.7/en/server-system-variables.html#sysvar_max_heap_table_size) system variable.

### Getting the list of active users is very slow.

On MySQL systems, the ping is stored in memory. While very fast for smaller installations, on larger installations memory tables can become very slow. Changing the ping table to a non-memory table is then advised.

Note that, if streaming is enabled, most user status updates will occur through the streaming system. If you have streaming disabled, you may wish to consider enabling it as well.

### Sending and retrieving messages suddenly became quite slow.

Like with the ping table above, the [`roomPermissionsCache`](http://josephtparsons.com/messenger/docs/database.htm#roomPermissionsCache) table on MySQL installations is stored in memory.

While unlikely, the first issue that may happen is that too many rows are in this table for new entries to be added; this will happen after around 1,000,000 rows. Permission calls will instead perform a much slower full-lookup, which can adversely affect the performance of almost all actions. To address this situation, increase the MySQL [max_heap_table_size](https://dev.mysql.com/doc/refman/5.7/en/server-system-variables.html#sysvar_max_heap_table_size) system variable.

More common is that too many queries are being made to this table at once; due to MySQL limitations, memory tables are not performant on very large installations. In this situation, you can install any supported non-disk cache (APC, Memcached, or Redis) to alleviate this issue, or change the [`roomPermissionsCache`](http://josephtparsons.com/messenger/docs/database.htm#roomPermissionsCache) table to a non-memory table.

### I am unable to login when a lot of other users are logged in.

Like with the ping and roomPermissionsCache tables, the [`oauth_access_tokens`](http://josephtparsons.com/messenger/docs/database.htm#oauth_access_tokens) table is stored in memory on MySQL installations. When a user tries to log in, the table will first be pruned of expired sessions, but in theory so many users may be active at once that no new rows can be added to the table.

Around 18,000 rows can be stored in this table on a default MySQL installation; this can be increased by changing the [`oauth_access_tokens`](http://josephtparsons.com/messenger/docs/database.htm#oauth_access_tokens) table to a non-memory table, or by increasing the MySQL [max_heap_table_size](https://dev.mysql.com/doc/refman/5.7/en/server-system-variables.html#sysvar_max_heap_table_size) system variable.

The Validation Bottleneck
-------------------------

By default, at startup every script invocation performs the following:

1.  Look up the server configuration information. This information will be stored in any available cache, but if one is not available it will query the configuration table for every script execution. Many DBMS software will automatically cache the result, but it is typically advised not to alter many configuration directives if a non-disk cache is not available.

2.  Check to see if a session token is valid by looking it up in the session table. This table will preferentially be stored in memory, and thus could encounter write bottlenecks if validation attempts are not limited.

3.  Insert or increment a record of the user's activity in the flood table or cache. This table exists in memory by default, and because writes to it are constant (and memory tables typically prevent simultaneous writes), it may become a bottleneck. Changing it to a non-memory table is often advised on large systems. By default, to spread the writes, the table will be partitioned. Additionally, if available, any available non-disk cache may be used to keep track of the counter instead, obviating write concerns entirely if the cache is in memory, though causing far more activity on the cache servers.

In general, most scripts should not be queried by clients frequently, and the above start-up will be fine. However, obtaining messages may be performed quite frequently, especially by clients that do not support interfacing with the event API using server-sent events, and thus it is advised to keep the above in mind. Moreover, if server-sent events are disabled (possibly because your server is unable to run a script for at least 30 seconds), then the above becomes much more of a concern.


Administration Tools
====================

The WebPro interface comes bundled with a handful of administration tools. They are detailed here.

View Logs
---------

View logs allows you to view the short- and full- moderation logs, as well as the access log and query log, if they are enabled.

Modify Censor
-------------

Modify censor allows you to create censor lists and censor words. These censor lists are typically either enabled or disabled by default, and rooms (if allowed) can individually opt-in to and opt-out of censor lists.

Modify Emoticons
----------------

Modify emoticons allows you to add, edit, and delete emoticons -- that is, short strings of text that are replaced by images in supported frontends. The tool is not available if emoticons are provided by another source, such as a forum integration login.

Administrator Permissions
-------------------------

Within administrator permissions, you can alter what permissions different administrators have, and add new administrators. Briefly, the permissions are as follows:

-   __Grant Permissions__ - Whether the user can give other users administrator privileges.
-   __Protected__ - Whether the user is protected from permission revocation; when enabled, only "super-administrators" (those defined in config.php) and the user themself may remove their permissions.
-   __Administer Rooms__ - The user will be a moderator in all rooms, site-wide, with the exception of private and off-the-record rooms. If they can administrator users, they will also be able to kick users in rooms.
-   __Administer Users__ - The user will be able to ban users site-wide and, if they have administrator rooms privileges, will be able to kick users in rooms.
-   __Administer Files__ - The user will be able to delete files, and review files flagged for moderation.
-   __Administer Emoticons__ - The user will be able to delete, edit, and add new emoticons. This functionality is automatically disabled for all administrators if emoticons are copied from an installation system.

User Sessions
-------------

User sessions displays the currently active (unexpired) user sessions.

Configuration Editor
--------------------

The base configuration editor can be used to modify a large number of variables within FreezeMessenger; documentation for these variables can be found within [the documentation for \Fim\Config.php](http://josephtparsons.com/messenger/docs/classes/classes/Fim.Config.html).

Tools
-----

### Update Database Schema

When you run the update database schema tool, FreezeMessenger will attempt to update the database schema to correspond with that detailed in install/dbSchema.xml. This is the same process that occurs at installation time, though it less flexible -- certain dbSchema.xml changes will cause the tool to error out, and others may result in the loss of data. As such, a full database backup should first be made.

This tool can be used to change memory tables to non-memory tables (or vice-versa), change the maximum size of columns, and so-on. Simply edit install/dbSchema.xml and then run the tool.

### View Cache

The view cache tool will show you all cache entries FreezeMessenger is aware of. This may include cache entries belonging to other applications, and is not guaranteed to show all cache contents; in most cases, dedicated cache management tools for Redis, Memcached, etc. are preferrable.

### Clear Cache

The clear cache tool will attempt to clear all cache entries used by any caching system FreezeMessenger is connected to. As a result, it may clear the cache of other applications running alongside FreezeMessenger, and should be used with caution.

PHPInfo
-------

PHPInfo displays the system information. It is identical to calling phpinfo() in the PHP interpreter.


Room Permission Considerations
==============================

Allowing granular room permissions is one of FlexChat's primary goals, but such granularity does bring its own limitations.

Complexity of Lookup
--------------------

First, because room permissions can be affected by a number of factors (whether a user is in a banned or administrative usergroup, for instance), it is impossible to store the enumeration of all user permissions across all rooms in the database. In turn, this makes it difficult to only fetch rooms that a given user has permission to access. Instead, when fetching rooms, every room is retrieved, and the user's permissioning is then checked. If a user only has permission to access 10 rooms in a site composed of thousands, every time the user tries to enumerate rooms, they will be scanning almost the entire rooms table, and further scanning the room permissioning table once for each room. (The actual logic is to fetch rooms in blocks of 50, filtering out disallowed rooms and then returning once at least one allowed room is found.)

This is simplified by the room permission cache, which allows for almost instantaneous checking of whether a user is allowed in a room, but because we can't rely on the cache containing a room-user permission pair, it is not able to be used to perform the initial room query. Further, the flood system tracks the number of queries needed before a room resultset can be returned to the client; clients will still be limited in the resources they can use. At the same time, this means users that can access only a small number of rooms may not be able to enumerate their room list at all.

Unclear Permissioning
---------------------

Second, because permissions can be affected by a number of factors, it is not always clear whether a user should have permission to access a room or not.

1.  First, administrators with the modRooms privilege are always allowed access.

2.  Next, banned users (those part of a banned usergroup) are always denied access.

3.  Next, users who are part of the allowed groups allowed in a room, or part of the allowed users in a room, are granted access. (Specifically, a user will have all permissions any of the groups they belong to have.)

4.  Finally, the user's permissions in the room will be any permissions that both the user has by default and the room has by default. (Thus, if a room is set to allow users to view and post, but a user only has default viewing permission, they will only be allowed to view. However, if a room explicitly grants that user permission, this will be overridden.)

Note that, at an API level, FreezeMessenger supports effectively arbitrary permissioning of any user or group. The default "WebPro" frontend only exposes which users and which groups are allowed to post, which users are moderators, and what the default permissioning of the room is.

Private Rooms
-------------

FlexMessenger's concept of a private room is a group message between 2 and 10 users, where the room itself is named by its constituent users; for instance, a room between users with IDs 1, 7, and 10 is named "p1,7,10". The rooms never have an explicit entry in the database; their properties are calculated by the room object itself (for instance, the name of such a room is "Private Chat between {user1.name}, {user7.name}, and {user10.name}". Such room properties generally can't change, and users cannot be added to a private room (the message history of "p1,7,10" will only ever be visible to those users) or removed from them.

To give users control over private rooms, they have three relevant settings:

1.  __Privacy Level__ - This controls what other users may initiate a private conversation with the user.

    1.  If set to "block all users", the user disables private messages entirely, and will never be able to join a private room.
    2.  If set to "allow all users", the user allows private messages from everybody except other users in the user's ignore list.
    3.  If set to "only friended users", the user disables private messages from everybody except other users in the user's friends list.

2.  __Ignore List__ - A list of users who are not allowed to message the user regardless of their privacy level.

3.  __Friends List__ - A list of users who, if the user has set their privacy level to "only friended users", are the only users allowed to initiate conversion with the user.

Because a user's privacy level, ignore list, and friends list may change at any time, a private room that at one time was allowed to exist may no longer. As a consequence, private rooms are considered to be in one of two states at any time:

1.  __Read Only__ - Whenever any user in a private room is disallowing private communication from any other user in that private room, the room is considered read only. Old messages may still be read by any room participant, but new ones may not be sent.

2.  __Normal__ - Whenever all users in a private room allow communication from all other users, the room behaves normally, allowing messages to be sent and received.

At no time are old messages blocked from any room member; while it may make sense for a user who initiated a privacy restriction to be unable to view previous communications, it arguably makes more sense for them to still be able to view those messages without having to change their privacy controls first.

There is, additionally, a third state that private rooms may enter if private rooms are disabled at a site-wide level:

3.  __Disabled__ - Whenever the private room subsystem is disabled, all private rooms are considered invalid, and all participants are neither allowed to receive nor send messages. In the future, such rooms may be considered "read only" instead, but right now they are disabled entirely. Likewise, if the maximum number of allowed users in a private room changes, any rooms with more than that number of users will be considered disabled.


Messaging & Event Systems
=========================

FreezeMessenger has two sets of events -- one for notifications towards users, and one for notifications towards rooms (including new messages). Clients can use special APIs to see events for the logged in user or for a certain room.

The currently supported events are:

1.  User Events
    1.  Missed Message (including if a another user sends a private message)

2.  Room Events
    1.  New Message
    2.  Edited Message
    3.  Deleted Message
    4.  Topic Change
    5.  User Status Change (including typing)

There are four systems FreezeMessenger can use for publishing and receiving events:

Simple Tables
-------------

By default, events are stored in database tables, with tables automatically created when a publisher/subscribe event occurs, and automatically deleted after a period of inactivity. The event system queries these tables frequently (typically around once a second), checking for new entries. To keep the table small and performant, only around 100 entries are kept in the table at once; when a new entry is created, entries with an ID 100 lower than itself are deleted.

Obviously, these are not optimal; some databases, however, may choose to keep such small tables in memory, ensuring a degree of speed and preventing excessive table locking. Others, like MySQL, are explicitly told to store such tables in memory. As a result, this will still be reasonably performant in most cases.

PostgreSQL Listen/Notify
------------------------

PostgreSQL's listen/notify functionality is used to communicate messages on channels dedicated to each unique event stream (e.g. the event stream for room 1). This method is automatically used if Postgres is the primary database driver, but it can also be enabled separately.

Note that this method still uses database tables for its initial query. Subsequently, disk writes will still occur with some frequency.

Redis Pub/Sub
-------------

If the [Redis](https://pecl.php.net/package/redis) plugin is available, its publisher/subscriber model can be used. This method is generally faster than the alternatives (as it uses sockets to communicate), though it will typically only be available to those on dedicated hosting.

Like PostgreSQL Listen/Notify, this will use database tables for its initial query. However, unlike PostgreSQL Listen/Notify, it is theoretically possible to miss messages between when the database is queried and when the Redis socket is opened. This makes Redis a somewhat poor choice in environments where the stream.php script is unable to stay open for less than a few minutes.

Kafka
-----

Finally, the [rdkafka](https://pecl.php.net/package/rdkafka) plugin can be installed and used to connect to an Apache Kafka server. However, partitioning is not currently well-implemented, and this method is considered experimental.

Adding Your Own Methods
-----------------------

FlexMessenger uses reflection to expose stream methods. New Stream methods can be placed in the functions/Stream directory, and then named in config.php, like other methods are.


Login Compatibility
===================

Supported Login Functionality
-----------------------------

FreezeMessenger can "borrow" functionality from login servers, though the functionality is currently fairly limited. It is implemented as such:

-   __authentication__ - Will always be provided by the login server.
-   __userName__ - Will always be provided by the login server, even if only an email address. If a login server does not expose a userId, the userName will be used to make future queries to the login server. (If it does expose a userId, userName may be updated to reflect changes in the login server.)
-   __userName formatting__ - May occasionally be provided by the login server. The login connector may be able to interpret this value from, e.g., group membership. Such functionality is not otherwise implemented by FreezeMessenger (i.e. it can be shown, but it can not be changed directly).
-   __email__ - May or may not be provided by the login server. If available, may be used to list user contact information and provide email updates to users (though such functionality is unlikely to be implemented anytime soon).
-   __userGroups__ - The names of userGroups a user belongs to may be provided by a login server. While FreezeMessenger has plans to support its own userGroups, they will not be implemented until a future release. (Thus, userGroups cannot currently be created through FreezeMessenger under any circumstances, nor can userGroup membership change through FreezeMessenger. However, rooms can be permissioned by usergroups imported through login systems.)
-   __avatar and/or profile__ - If available from login server, FreezeMessenger functionality will be disabled entirely (i.e. will only support reads). If not available from login server, will both be readable and writable by FreezeMessenger.
-   __banned status__ - In rare cases, a login method may indicate that a user should be considered banned.

In addition, the following site-wide features may be implemented using login system-provided data:

-   __emoticons__ - The list of emoticons that are used in messages.

Supported Login Methods
-----------------------

### Primary Login Methods
Note that, at present, the following primary login systems are available:

-   __PHPBB 3__, which provides username formatting, email, usergroups, and avatar. It also provides emoticons.
-   __vBulletin 3/4__, which provides username formatting, email, usergroups, and avatar. It also provides emoticons. Additionally, if a user's primary usergroup is a banned usergroup, they will be marked as banned.
-   __vBulletin 5__, which provides username formatting, email, usergroups, and avatar. It also provides emoticons. Additionally, if a user's primary usergroup is a banned usergroup, they will be marked as banned.

(If no primary login system is used, FreezeMessenger will handle authentication itself using usernames and passwords, and will provide for setting user avatars and profiles. No other login server functionality is currently implemented by FreezeMessenger.)

### OAuth/OpenID Login Methods
Additionally, the following OAuth-style login systems can be used in addition to the primary login system, if API keys are available:

-   __Google__, which providers usernames, emails, and avatars.
-   __Twitter__, which provides usernames and avatars.
-   __Facebook__, which provides usernames and avatars.
-   __Microsoft__, which providers usernames and avatars.
-   __Reddit__, which provides usernames and usergroups (as the list of subreddits subscribed to by users)
-   __Steam__, which provides usernames, avatars, and usergroups (as the list of games Steam users play).

### Adding New Login Systems

FlexMessenger uses reflection to load its login methods. New primary and secondary login systems can be added by uploading them to the Login/Database and Login/TwoStep directories respectively. Thus, if you have a database with login information, you can write your own login provider and upload it to the Login/Database folder, and then update $loginConfig['method'] in config.php accordingly. Similarly, if you have an OAuth provider you would like to add support for, you can upload new methods to Login/TwoStep.


Overload (Flood) Protection
===========================

As with most software, FreezeMessenger grows slower the more people there are using it. An effort has been made to ensure that it performs well for both small and large deployments, but this only counts for so much if malicious actors attempt to slow your installation.

To this end, FreezeMessenger deploys a number of protection techniques:

-   First, we try to limit the number of accounts a single individual may register by limiting the number of accounts created by a single IP address.
-   We also place limits on the number of rooms that can be created by a single user, and allow for a fixed number of additional rooms to be created per year of the account age.
-   We implement file flood detection by limiting both the number of files users can upload and the amount of space those files can occupy.
-   We implement message flood detection by restricting the number of messages a user can post in a minute. By default, this limits to 30 in a single room, and 60 sitewide. (Detection for this is somewhat involved -- we keep a separate counter for each minute for each user for each room, as well as a counter for each minute for each user over the entire site.)
-   Finally, we limit the number of API calls a user can invoke in a given 60-second period. Different limits are used for different APIs.


Caching
=======

Caching is deployed in a number of ways throughout FreezeMessenger. We sort cache methods into three departments:

1. __Disk Cache__: A cache that writes to disk. This currently only includes the custom disk-cache implementation included with FreezeMessenger. However, if a memory cache or a distributed cache is available (in that order), they will always be used instead of a disk cache.
2. __Memory Cache__: A cache that writes to system memory, typically for users that can be "out-of-sync" with users on other servers. This currently only includes [APC](http://php.net/manual/en/book.apc.php) and [APCu](http://php.net/manual/en/book.apcu.php), but any distributed cache may be used instead.
3. __Distributed Cache__: A cache that is synchronised across servers, typically for data that should be updated for all users simultaneously. This currently includes [Redis](https://github.com/phpredis/phpredis) and [Memcached](http://php.net/manual/en/book.memcached.php), though any available memory cache may be used instead (albeit at the risk of de-syncing data).

Complete Caches (Disk)
----------------------

The [`emoticons`](http://josephtparsons.com/messenger/docs/database.htm#emoticons) and [`config`](http://josephtparsons.com/messenger/docs/database.htm#configuration) database tables will be cached in their entirety, as they rarely change and are frequently read.


Object Caches (Disk)
--------------------

Room and User objects are cached automatically when they are fetched from the database, and will typically be re-cached whenever they are used to update their database representation.

Notably, certain lists are also cached as a part of this:

-   Users have five: one for watched rooms, one for favourite rooms, one for friended users, one for blocked users, and one for groups the user is a member of [todo]. These will be stored with the rest of the user data where applicable, e.g. in columns on the user table.

-   Rooms have two: a list of users who are watching the room, and a list of censor words applied to that room.

Result Caches
-------------

Some functions query many different tables in order to ascertain relatively little information. We try to cache such function calls in memory tables (if supported), with the table's primary key being the composite of the function's inputs, and its remaining columns being the function's outputs.

-   __fimDatabase->hasPermission__ takes two arguments, a fimRoom and a fimUser, and computes the bitfield of the user's permissions in that room. If a distributed cache is available, we use it to cache the result; alternatively, we cache the result in the [`roomPermissionsCache`](http://josephtparsons.com/messenger/docs/database.htm#roomPermissionsCache) table.

-   __The number of queries of a given type a user has made in any given minute__ is preferentially tracked using a memory cache. If no memory cache is available, it will instead use the [`accessFlood`](http://josephtparsons.com/messenger/docs/database.htm#accessFlood) table.

Memory Table Caches
-------------------

Some data is transient in nature, and stored in memory tables as a result. While this data will be lost on a restart (or, possibly, once the memory table gets too large), in return it becomes far faster to access.

As memory tables are very transient in nature, we never rely on a memory table cache; the data is always available through slower alternatives. In general, we try to use them opportunistically:

-   If used, the [`roomPermissionsCache`](http://josephtparsons.com/messenger/docs/database.htm#roomPermissionsCache) and [`accessFlood`](http://josephtparsons.com/messenger/docs/database.htm#accessFlood) tables will use memory tables if available.

-   [The tables used when no other event system is available](#simple-tables) will preferentially be memory tables.

-   The [`ping`](http://josephtparsons.com/messenger/docs/database.htm#ping) table, which keeps track of active users, will use a memory table if available.


Database Abstraction Layer
==========================

[For information about the database abstraction layer FreezeMessenger uses, refer to its Git page.](https://github.com/FreezeWarp/freeze-db-abstraction)
