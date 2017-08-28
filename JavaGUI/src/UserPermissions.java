import com.fasterxml.jackson.annotation.JsonProperty;

/**
 * Created by joseph on 28/08/17.
 */
public class UserPermissions {
    public boolean changeTopic;
    public boolean createRooms;
    public boolean deleteOwnPosts;
    public boolean editOwnPosts;
    public boolean modCensor;
    public boolean modFiles;
    public boolean modPrivate;
    public boolean modPrivs;
    public boolean modRooms;
    public boolean modUsers;
    public boolean post;
    public boolean postCounts;
    public boolean privateRoomsAll;
    public boolean privateRoomsFriends;
    public boolean roomsOnline;
    public boolean view;

    @JsonProperty("protected")
    boolean protectedUser;
}