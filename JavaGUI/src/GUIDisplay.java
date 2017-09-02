/**
 * Created by joseph on 28/08/17.
 */

import com.fasterxml.jackson.databind.JsonNode;
import javafx.application.Application;
import javafx.application.Platform;
import javafx.event.ActionEvent;
import javafx.event.EventHandler;
import javafx.fxml.FXMLLoader;
import javafx.geometry.Insets;
import javafx.geometry.Pos;
import javafx.scene.Parent;
import javafx.scene.Scene;
import javafx.scene.control.*;
import javafx.scene.layout.GridPane;
import javafx.scene.layout.HBox;
import javafx.scene.layout.VBox;
import javafx.scene.paint.Color;
import javafx.scene.text.Font;
import javafx.scene.text.FontWeight;
import javafx.scene.text.Text;
import javafx.scene.text.TextFlow;
import javafx.stage.Stage;

import java.util.Timer;
import java.util.TimerTask;

/**
 * Basic driver for Messenger.
 *
 * @author
 * @version 1.0
 */
public class GUIDisplay extends Application {
    /**
     * This is the object for making API calls.
     * It will be instantiated once a server URL is known.
     */
    static MessengerAPI api;


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
    public void mainScene(Stage primaryStage) {
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
        try {
            Parent root = FXMLLoader.load(getClass().getResource("MainPane.fxml"));

            Scene scene = new Scene(root);
            primaryStage.setResizable(true);
            primaryStage.setMinWidth(600);
            primaryStage.setMinHeight(400);
            primaryStage.setScene(scene);
            primaryStage.setTitle("Message Interface");
            primaryStage.show();
        } catch (Exception ex) {
            System.out.println("Exception: " + ex);
        }



        // Open/Close Door
        //buttons.get("").setOnAction(new ButtonHandler());
        //buttons.get("").setOnAction(new ButtonHandler());
    }

    public void start(Stage primaryStage) {
        primaryStage.setTitle("JavaFX Welcome");
        GridPane grid = new GridPane();
        grid.setAlignment(Pos.CENTER);
        grid.setHgap(10);
        grid.setVgap(10);
        grid.setPadding(new Insets(25, 25, 25, 25));

        Text scenetitle = new Text("Welcome");
        scenetitle.setFont(Font.font("Tahoma", FontWeight.NORMAL, 20));
        grid.add(scenetitle, 0, 0, 2, 1);

        Label userName = new Label("User Name:");
        grid.add(userName, 0, 1);

        TextField userTextField = new TextField();
        grid.add(userTextField, 1, 1);

        Label pw = new Label("Password:");
        grid.add(pw, 0, 2);

        PasswordField pwBox = new PasswordField();
        grid.add(pwBox, 1, 2);

        Button btn = new Button("Sign in");
        HBox hbBtn = new HBox(10);
        hbBtn.setAlignment(Pos.BOTTOM_RIGHT);
        hbBtn.getChildren().add(btn);
        grid.add(hbBtn, 1, 4);

        final Text actiontarget = new Text();
        grid.add(actiontarget, 1, 6);

        btn.setOnAction(new EventHandler<ActionEvent>() {
            @Override
            public void handle(ActionEvent e) {
                api = new MessengerAPI("http://localhost/messenger/");
                if (!api.login(userTextField.getText(), pwBox.getText())) {
                    alert("Login failed.");
                }
                else {
                    System.out.println("Login successful, maybe. Session token: " + api.getSessionToken());

                    primaryStage.hide();
                    mainScene(primaryStage);
                }
            }
        });

        Scene scene = new Scene(grid, 300, 275);
        primaryStage.setScene(scene);
        primaryStage.show();
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
}
