# Usernames Are Horrible #
Prior to working on FreezeMessenger I spent much time working with usernames when writing vBulletin plugins. I learned early on that you can make absolutely no assumptions about how usernames are encoded, ever.

Which is silly, really. In an ideal world everybody would just use UTF8 and move on.

In FreezeMessenger's database, UTF8 is used for everything. In syncing with vBulletin and PHPBB we try to have the database convert the username columns to UTF8, and hopefully that works. In some versions of vBulletin, however, it doesn't: vBulletin converts, at times half at random, multibyte characters into entities. This is weird, but at least if we use UserID as our foreign key, there really isn't a problem: the entities are just carried over into FreezeMessenger's sync table.

But when dealing with API calls (over HTTP), things become even weirder. And it's not just character encoding (though that at times can rear its ugly head as well): trying to sanely reference a CSV list of usernames may as well be impossible, when usernames can include commas themselves. After a while, we decided it simply wasn't practical: CSV was transitioned out during the development process, and JSON lists could be used instead. This was a little weird, but it worked.

In fact, we momentarily considered forcing usernames to be Base64 encoded. JSON may or may not have been ideal, but this would have been simply insane!

# Request Methods #
FIMv3 provides three seperate request methods: poll, longPoll, and server-sent events. A quick comparison follows:

## Polling ##
Polling is the standard method of retrieving messages. It requests messages, returns empty if none are found, or returns the messages if they are to be found. It does nothing fancy.

By default, polling is the only supported request method.

## Long Polling ##
Long Polling is a modification of polling that works using the same API as polling (getMessages), but, unlike the alternative, does not close the connection or return the empty data set if no messages are found. Instead, the script will continually search for messages until found, or until the max retries limit is reached. This means that there are several drawbacks, however:

  * The MySQL server may be over-taxed, as the standard MySQL request can be complicated.
  * Apache servers tend to suffer from memory leaks until this methodology.
  * Scripts may timeout.
  * It may pose a DDoS risk.

Its benefits are less impressive:
  * Reduce latency dramatically by removing the API overhead on each subsequent call.
  * longPolling only works with MySQL memory tables, and thus queries may return almost instantly. longPolling allows the instantaneous returns more easily.

By default, longPolling is disabled.

**Long Polling may be removed in FIMv4. Possibly, polling will instead be removed if long polling has become more stable.**

## Server Sent Events ##
Server sent events are a custom API found in the root directory (eventStream.php) that, unlike the others, only allows for a small handful of directives. It conforms to the event stream protocol (now supported in Opera, Google Chrome, and Safari, with Firefox support coming soon), and also is the only way of accessing unread messages. Unlike longPolling, it is far smoother, and is not as easily at risk for overtaxing the MySQL server.

Server Sent Events will not work out of the box on some servers. FastCGI, for instance, requires that the `ServerSentFastCGI` configuration value be set to `true`, for instance. In this state, it will bear greater similarity to LongPolling, because the script execution will end as soon as content is received.

By default, ServerSentEvents are disabled, but use is strongly encouraged where possible.

# Vanilla Logins #
The vanilla login system used in FIMv3 is a very simple, very basic implementation of the login system that allows for a small number of core activities without using vBulletin or PHPBB. In comparison to the other systems, it is nowhere near as powerful, but it does allow for basic activities, these being:

  * User Registration
  * User Login
  * Avatars

The following will still be implemented, but have not yet been:
  * Emoticons

The following, however, will not be implemented in FIMv3:
  * User Groups

In general, one of the other systems is recommended. User Registration is only supported for vanilla logins, though is an API-standard feature. In FIMv4, vanilla logins will support far more controls.

# Future-Proofing #
Note: The following document highlights many aspects of FIMv3 that illustrate its intent on hopefully not becoming out of date where possible -- or at least staying as far ahead as one could conceivably do.

It is still in draft state.

## UTF-8 & UTF-16 ##
UTF-8 was a big goal in the overall design of FreezeMessenger from the very first (private) version. Other products - notably vBulletin with which FIM was originally intended to interface with - use messy encodings and hackish means of supporting the full Unicode spectrum. Thus, the product - internally - has become greatly out-of-date. UTF-8 is currently an industry standard for character encodings, with the only "respectable" alternative UTF-16: the successor of UTF-8 that supports more of Unicode. UTF-8 still supports nearly all common scripts, but UTF-16 (and, to go further, UTF-32) allow even more.

To this end, UTF-8 is used and properly supported everywhere (at times with help from the Multibyte String PHP Extension). However, in the future (FIMv5 or so), UTF-16 would also be interesting to explore. That said, UTF-8 should conceivably work for years to come, without breaking down like the various ISO character encodings have.

## Database Abstraction Layer ##
Learn More: [DatabaseAbstractionLayer](DatabaseAbstractionLayer.md)

# Maximum Values #
The following is a list of size constraints on different database-stored items:

  * Users: 8; 0-99,999,999
  * Groups: 6; 0-999,999
  * Smilies: 6; 0-999,999
  * Rooms: 6; 0-999,999
  * Mod Log Entries: 12; 0-999,999,999,999
  * Phrases: 8; 0-99,999,999
  * Templates: 8; 0-99,999,999
  * Hooks: 8; 0-99,999,999
  * Messages: 15; 0-999,999,999,999,999
  * Cached Messages: 6; 0-999,999 (the default maximum is only 100 messages, though)
  * Search Message Phrases: 15; 0-999,999,999,999,999
  * Search Message Entries: 15; 0-999,999,999,999,999
  * Anonymous User IDs (Experimental Feature): 6; 0-999,999
  * File Types: 6; 0-999,999
  * Maximum File Size: 12; 0-999,999,999,999 (in bytes; maximum ~ 931 GiB)
  * Kick Length: 8; 0-99,999,999 (in seconds; ~ 3 years)
  * Salts: 6; 0-999,999

Similar limits are placed on strings:
  * Phrases Names: 50
  * Template Names: 50
  * User Names: 50
  * Group Names: 50
  * Hook Names: 50
  * User Name Formatting Start+End: 300
  * Session Useragent: 300
  * Session Hash: 128
  * IP Address: 128
  * Template Vars (Deprecated): 1,000
  * Template Data: 4,294,967,296
  * Phrase Data: 4,294,967,296
  * Room User/Group Lists (e.g. allowed users): 65,536
  * Mime Types: 300
  * File Extensions: 8
  * Message Text: 10,000
  * IVs: 15
  * Message Flags: 10
  * Most URIs: 1,000
  * Group Member IDs: 1,000,000
  * Colours: 11 (abc,def,xyz)
  * Fontfaces: 50

# Events #
Events, stored in the `event` table, allow for more effective communication with a number of datastores that allow for greater speed and scalibility where it would not otherwise be possible. Events are transient in nature: they are cached (usually recycling after 100 or so entries) and are entirely symbolic of modifications to other parts of the database.

In the root API, they can be retrieved with the eventSource.php file (and indeed this is the primary intent). Supplementary third-party APIs may also allow for event retrieval, though there is no standard API for this purpose. The following detail the events that will occur throughout the system:

  * Delete a Message -- deleteMessage
  * Undelete a Message -- undeleteMessage
  * Change a Room Topic -- changeRoomTopic
  * Change a Room Name -- changeRoomName
  * A Room is Deleted - deleteRoom
  * A New Private IM - missedMessage


Note that the new private IM event is complimentary to the larger `unreadMessages` table, primarily for the purpose of tracking offline messages. The event table is naturally far faster due to its memory store, and thus the unreadMessages table is usually only read once (when a user logs in or loads the page).

# BBCode #
BBCode was originally going to implemented directly in FreezeMessenger, but the complexities of doing so are great (especially in the interest of scalability). Instead, BBCode is left mostly to the client. The API will return certain BBCode, which the client is encouraged to format to its needs. These BBCode are:

  * [img="alt"]url[/img] -- The API will occasionally modify sent "smilies" as defined by the server. The original text will be stored as "alt" and the image URL "url".

# Message Caching #
Message metadata is cached in three distinct ways (or will be once the overhaul is complete). These are:
  * Time-based - Every message made at the start of a day is cached. An APC/Memcache-set flag will be used to determine if the day is new or not on message send (in case this is somehow corrupted, the table will be manually queried to confirm).
  * Room-based - Every xth message (usually the thousandth) made in a room is cached.

By doing this, we can query the cache to determine an approximate range for how to restrict the incredibly large messages table by the primary ID (using "LIMIT" still requires MySQL to sort through thousands of messages; here it does not). For example:
  * Room 1, after message ID 2001: Query to see where 2001 falls in the room-based cache. If it falls between 1500 and 2500, and we're only getting 40 messages, we'll just query that subset of 1000. If it falls between 1500 and 1510, we'll expand in the proper direction until we get something that is suitably large. If, instead, it falls between two incredibly large numbers, we'll manually limit it as appropriate (thus, we may only get 5 or so messages for the least active rooms). This will default to 10,000 for now.
  * Room 1, after two days ago: First we will query the time-based table to see what messages come before and after the two days ago timestamp (if it breaks even, we'll take the next day's midnight as well). From here we can further process the times to see the specific range for the room and work with that range.
  * Room 1, most recent: This time we'll check the room cache to see the last message indexed and go from there.

Other caches will also be used as appropriate. For instance, we will store the last message sent. If we are trying to get something that is only 20 messages before this, we'll skip the above checks.

# Caching #
  1. APC Caching -- Caching is an obvious one. We try to cache small tables whenever possible (usually tables designed to have under a 100 rows, but not effectively implemented with the cache).
  1. Message Table Cache -- 100 messages are stored in the cache table to allow for efficient retrieval of recent messages. No more than 100 messages ever appear in these table.
  1. "Add", "Remove", and "Replace" Methods -- For large lists (like the ignoredUsersList), a table will always store the list across a number of rows. The API then supports replacing the entire list (which is impractical if a list contains around 1,000 entries), or simply adding or removing a single value to the list.

Note the approximate sizes of the several APC caches:
  * Hooks - Hooks is not a standard cache, and in many installations may never be used. It does not require indexing, but is usually an array with minimal entries that are very large in size.
  * Kicks - Kicks can be huge, upwards of 100,000 entries.
  * Permissions - Permissions can also be huge, upwards of 100,000 entries.
  * Watch Rooms - Watch Rooms can also be huge, upwards of 100,000 entries.
  * Censor Lists - Censor Lists are small, usually under 100 entries.
  * Censor Words - Censor Words are also small, usually under 100 entries.
  * Room List Names - Room List Names are also small, usually under 100 entries.

# Deprecated #
## Database ##
### Vanilla Logins ###
The current vanilla login system is at best placeholder. It may be improved in the future, but should not be expected to retain compatibility with previous iterations.

## APIs ##
All APIs in FIMv3 are largely final. Few changes should be expected, however:
  * In FIMv4, the "errDesc" node will be removed from all APIs.

## Functions ##
  * The FIMv3 database API is still in its infancy. It will see large changes when new drivers become supported. However, the general syntax should not change, so most or all uses of the database class should largely remain supported in future versions.
  * All FIMv3 parser functions should be final. They will not change in the near future.
  * All FIMv3 general functions should be final. They will not change in the near future.