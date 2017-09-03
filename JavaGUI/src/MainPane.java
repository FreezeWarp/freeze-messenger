import com.fasterxml.jackson.databind.JsonNode;
import javafx.application.Platform;
import javafx.beans.binding.Bindings;
import javafx.event.ActionEvent;
import javafx.event.EventHandler;
import javafx.fxml.FXML;
import javafx.geometry.Pos;
import javafx.scene.control.Button;
import javafx.scene.control.ScrollPane;
import javafx.scene.control.TextArea;
import javafx.scene.control.TextField;
import javafx.scene.input.KeyCode;
import javafx.scene.input.KeyEvent;
import javafx.scene.layout.Priority;
import javafx.scene.layout.VBox;
import javafx.scene.text.Font;
import javafx.scene.text.FontWeight;
import javafx.scene.text.Text;
import javafx.scene.text.TextFlow;

import java.text.SimpleDateFormat;
import java.util.Calendar;
import java.util.Locale;
import java.util.Timer;
import java.util.TimerTask;

public class MainPane {
    @FXML
    public VBox messageList;

    @FXML
    public TextArea newMessageText;

    @FXML
    public ScrollPane messageListScroll;

    /**
     * This is the current room we have loaded and are getting messages for.
     * In the future, this will be an array of multiple rooms.
     */
    Room currentRoom = new Room(2);


    public void initialize() {
        Timer timer = new Timer();
        timer.schedule(new RefreshMessages(), 0, 1000);

        // align messages to bottom
        messageListScroll.setFitToHeight(true);
        messageList.minHeightProperty().bind(Bindings.createDoubleBinding(() -> messageListScroll.getViewportBounds().getHeight(), messageListScroll.viewportBoundsProperty()));

        // todo: shift+enter
        newMessageText.setOnKeyPressed(event -> {
            if(event.getCode() == KeyCode.ENTER) {
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
                    System.out.println(message);
                    currentRoom.addNewMessage(message);

                    final Text userName = new Text("temp");
                    userName.setFont(Font.font(null, FontWeight.BOLD, -1));

                    Calendar c = Calendar.getInstance(Locale.getDefault());
                    c.setTimeInMillis(message.get("time").asLong() * 1000);
                    final Text messageTime = new Text((new SimpleDateFormat()).format(c.getTime()));
                    final Text messageText = new Text(message.get("text").asText());

                    Platform.runLater(new Runnable() {
                        @Override
                        public void run() {
                            messageList.getChildren().add(new TextFlow(userName, new Text(" @ "), messageTime, new Text(": "), messageText));
                        }
                    });

                }
            }
            else {
                GUIDisplay.alert("Bad response from getMessages.");
            }
        }
    }
}