# Summary #
FreezeMessenger is an advanced PHP+AJAX messenger. It is currently under development (nightlies are, at present, mostly broken), and this page will be updated once the stable has been released.

# Release Schedule #
First things first, you'll notice this project has received next to no love in nearly two years. While I had been able to devote nearly 20-30 hours a week to it at one point, I am currently only able to do so infrequently.

The project is still being worked on. I have made several substantial changes to the source since the beta in August, 2011, which you can view to some extent in the source tab. Ideally the next beta will be out within a few months; in general, whenever I return to the project there is something fairly large I want to change, which has hampered my ability to release a new beta version.

A truly stable version likely won't be ready before summer, 2014. That said, I will need beta testers. Many. If you are at all interested in the project, and have any interest in deploying the fourth beta and helping me test it on a reasonably-sized server (one that would get around 50 active users), please contact me (josephtparsons@gmail.com). I will love you for it.

My current, overly optimistic schedule of beta releases:

  * Beta 1 (released) - June 25th, 2011
  * Beta 2 (released) - July 23rd, 2011
  * Beta 3 (released) - August 30th, 2011
  * Beta 4 - April, 2014 (On Track)
  * Beta 5 - May, 2014
  * RC 1 - July, 2014
  * Final - August, 2014

As of now, the third beta (found under downloads) is mostly stable (there are a few egregious bugs, but they don't cause any huge user experience hiccups), but it is not exactly "good." Still, feel free to try it out. If you like what you see, the next beta should be amazing. If you don't... tell me why (either by email or by filing a bug report). There's a chance I've already fixed it, and if not I might yet be able to.

Anywho, here is a preview from shortly after B1 (spot the typo!):
![https://lh4.googleusercontent.com/-eqthZL-O7MU/ThglC8moXTI/AAAAAAAAAJk/9oz6nE03XrE/s912/desktop53.png](https://lh4.googleusercontent.com/-eqthZL-O7MU/ThglC8moXTI/AAAAAAAAAJk/9oz6nE03XrE/s912/desktop53.png)

And here is one from the B4 nightlies:
<a href='https://lh5.googleusercontent.com/-immWk_weS90/UJiFWSFex2I/AAAAAAAADG8/zPOwZZhtF6c/s1286/FreezeLinux.png'><img src='https://lh5.googleusercontent.com/-immWk_weS90/UJiFWSFex2I/AAAAAAAADG8/zPOwZZhtF6c/s1286/FreezeLinux.png' /></a>

# Version History #
  * Beta 4 (April, 2014)
    * (Landed) Eliminate Server Systems for Templates, Phrases, and BBCode; move Templates & Phrases to JSON file for Javascript.
      * (Landed) Update AdminCP to allow JSON editing of the appropriate files.
    * (Landed) Parental Content System
      * (Landed) Image Flagging
      * (Landed) Room Flagging
    * (Landed) MSN Paradigm
      * (Landed) Context-Based Room IDs for Private Rooms (e.g. "1,3").
      * (Working) Friend Lists & Privacy Settings (~5-10 Hours)
    * (Landed) Overhaul Message Caching & APC Caching
      * (Working/Landed) Refactor Cache Class
      * More frequent use of cached data where possible.
    * (Landed) Vanilla Logins
      * (Landed) User Registration
    * (Landed) New Install GUI
    * (Working-80%) Improved Events Subsystem (~5-10 Hours)
    * (Working-50%) New Field for Watch Rooms, Ignore List, and Room Lists (~5-10 Hours)
    * (Working) New Interface for Allowed Users
    * (Landed) File Caching as Alternative to APC Caching
    * (Landed) Several New WebPro Themes, Change Default to Absolution
    * (Working) Re-add Notifications & Official Rooms
    * (Working) Remove All English Strings (~1-2 Hours)
    * (Landed) Cleanup of WebPro and Some Functions
      * (Landed) Use non-blocking AJAX wherever possible.
      * (Working-50%) AJAX Autocoomplete Instead of Old Method
    * Partial Refactor of Database (~20-30 Hours):
      * (Landed) Remove Horrid, Horrid Long Hand Select (Line count roughly divided by 5 for queries.)
      * (Landed) Split Database into Abstract and SQL Classes
      * (Working-80%) Move nearly all database queries into fim\_database.php, reducing redundancy.
      * (Working-10%) Have objects represent rooms and users.
      * (Landed) Add LEFT JOINs
      * (Working-40%) Add (and use) Transactions
      * (Working-50%) Support PDO
      * (Working-50%) Support PostGreSQL
    * (Working) Remove errDesc (~2-5 Hours)
    * (Working-80%) Upgrade jQuery, jQueryUI, and Themes
    * (Working-60%) Overhaul Login System
      * Anonymous User Support
    * (Soon) Message Editing

  * Beta 5 (May, 2014)
    * Offset/limit in all APIs
    * Fix Absolution Theme

  * Version 3.1 / 3.2 (Fall-Winter, 2014)
    * PostGreSQL Support
    * "Simple" Interface
    * "Mobile" Interface
    * "WebPro" Interface Work
      * Access Key Highlighting
    * reCaptcha Support
    * New Active Users Functionality

  * Version 4 (~2015)
    * Memcache(d) Support
    * Proper API Keys
    * Improved Configuration Editor (not just a list)
    * Google App Engine Support
    * Plugins (maybe)
    * Python (or Java) PC/Linux Client
    * WebSockets

# Features (Landed) #
  * Scalable for both Large and Small Deployments
    * Support for HTML5 Server-sent Events and Long Polling
    * APC Caching (required in Beta 3)
    * Scalable Database Scheme
  * Integrates with vBulletin 3.8, vBulletin 4, and PHPBB
  * Room Creation and One-on-One Messaging
  * Optional Message Encryption
  * Optional File Uploads for Message Embedding
  * Advanced Plugin and Templating Interface (though it still could use a lot of work)
  * Supports Custom Frontends via Advanced API (both JSON and XML supported)