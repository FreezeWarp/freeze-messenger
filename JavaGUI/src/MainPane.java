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

    ObservableList<User> activeUsers =  FXCollections.observableArrayList();

    public static Map<Integer, User> users = new HashMap<>();
    public static Map<String, Image> images = new HashMap<>();

    public static ImageView getAvatar(String avatar) {
        if (!images.containsKey(avatar)) {
            images.put(avatar, new Image(avatar, 24, 24, false, true));
        }

        return ImageViewBuilder.create()
                .image(images.get(avatar))
                .build();
    }

    public User getUser(int userId) {
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


    static Timer timer = new Timer();


    public void initialize() {
        // UserList SetUp
        username.setCellValueFactory(new PropertyValueFactory<User, String>("name"));
        avatar.setCellValueFactory(new PropertyValueFactory<User, String>("avatarImageView"));
        userList.setItems(activeUsers);

        // Recurring GETs
        timer.schedule(new RefreshMessages(), 0, 3000);
        timer.schedule(new RefreshUsers(), 0, 10000);

        // align messages to bottom
        messageListScroll.setHbarPolicy(ScrollPane.ScrollBarPolicy.NEVER);
        messageListScroll.setFitToWidth(true);

        messageList.minHeightProperty().bind(Bindings.createDoubleBinding(() -> messageListScroll.getViewportBounds().getHeight(), messageListScroll.viewportBoundsProperty()));

        // Send Message Bind
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