# Introduction #

FreezeMessenger has not yet been tested in a large real-world deployment, but the software none-the-less is expected to perform reasonably well in systems with up to 200 concurrent users and millions of messages, thanks to the measures that are detailed in this page.


# WARNING: Inefficient Features in FreezeMessenger That Can be Enabled #
Before going on, it is worth advising users that FreezeMessenger does offer features that are very not fast, but are disabled by default (with fairly sane defaults available instead). These are outlined below:

  * (TODO: shouldn't be default) fileContentsInDatabase - The `fileVersions` table can include file contents. It really shouldn't, but it can. Several limitations will result: files will use Base64 encoding in this mode, which takes up ~33% more space (files may also be stored in Base64 in the filesystem as a security measure, but in the database there is no option).
  * (TODO) searchMethod = 'fullText', searchMethod = 'phraseSearch' are both far slower than the default searchMethod, 'phraseMatch'. These all basically trade usability for performance: the fullText search can search entire message contents (though can not work if encryption is enabled), but is slow, while the phraseSearch method is a little bit faster but is restricted to searching in valid phrases. 'phraseMatch' is the most limited -- only full-word matches will be possible -- but it is also fairly fast.
  * (TODO) (Default, But Kinda Minor) By default, FreezeMessenger includes a default accessLog that is added to with all database queries. This doesn't cause a major slowdown, but systems particularly concerned with performance are recommended to disable this log.
  * (TODO) (Default, And Very Minor) FreezeMessenger also supports disabling the activeUsers/ping functionality. This feature generally only causes minimal slowdown, but disabling it can none-the-less boost performance in a pinch. PostGreSQL systems, which aren't able to use the MEMORY engine, may benefit more from doing this.



# MEMORY Table Caching #
MySQL implements a storage engine called MEMORY that stores all data in memory -- greatly improving the speed of transactions over reasonably small tables. The main drawback to this method is that table sizes are greatly limited, but if used carefully this can be one of the most efficient ways of improving table performance.

The following tables will use a MEMORY engine if available:
  1. Event Tables: `events`, `roomEvents`, `userEvents`, and `messagesCached`
  1. `ping` (information is only relevant for a short period of time)
  1. `sessions` (information is only relevant for a short period of time)
  1. `roomPermissionsCache` (a summary of the `kicks` and `roomPermissions` table that is created whenever a user permission check is performed; kicks and unkicks may modify this table directly in order to avoid having to wait for the cache to expire).

# PostGreSQL Triggers (TODO) #
PostGreSQL's trigger system is an excellent alternative to MySQL's MEMORY tables for use with the event tables (in fact, it is arguably better). A trigger will fire be used whenever an insert into these tables occurs, eliminating the need to poll.

# Summary Tables #
Several tables have summary counterparts that eliminate the need to JOIN with common queries. These include:
  1. `messagesCached` - Combines information from the `messages` and `users` tables.
  1. `counters` effectively exist as a replacement for common aggregate queries (indeed, FreezeDatabase chooses not to support aggregate functions, as in the real world counters will always be more efficient if implemented correctly).
  1. `roomStats` is a counter of the total number of posts made by a given user in a given room.
  1. `searchCache` logs search results. This is generally not really all that useful, but can be an acceptable way of using a slower search algorithm without as many drawbacks. It will generally be combined with `rooms`.`lastMessageTime` to check if it is still valid or not (since a new message will shift queries down, potentially).

# Index Tables #
Experimental index tables exist to try to better optimise for certain queries. Honestly, we have no idea if they work, but they're good for analytics information regardless.

  1. `messageIndex` - Caches the messageId of every xth message made in a room.
  1. `messageDates` - Caches the messageId of the first message made in a time interval.

# Column Caches #
Several columns correspond with the query results from a different table:
  1. `rooms`.`lastMessageTime` and `rooms`.`lastMessageId` record the most recent messageId and messageTime in a given room.
  1. `rooms`.`messageCount` stores the total number of a messages in a room.
  1. `users`.`fileCount` and `users`.`fileSize` determine the aggregate size of the user's uploaded files.
  1. `users`.`ownedRooms` determines the number of rooms a user is an owner of.
  1. `users`.`messageCount` determines the number of messages a user has made.
  1. `users`.`favRooms`, `users`.`ignoredUsers`, and `users`.`friendedUsers` are the aggregate of those tables for the given user.

(TODO) All list column caches may be disabled if desired (they can become glitchy with a large number of entries).

# APC #
The `config`, `censorWords`, and `censorLists` tables are automatically stored using APC, as these tables are generally small enough (or, in config's case, used frequently enough) to gain from APC.