# Roadmap #
At this point, the roadmap is not clear, but it is possible many features will be delivered via point-releases, thus reducing necessary bug testing. Maintenance will quite likely be a part of FIMv3.1 with its own API, and others may also be delivered at this time. Ultimately, it will come down to which features are most desired in the short-term.

That said, FIMv4 will likely be released in December, if all goes according to plans.

# Summary & Features #
Below I discuss a number of future plans for FIMv4, which will take what is already a truly amazing product and make it something wonderful. Truly, simply wonderful. The bug tracker also lists a number of planned features at the moment, but does not go as in-depth. Of course, as FIMv3's development cycle showed, you can expect that many features will be axed and many others added, but that doesn't change the many ideas for the future.


## Alternative Databases: VoltDB, PostGreSQL, MSSQL, Oracle, SQLite, IBM DB2 (FIMv4) ##
FIMv3 completely overhauled the database system used in FIMv2 and replaced dry structure query language with highly structured data (that, for better or for worse, is defined in PHP using arrays). The system, while not overly intuitive in the traditional sense, offers a great number of features that simply wouldn't otherwise exist in done in any other way: the ability for plugins to smoothly modify query data before it is sent, the power to manage databases of all varieties with ease, and perhaps more critically the avoidance of unnecessary and often murky function calls.

With this in mind, an incredibly broad array of databases could be supported in FIMv4. Two that have caught my attention recently are VoltDB and PostGreSQL. The latter simply because it is far in a way better than MySQL, and the former because it is a new solution entirely. Outside of these, any number of additional database backends could also be supported.


## New Login Methods (FIMv4) ##
Ideally, FIMv4 will implement Facebook Connect, Google Login, and OpenID login systems in addition to its current PHPBBv3, vBulletinv3, and vBulletinv4 integration methods. These, of course, will be much more advanced compared to the simple database lookup methods currently employed, and the session system will be all the more useful as a result.

In addition, the vanilla login system that currently works in FIMv3 will be upgraded greatly, supporting far more advanced features that are currently absent, such as group membership and registration restrictions.


## Server-Sent Events (FIMv3.1 or FIMv4) ##
**Note**: This feature was actually largely implemented in FIMv3. However, some features described below will still be added in future versions, notably user presence setting.

Server-sent events have become the PUSH messaging of HTML5 (or whatever you want to call it). It is powerful, well-defined, and essential to IM on the web. Thus, it will also be a huge priority (arguably the one must-have feature of the next release). The spec itself was first provided by Opera; Chrome & Safari followed suite not too long after, and now Firefox plans to support it (properly) in Firefox 6, which will likely be released shortly after FIMv3. The one holdout for now is Internet Explorer, as well as the slew of mobile browsers, which will of-course be supported via the alternative provided methods: Long-polling, which is an experimental and incredibly glitchy feature of FIMv3, and standard polling.

This said, FIMv4 will not only implement server-sent events for message retrieval, but also (if plans don't change) for user presence, room  topics, and so-on.


## The MSN Paradigm (FIMv3B4 and FIMv4) ##
FIMv4 will implement to the extent possible MSN-like communication. All communication streams currently require a unique integer-based ID for communication. In the future, however, this will be replaced with simple "userx,usery,userz" strings that will act as both the ID of most rooms (all except those that belong to more than a select few users).

In addition, support for contact lists and user privacy settings will be added to the backend and WebPro interface.

Finally, the addition of OTR communication will be used for true private communication. This will store data only briefly on the end-server (in the message cache) but not in the permanent message store.

## New Engines ##
FIMv4 will add support for Google App Engine and Memcached, both allowing for more dynamic runtimes.


## Logging and API Clients ##
Optional support for advanced logging and API clients will be added in FIMv4. API Client support is where clients must establish specific API keys with a server, thus ensuring only certain applications will be runnable with server if that is preferable.

Both of these features will be optional, and generally only intended for a more locked-down environment.


## Some New Interfaces (FIMv4 or later) ##
Plans exist to create a new, jQuery-Mobile powered mobile interface, though it is not yet set in stone.


## Message Editing (FIMv3B4) ##
Message editing will be implemented with a corresponding log. It will be easily disabled by hosts, may not be supported by all clients, and will be integrated with Server-Sent communications.


## User Privacy and Content Settings (FIMv3B4 and FIMv4) ##
FIMv4 will implement an improved user privacy and content system, which will include:
  * Flagging images for certain content and age requirement.
  * Having a room age requirement, and requiring it to be appropriate based on administration-defined censors.
  * Block lists for group contact.


## YAML (FIMv4) ##
FIMv4's API will support, in addition to JSON and XML, YAML formatting.


## Proper Maintenance (FIMv4) ##
Proper maintenance tools will be delivered in FIMv4. These have thus far been lacking, and largely after-thoughts. As such, in FIMv4, the following maintence tasks will be possible, all done via the API:
  * Formatted messages cache regeneration.
  * Message-count regeneration.
  * Temporary product turnoff.


## The Full API (FIMv4) ##
Some back-end communication is not supported via the API, and can only be done via the provided WebPro moderate.php file or third-party equivalents that must be uploaded to the server. This will thus be a huge priority. Among other tasks that will be possible:
  * Maintenance by Administration
  * Modifying Censor Lists, Templates, Phrases, and so-on


## Translations The Right Way (FIMv4) ##
One of FIMv3's big features was going to be the ability to translate the product. This fell apart when two choices were made: first making the backend agnostic from the frontend interfaces, and thus allow custom frontends to be created, and second replacing many templating parts of the WebPro interface with live, in-the-javascript code.

Version 4 will update the WebPro interface to read a phrases file, which itself will be a cached version of the database. Any updates to the phrases will need to update the file, which should also be more streamlined.

Other goodness will come this way as well, but it is not yet set in stone.

## jQueryUI 1.9 ##
FIMv4's WebPro interface will update jQueryUI to 1.9, and will not release until jQueryUI 1.9 is stable.


## Stuff to be Removed ##
A variety of things will be removed in FIMv4, which are outlined in [Deprecated](Deprecated.md).

In addition, the templating backend will be removed. This will reduce a significant amount of code that as never really doing much anyway.