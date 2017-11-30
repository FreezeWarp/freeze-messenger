import com.fasterxml.jackson.databind.JsonNode;
import javafx.application.Platform;
import javafx.beans.binding.Bindings;
import javafx.beans.property.ReadOnlyBooleanProperty;
import javafx.beans.property.SimpleObjectProperty;
import javafx.beans.value.ChangeListener;
import javafx.beans.value.ObservableValue;
import javafx.collections.FXCollections;
import javafx.collections.ObservableList;
import javafx.event.ActionEvent;
import javafx.event.EventHandler;
import javafx.fxml.FXML;
import javafx.fxml.FXMLLoader;
import javafx.geometry.Pos;
import javafx.scene.Node;
import javafx.scene.Parent;
import javafx.scene.Scene;
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
import javafx.stage.Modality;
import javafx.stage.Stage;
import javafx.stage.Window;
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
/*   Help */
    @FXML
    public MenuButton helpButton;

    @FXML
    public MenuItem helpFAQ;

    @FXML
    public MenuItem helpTips;
/*   Settings  */
    @FXML
    public MenuButton settingsButton;

    @FXML
    public MenuItem settingsList;

    @FXML
    public MenuItem settingsLogout;



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

        /* Fix help button menu width to help button's width. */
        // This is such a stupidly hacky solution.
        helpButton.showingProperty().addListener(new ChangeListener<Boolean>() {
            @Override
            public void changed(ObservableValue<? extends Boolean> observable, Boolean oldValue, Boolean newValue) {
                if (newValue) {
                    helpTips.getParentPopup().styleProperty().setValue("-fx-min-width: " + helpButton.widthProperty().get() + "px");
                }
            }
        });

        settingsButton.showingProperty().addListener(new ChangeListener<Boolean>() {
            @Override
            public void changed(ObservableValue<? extends Boolean> observable, Boolean oldValue, Boolean newValue) {
                if (newValue) {
                    settingsList.getParentPopup().styleProperty().setValue("-fx-min-width: " + settingsButton.widthProperty().get() + "px");
                }
            }
        });

        settingsLogout.setOnAction(new EventHandler<ActionEvent>() {
            @Override
            public void handle(ActionEvent e) {
                MessengerAPI.user = new LoggedInUser();
                Platform.exit();
            }
        });


        /* Scroll The message list when its height changes (typically because of a new message) */
        messageList.heightProperty().addListener(new ChangeListener() {
            @Override
            public void changed(ObservableValue observable, Object oldvalue, Object newValue) {
                messageListScroll.setVvalue(1.0);
                ;
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

        roomList.setRowFactory(tv -> {
            TableRow<Room> row = new TableRow<>();
            row.setOnMouseClicked(event -> {
                if (event.getClickCount() == 2 && (!row.isEmpty())) {
                    Room room = row.getItem();
                    currentRoom = room;
                    currentRoom.resetLastMessageId();

                    System.out.println(currentRoom.getId());

                    // todo: refactor
                    messageList.getChildren().clear();
                    (new RefreshMessages()).run();
                    (new RefreshUsers()).run();
                }
            });
            return row;
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
                if (event.isShiftDown()) {
                    newMessageText.setText(newMessageText.getText() + System.getProperty("line.separator")); // Append new line.
                    newMessageText.end(); // Set cursor to end.
                } else {
                    String text = newMessageText.getText();
                    GUIDisplay.api.sendMessage(currentRoom.getId(), text);

                    newMessageText.setText("");

                    event.consume(); // don't show enter key
                }
            }
        });
/* Help List Items */
        helpFAQ.setOnAction(new EventHandler<ActionEvent>() {
            @Override
            public void handle(ActionEvent e) {
                try {
                    System.out.println(getClass().getClassLoader().getResource("HelpFAQPane.fxml"));
                    Parent root = FXMLLoader.load(getClass().getClassLoader().getResource("HelpFAQPane.fxml"));
                    Stage stage = new Stage();
                    stage.setTitle("Help: F.A.Q.");
                    stage.setScene(new Scene(root, 450, 450));
                    stage.initModality(Modality.APPLICATION_MODAL);
                    stage.show();
                } catch (Exception ex) {
                    System.out.println("Exception: " + ex);
                    ex.printStackTrace();
                }
            }
        });

        helpTips.setOnAction(new EventHandler<ActionEvent>() {
            @Override
            public void handle(ActionEvent e) {
                try {
                    System.out.println(getClass().getClassLoader().getResource("HelpTipsPane.fxml"));
                    Parent root = FXMLLoader.load(getClass().getClassLoader().getResource("HelpTipsPane.fxml"));
                    Stage stage = new Stage();
                    stage.setTitle("Help: Tips");
                    stage.setScene(new Scene(root, 450, 450));
                    stage.initModality(Modality.APPLICATION_MODAL);
                    stage.show();
                } catch (Exception ex) {
                    System.out.println("Exception: " + ex);
                    ex.printStackTrace();
                }
            }
        });
/* Settings List Items */
        settingsList.setOnAction(new EventHandler<ActionEvent>() {
            @Override
            public void handle(ActionEvent e) {
                try {
                    System.out.println(getClass().getClassLoader().getResource("SettingsListPane.fxml"));
                    Parent root = FXMLLoader.load(getClass().getClassLoader().getResource("SettingsListPane.fxml"));
                    Stage stage = new Stage();
                    stage.setTitle("Settings");
                    stage.setScene(new Scene(root, 450, 450));
                    stage.initModality(Modality.APPLICATION_MODAL);
                    stage.show();
                } catch (Exception ex) {
                    System.out.println("Exception: " + ex);
                    ex.printStackTrace();
                }
            }
        });
/*
        settingsLogout.setOnAction(new EventHandler<ActionEvent>() { @Override public void handle(ActionEvent e) {
            try {

                System.out.println(getClass().getClassLoader().getResource("LoginGUI.fxml"));
                Parent root = FXMLLoader.load(getClass().getClassLoader().getResource("LoginGUI.fxml"));
                Stage stage = new Stage();
                stage.setTitle("Help");
                stage.setScene(new Scene(root, 450, 450));
                stage.initModality(Modality.APPLICATION_MODAL);
                stage.show();
            } catch (Exception ex) {
                System.out.println("Exception: " + ex);
                ex.printStackTrace();
            }
        } });
*/
    }
    /**
     * Runner to check for new messages.
     */
    class RefreshMessages extends TimerTask {



        public void run() {
            JsonNode messages = GUIDisplay.api.getMessages(currentRoom.getId(), currentRoom.getLastMessageId()); // currentRoom.isArchiveFetched

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
                                t2 = new TextFlow(messageText, new Text("\n"));
                                t.setTextAlignment(RIGHT);
                            }

                            else{
                                t2 = new TextFlow(user.getAvatarImageView(), new Text(" "), userName, new Text(": "), messageText, new Text("\n"));
                                t.setTextAlignment(LEFT);
                            }
                            t2.maxWidthProperty().bind(messageList.widthProperty().multiply(.6));
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
            JsonNode usersLocal = GUIDisplay.api.getActiveUsers(MainPane.this.currentRoom.getId());
            JsonNode usersAll = GUIDisplay.api.getActiveUsers();

            System.out.println(users);
            activeUsers.clear();

            if (usersLocal.isObject()) {
                for (final JsonNode user : usersLocal) {
                    activeUsers.add(getUser(user.get("id").asInt()));
                }
            }

            if (usersAll.isObject()) {
                for (final JsonNode user : usersAll) {
                    User userObj = getUser(user.get("id").asInt());

                    if (!activeUsers.contains(userObj)) {
                        activeUsers.add(userObj);
                    }
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