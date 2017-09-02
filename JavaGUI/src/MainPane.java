import com.fasterxml.jackson.databind.JsonNode;
import javafx.application.Platform;
import javafx.event.ActionEvent;
import javafx.event.EventHandler;
import javafx.fxml.FXML;
import javafx.scene.control.Button;
import javafx.scene.control.TextArea;
import javafx.scene.control.TextField;
import javafx.scene.input.KeyCode;
import javafx.scene.input.KeyEvent;
import javafx.scene.layout.VBox;
import javafx.scene.text.Font;
import javafx.scene.text.FontWeight;
import javafx.scene.text.Text;
import javafx.scene.text.TextFlow;

import java.util.Timer;
import java.util.TimerTask;

public class MainPane {
    @FXML
    public VBox messageList;

    @FXML
    public TextArea newMessageText;

    /**
     * This is the current room we have loaded and are getting messages for.
     * In the future, this will be an array of multiple rooms.
     */
    Room currentRoom = new Room(1);


    public void initialize() {
        Timer timer = new Timer();
        timer.schedule(new RefreshMessages(), 0, 1000);

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
                    final Text messageTime = new Text(message.get("messageTime").asText());
                    final Text messageText = new Text(message.get("messageText").asText());

                    Platform.runLater(new Runnable() {
                        @Override
                        public void run() {
                            messageList.getChildren().add(0, new TextFlow(userName, new Text(" @ "), messageTime, new Text(": "), messageText));
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