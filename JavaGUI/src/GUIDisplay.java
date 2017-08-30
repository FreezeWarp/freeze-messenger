/**
 * Created by joseph on 28/08/17.
 */

import com.fasterxml.jackson.databind.JsonNode;
import javafx.application.Application;
import javafx.application.Platform;
import javafx.event.ActionEvent;
import javafx.event.EventHandler;
import javafx.geometry.Pos;
import javafx.scene.Scene;
import javafx.scene.control.*;
import javafx.scene.layout.HBox;
import javafx.scene.layout.VBox;
import javafx.scene.text.Font;
import javafx.scene.text.FontWeight;
import javafx.scene.text.Text;
import javafx.scene.text.TextFlow;
import javafx.stage.Stage;

import java.util.Timer;
import java.util.TimerTask;

/**
 * A GUI interface to simulate two coolers, a refridgerator and freezer.
 *
 * Key Design Notes:
 ** We are using JavaFX's listeners (using Properties) instead of custom listener classes. This is probably a good idea, because those methods exist for a reason and are becoming the default choice for GUIs in the Java world. They work much like the Observer pattern.
 *
 * @author Eric Fulwiler, Daniel Johnson, Joseph T. Parsons, Cory Stadther
 * @version 2.0
 * @since   2017-August-05
 */
public class GUIDisplay extends Application {
    /**
     * This is the object for making API calls.
     * It will be instantiated once a server URL is known.
     */
    MessengerAPI api;

    /**
     * This is the current room we have loaded and are getting messages for.
     * In the future, this will be an array of multiple rooms.
     */
    Room currentRoom = new Room(1);


    // Stores the overall frame, composed of the main frame and the side panel.
    VBox mainMessageInterface = new VBox(25);


    // Stores the main frame, composed of the messages frame and the send message frame.
    VBox mainFrame = new VBox(5);


    // Stores the config frame, composed of the labels and buttons for setting certain configuration at run-time.
    VBox messagesFrame = new VBox(10);


    // Stores the bit where new messages are typed.
    VBox sendMessageFrame = new VBox(10);


    // Stores side frame
    HBox sideFrame = new HBox(10);


    /**
     * The textbox used when typing a new message.
     */
    TextField newMessageTextField = new TextField();


    /*################################
     * Program Entry-Point
     *###############################*/

    /**
     * Entry-point for program. Opens config from file in first argument and then launches JavaFX.
     *
     * @param args Command-line arguments. The first one will be used as a configuration file, if available.
     */
    public static void main(String[] args) {
        Application.launch(args);
    }




    /*################################
     * JavaFX Properties
     *###############################*/
    /**
     * This is a map of labels that can be obtained dynamically by their name.
     */
    private static StrongMap<String, Label> labels;


    /**
     * This is a map of buttons that can be obtained dynamically by their name.
     */
    private static StrongMap<String, Button> buttons;


    /**
     * This is a map of text fields that can be obtained dynamically by their name.
     */
    private static StrongMap<String, TextField> textfields;



    /*################################
     * JavaFX Entry-Point
     *###############################*/

    /**
     * Entry-point for JavaFX.
     *
     * @param primaryStage Set by JavaFx.
     */
    public void start(Stage primaryStage) {
        labels = new StrongMap<String, Label>(
                new String[] {
                },
                new Label[] {
                }
        );

        buttons = new StrongMap<String, Button>(
                new String[] {
                },
                new Button[] {
                }
        );

        textfields = new StrongMap<String, TextField>(
                new String[] {
                },
                new TextField[] {
                }
        );



        /* Build Our Interface */
        // Make messagesFrame scroll.
        ScrollPane messagesFrameScrollable = new ScrollPane();
        messagesFrameScrollable.setContent(messagesFrame);


        // Add new message text box
        sendMessageFrame.getChildren().add(newMessageTextField);


        // Add the messages, send message frames to the main frame
        mainFrame.getChildren().addAll(messagesFrameScrollable, sendMessageFrame);


        // Add main frame, side frame to overall frame.
        mainMessageInterface.getChildren().addAll(sideFrame, mainFrame);


        // Sizes
        mainMessageInterface.setFillWidth(true);
        sideFrame.setMinWidth(200);
        sideFrame.setPrefWidth(400);
        sideFrame.setFillHeight(true);
        mainFrame.setMinWidth(400);
        sendMessageFrame.setPrefHeight(100);
        sendMessageFrame.setFillWidth(true);
        messagesFrameScrollable.setFitToHeight(true);
        messagesFrameScrollable.setFitToWidth(true);


        // Center stuff.
        mainMessageInterface.setAlignment(Pos.CENTER);
        mainFrame.setAlignment(Pos.CENTER);
        messagesFrame.setAlignment(Pos.CENTER);
        sendMessageFrame.setAlignment(Pos.CENTER);
        sideFrame.setAlignment(Pos.CENTER);


        // Add the overall frame to a scene, add the scene to the primary stage, then display the stage.
        Scene scene = new Scene(mainMessageInterface);
        primaryStage.setResizable(true);
        primaryStage.setMinWidth(600);
        primaryStage.setMinHeight(400);
        primaryStage.setScene(scene);
        primaryStage.setTitle("Message Interface");
        primaryStage.show();



        // Open/Close Door
        //buttons.get("").setOnAction(new ButtonHandler());
        //buttons.get("").setOnAction(new ButtonHandler());



        api = new MessengerAPI("http://localhost/messenger/");
        if (!api.login("admin", "admin")) {
            alert("Login failed.");
        }
        else {
            //alert("Login successful, maybe. Session token: " + api.getSessionToken());

            Timer timer = new Timer();
            timer.schedule(new RefreshMessages(), 0, 1000);
        }
    }




    /*################################
     * Event Handlers
     *###############################*/

    /**
     * Handles all general button presses in the program.
     * (Specific button press handlers defined elsewhere.)
     */
    class ButtonHandler implements EventHandler<ActionEvent> {
        public void handle(ActionEvent event) {
        }
    }




    /*################################
     * JavaFX Helpers
     *###############################*/

    /**
     * Display a generic error-like alert message.
     *
     * @param text The text to display.
     */
    public static void alert(String text) {
        Label label = new Label(text);
        label.setWrapText(true);

        Alert dialog = new Alert(Alert.AlertType.ERROR);
        dialog.setHeaderText("Error");
        dialog.getDialogPane().setContent(label);
        dialog.showAndWait();
    }



    class RefreshMessages extends TimerTask {
        public void run() {
            JsonNode messages = api.getMessages(currentRoom.getId(), currentRoom.getLastMessageId(), !currentRoom.isArchiveFetched());
            currentRoom.setArchiveFetched(true);

            if (messages.isArray()) {
                for (final JsonNode message : messages) {
                    JsonNode messageTemp = message.get("messageData"); // TODO: remove messageData node
                    System.out.println(message);
                    currentRoom.addNewMessage(messageTemp);

                    Text userName = new Text("temp");
                    userName.setFont(Font.font(null, FontWeight.BOLD, -1));
                    Text messageTime = new Text(messageTemp.get("messageTime").asText());
                    Text messageText = new Text(messageTemp.get("messageText").asText());

                    Platform.runLater(new Runnable() {
                        @Override
                        public void run() {
                            messagesFrame.getChildren().add(0, new TextFlow(userName, new Text(" @ "), messageTime, new Text(": "), messageText));
                        }
                    });

                }
            }
            else {
                alert("Bad response from getMessages.");
            }
        }
    }
}
