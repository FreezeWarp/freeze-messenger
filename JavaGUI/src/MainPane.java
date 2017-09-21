import com.fasterxml.jackson.databind.JsonNode;
import javafx.application.Platform;
import javafx.beans.binding.Bindings;
import javafx.collections.FXCollections;
import javafx.collections.ObservableList;
import javafx.event.ActionEvent;
import javafx.event.EventHandler;
import javafx.fxml.FXML;
import javafx.geometry.Pos;
import javafx.scene.control.*;
import javafx.scene.control.cell.PropertyValueFactory;
import javafx.scene.image.Image;
import javafx.scene.image.ImageView;
import javafx.scene.image.ImageViewBuilder;
import javafx.scene.input.KeyCode;
import javafx.scene.input.KeyEvent;
import javafx.scene.layout.Priority;
import javafx.scene.layout.VBox;
import javafx.scene.text.Font;
import javafx.scene.text.FontWeight;
import javafx.scene.text.Text;
import javafx.scene.text.TextFlow;

import java.text.SimpleDateFormat;
import java.util.*;

public class MainPane {
    @FXML
    public VBox messageList;

    @FXML
    public TextArea newMessageText;

    @FXML
    public ScrollPane messageListScroll;

    @FXML
    public TableView userList;

    @FXML
    public TableColumn avatar = new TableColumn<User, String>("Avatar");

    @FXML
    public TableColumn username = new TableColumn<User, String>("User Name");

    /**
     * A list of users currently considered active. userList monitors this for changes, and updates accordingly.
     */
    ObservableList<User> activeUsers =  FXCollections.observableArrayList();

    /**
     * A map between user IDs and user objects. Used mainly for caching.
     */
    public static Map<Integer, User> users = new HashMap<>();

    /**
     * A map between image URLs and image objects. Used mainly for caching.
     */
    public static Map<String, Image> images = new HashMap<>();

    /**
     * Get an ImageView representation of an avatar.
     * @param avatar The source URL.
     * @return An ImageView containing the source URL, resized.
     */
    public static ImageView getAvatar(String avatar) {
        if (!images.containsKey(avatar)) {
            images.put(avatar, new Image(avatar, 24, 24, false, true));
        }

        return ImageViewBuilder.create()
                .image(images.get(avatar))
                .build();
    }

    /**
     * Get a User object from a userId.
     * @param userId The user's ID.
     * @return A corresponding User object.
     */
    public static User getUser(int userId) {
        if (!users.containsKey(userId)) {
            JsonNode user = GUIDisplay.api.getUser(userId);
            User newUser = new User();
            newUser.setId(user.get("id").asInt());
            newUser.setName(user.get("name").asText());

            if (user.get("avatar").asText().length() < 1) {
                newUser.setAvatar(GUIDisplay.api.getServerUrl() + "webpro/client/images/blankperson.png");
            } else {
                newUser.setAvatar(user.get("avatar").asText());
            }

            users.put(userId, newUser);
        }

        return users.get(userId);
    }

    /**
     * This is the current room we have loaded and are getting messages for.
     * In the future, this will be an array of multiple rooms.
     */
    Room currentRoom = new Room(1);


    /**
     * A timer object used for recurring queries.
     */
    static Timer timer = new Timer();


    public void initialize() {
        /* UserList SetUp */
        // Bind the username column to the "name" property from a User object.
        username.setCellValueFactory(new PropertyValueFactory<User, String>("name"));

        // Bind the avatar column to the "avatarImageView" property from a User object.
        avatar.setCellValueFactory(new PropertyValueFactory<User, String>("avatarImageView"));

        // Bind the table's data to the activeUsers list.
        userList.setItems(activeUsers);


        /* Recurring GETs */
        // Check for new messages every 3 seconds.
        timer.schedule(new RefreshMessages(), 0, 3000);

        // Check the currently active users every 10 seconds.
        timer.schedule(new RefreshUsers(), 0, 10000);


        /* Align messages to bottom */
        messageListScroll.setHbarPolicy(ScrollPane.ScrollBarPolicy.NEVER);
        messageListScroll.setFitToWidth(true);

        messageList.minHeightProperty().bind(Bindings.createDoubleBinding(() -> messageListScroll.getViewportBounds().getHeight(), messageListScroll.viewportBoundsProperty()));


        /* Send Message On Enter */
        // todo: shift+enter
        newMessageText.setOnKeyPressed(event -> {
            if (event.getCode() == KeyCode.ENTER) {
                String text = newMessageText.getText();
                GUIDisplay.api.sendMessage(currentRoom.getId(), text);

                newMessageText.setText("");

                event.consume(); // don't show enter key
            }
        });
    }


    /**
     * Runner to check for new messages.
     */
    class RefreshMessages extends TimerTask {
        public void run() {
            JsonNode messages = GUIDisplay.api.getMessages(currentRoom.getId(), currentRoom.getLastMessageId(), !currentRoom.isArchiveFetched());

            currentRoom.setArchiveFetched(true);

            if (messages.isArray()) {
                for (final JsonNode message : messages) {
                    currentRoom.addNewMessage(message);

                    int userId = message.get("userId").asInt();
                    User user = getUser(userId);
                    ImageView avatar = getAvatar(user.getAvatar());
                    final Text userName = new Text(user.getName());
                    userName.setFont(Font.font(null, FontWeight.BOLD, -1));

                    Calendar c = Calendar.getInstance(Locale.getDefault());
                    c.setTimeInMillis(message.get("time").asLong() * 1000);
                    final Text messageTime = new Text((new SimpleDateFormat()).format(c.getTime()));
                    final Text messageText = new Text(message.get("text").asText());

                    Platform.runLater(new Runnable() {
                        @Override
                        public void run() {
                            messageList.getChildren().add(new TextFlow(avatar, userName, new Text(" @ "), messageTime, new Text(": "), messageText));
                        }
                    });

                }
            } else {
                GUIDisplay.alert("Bad response from getMessages.");
            }
        }
    }


    /**
     * Runner to check for active users.
     */
    class RefreshUsers extends TimerTask {
        public void run() {
            JsonNode users = GUIDisplay.api.getActiveUsers();
            System.out.println(users);
            activeUsers.clear();

            if (users.isObject()) {
                for (final JsonNode user : users) {
                    System.out.println(user);
                    activeUsers.add(getUser(user.get("userData").get("id").asInt()));
                }
            }
        }
    }
}