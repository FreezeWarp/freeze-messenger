import com.fasterxml.jackson.databind.JsonNode;
import javafx.application.Platform;
import javafx.beans.binding.Bindings;
import javafx.beans.property.SimpleObjectProperty;
import javafx.beans.value.ChangeListener;
import javafx.beans.value.ObservableValue;
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
import javafx.scene.text.*;
import javafx.util.Callback;

import java.text.SimpleDateFormat;
import java.util.*;

import static javafx.scene.text.TextAlignment.LEFT;
import static javafx.scene.text.TextAlignment.RIGHT;

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

    @FXML
    public TableView roomList;

    @FXML
    public TableColumn roomName = new TableColumn<Room, String>("Room Name");

    /**
     * A list of users currently considered active. userList monitors this for changes, and updates accordingly.
     */
    ObservableList<User> activeUsers =  FXCollections.observableArrayList();

    /**
     * A list of rooms on the site. roomList monitors this for changes, and updates accordingly.
     */
    ObservableList<Room> rooms =  FXCollections.observableArrayList();

    /**
     * A map between user IDs and user objects. Used mainly for caching.
     */
    public static Map<Integer, User> users = new HashMap<>();

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

        messageList.heightProperty().addListener(new ChangeListener() {
            @Override
            public void changed(ObservableValue observable, Object oldvalue, Object newValue) {
                messageListScroll.setVvalue(1.0);;
            }
        });
        /* UserList SetUp */
        // Bind the username column to the "name" property from a User object.
        username.setCellValueFactory(new PropertyValueFactory<User, String>("name"));

        // Bind the avatar column to the "avatarImageView" property from a User object.
        avatar.setCellValueFactory(new Callback<TableColumn.CellDataFeatures<User, String>, ObservableValue<ImageView>>() {
            @Override
            public ObservableValue<ImageView> call(TableColumn.CellDataFeatures<User, String> user) {
                return new SimpleObjectProperty<ImageView>(user.getValue().getAvatarImageView());
            }
        });

        // Bind the table's data to the activeUsers list.
        userList.setItems(activeUsers);


        /* RoomList SetUp */
        // Bind the table's data to the rooms list.
        roomList.setItems(rooms);

        roomList.setRowFactory( tv -> {
            TableRow<Room> row = new TableRow<>();
            row.setOnMouseClicked(event -> {
                if (event.getClickCount() == 2 && (!row.isEmpty()) ) {
                    Room room = row.getItem();
                    currentRoom = room;
                    currentRoom.resetLastMessageId();

                    System.out.println(currentRoom.getId());

                    // todo: refactor
                    messageList.getChildren().clear();
                    (new RefreshMessages()).run();
                }
            });
            return row ;
        });

        // Bind the username column to the "name" property from a User object.
        roomName.setCellValueFactory(new PropertyValueFactory<Room, String>("name"));



        /* Recurring GETs */
        // Check for new messages every 3 seconds.
        timer.schedule(new RefreshMessages(), 0, 3000);

        // Check the currently active users every 10 seconds.
        timer.schedule(new RefreshUsers(), 0, 10000);

        // Check the room list every hour.
        timer.schedule(new RefreshRooms(), 0, 60 * 60 * 1000);


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

            // Indicate that we shouldn't refetch the archive in the future.
            currentRoom.setArchiveFetched(true);

            if (messages.isArray()) {
                for (final JsonNode message : messages) {
                    // Tell the room about the new message.
                    currentRoom.addNewMessage(message);

                    // Parse the user object.
                    int userId = message.get("userId").asInt();
                    User user = getUser(userId);

                    // Create the username for display.
                    final Text userName = new Text(user.getName());
                    userName.setFont(Font.font(null, FontWeight.BOLD, -1));

                    // Parse the message time.
                    Calendar c = Calendar.getInstance(Locale.getDefault());
                    c.setTimeInMillis(message.get("time").asLong() * 1000);
                    final Text messageTime = new Text((new SimpleDateFormat()).format(c.getTime()));

                    // Create the message text for display.
                    final Text messageText = new Text(message.get("text").asText());

                    // Add the new message once we're back on the JavaFX thread.
                    Platform.runLater(new Runnable() {
                        @Override
                        public void run() {

                            TextFlow t = new TextFlow();
                            TextFlow t2;
                            if(MessengerAPI.user.getId() == userId) {
                                t2 = new TextFlow(messageText, new Text("  "), user.getAvatarImageView());
                                t.setTextAlignment(RIGHT);
                            }

                            else{
                                t2 = new TextFlow(user.getAvatarImageView(), userName, new Text(": "), messageText);
                                t.setTextAlignment(LEFT);
                            }
                            t2.setMaxWidth(messageList.getWidth() * .6);
                            t.getChildren().add(t2);
                            messageList.getChildren().add(t);
                            messageListScroll.setVvalue(1.0);

                        }
                    });

                }
            } else {
                GUIDisplay.alert("Bad response from getMessages.");
            }
        }
    }

    /**
     * Runner to check for active users .
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

    class RefreshRooms extends TimerTask {
        public void run() {
            JsonNode apiRooms = GUIDisplay.api.getRooms();
            System.out.println(rooms);
            rooms.clear();

            if (apiRooms.isObject()) {
                for (final JsonNode room : apiRooms) {
                    System.out.println(room);
                    rooms.add(new Room(room.get("id").asInt(), room.get("name").asText()));
                }
            }
        }
    }
}