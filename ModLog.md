The `modlog` is a database that, at the very least, should be used by all AdminCP interfaces, conforming to the following specification. While it is entirely optional to offer the ability to read it (and deletion from it is highly discouraged), both are still left up to the developer. What is not is the format described below.

The `fulllog` database serves a similar purpose, except that it maintains a complete historical log of all moderator actions, stored using JSON\_encoded values of the entire relevant rows. Those rows are also listed under "Full Log Rows". For all edit actions, it will contain the row data prior to the edit; for all add actions, it will contain the row data that is being added; for all delete actions, it will contain the row data before deleted.

## Format by Action ##
| **Action** | **Format of `data`** | **Full Log Rows** |
|:-----------|:---------------------|:------------------|
| editCensorWord | `wordId` | `word` (from `censorWords` table), `list` (from `censorLists` table) |
| addCensorWord | `listId`,`wordId` | `word` (from request data, $database->insertId), `list` (from `censorLists` table) |
| deleteCensorWord | `wordId` | `word` (from request data, $database->insertId), `list` (from `censorLists` table) |
| editCensorList | `listId` | `list` (from `censorLists` table) |
| addCensorList | `listId` | `list` (from request data, $database->insertId) |
| deleteCensorList | `listId` | `list` (from `censorLists` table), `words` (from `censorWords` table) |
| addBBCode | `bbcodeId` | `bbcode` (from request data, $database->insertId) |
| editBBCode | `bbcodeId` | `bbcode` (from `bbcode` table) |
| deleteBBCode | `bbcodeId` | `bbcode` (from `bbcode` table) |
| editPhrase | `phraseName`-`languageCode`-`interfaceId` | `phrase` (from `phrases` table) |
| deleteMessage | `messageId` | n/a |
| undeleteMessage | `messageId` | n/a |
| deleteRoom | `roomId` | n/a |
| undeleteRoom | `roomId` | n/a |


## Standard Format of `modlog` ##
  * `userId` should always be the active user ID (the one doing the action)
  * `time` should always be the current time.
  * `ip` should always be the remote address of the active user, used for the specific request (`HTTP_REMOTE_ADDR`)


## Standard Format of `fulllog` ##
  * `user` should be the JSON-encoded user data array.
  * `time` should always be the current time.
  * `server` should be the JSON-encoded SERVER vars.