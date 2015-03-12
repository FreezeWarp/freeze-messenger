# Introduction #

An outline of the events method.

(TODO: Merge into API documentation.)


# Details #

Events, stored in the `events`, `roomEvents`, `userEvents`, and `messagesCached` tables (the last is essentially non-standard), allow for more effective communication with a number of datastores that provide greater speed and scalability where it would not otherwise be possible. Events are transient in nature: they are cached (usually recycling after 100 or so entries) and are entirely symbolic of modifications to other parts of the database.

In the root API, they can be retrieved with the stream.php file, which itself effectively runs four different scripts depending on the provided context (one for each table above). The following are the events the below to each event source:

## Rooms ##

  * Delete a Message -- deleteMessage
  * Undelete a Message -- undeleteMessage
  * Change a Room Topic -- changeRoomTopic
  * Change a Room Name -- changeRoomName
  * A Room is Deleted - deleteRoom

## Users ##
  * A New Private IM or Watch Message - missedMessage (note/todo: should not be given if the user has recently pinged).
  * Friend Request -- friendRequest

Note that the unreadMessage event is complimentary to the larger `unreadMessages` table, which is the permanent alternative (entries remain until they are marked as read). The `userEvent` table is naturally far faster due to its memory store, and thus the unreadMessages table is usually only read once (when a user logs in or loads the page).

## Events ##
The `events` source is currently not used for any event, however is implemented should that change.

## Messages ##
The `messages` source, unlike the other types, acts as an alternate (and preferred) way of obtaining recent messages in a room.