import com.fasterxml.jackson.databind.JsonNode;

/**
 * This stores the current working data for a single room's messages.
 */
public class Room {
    /**
     * The ID of the room.
     */
    int id;

    /**
     * The last (that is, greatest) message ID received in the current room.
     */
    int lastMessageId;

    /**
     * If the message archive has already been fetched for this room.
     */
    boolean archiveFetched;


    public Room(int id) {
        this.id = id;
    }


    public int getId() {
        return id;
    }

    public int getLastMessageId() {
        return lastMessageId;
    }

    public void setLastMessageId(int lastMessageId) {
        if (lastMessageId > this.lastMessageId) {
            this.lastMessageId = lastMessageId;
        }
    }

    public boolean isArchiveFetched() {
        return archiveFetched;
    }

    public void setArchiveFetched(boolean archiveFetched) {
        this.archiveFetched = archiveFetched;
    }


    public void addNewMessage(JsonNode message) {
        this.setLastMessageId(message.get("messageId").intValue());
    }
}
