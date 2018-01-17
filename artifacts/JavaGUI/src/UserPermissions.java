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
    public boolean modPrivs;
    public boolean modRooms;
    public boolean modUsers;
    public boolean modEmoticons;
    public boolean post;
    public boolean privateAll;
    public boolean privateFriends;
    public boolean view;
    public boolean selfChangeAvatar;
    public boolean selfChangeFriends;
    public boolean selfChangeIgnore;
    public boolean selfChangeParentalAge;
    public boolean selfChangeParentalFlags;
    public boolean selfChangeProfile;

    @JsonProperty("protected")
    boolean protectedUser;
}