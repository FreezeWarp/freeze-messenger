/**
 * Created by joseph on 28/08/17.
 */
import javafx.application.Application;
import javafx.event.ActionEvent;
import javafx.event.EventHandler;
import javafx.geometry.Pos;
import javafx.scene.Scene;
import javafx.scene.control.Alert;
import javafx.scene.control.Button;
import javafx.scene.control.Label;
import javafx.scene.control.TextField;
import javafx.scene.layout.HBox;
import javafx.scene.layout.VBox;
import javafx.stage.Stage;

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
    private static StrongMap<String, Label> labels;
    private static StrongMap<String, Button> buttons;
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
        // Stores the overall frame, composed of the main frame and the side panel.
        VBox mainMessageInterface = new VBox(25);


        // Stores the main frame, composed of the messages frame and the send message frame.
        HBox mainFrame = new HBox(5);


        // Stores the config frame, composed of the labels and buttons for setting certain configuration at run-time.
        VBox messagesFrame = new VBox(10);


        // Stores the status frame, composed of status labels for temperature, etc.
        VBox sendMessageFrame = new VBox(10);


        // Add the messages, send message frames to the main frame
        mainFrame.getChildren().addAll(messagesFrame, sendMessageFrame);


        // Stores side frame
        HBox sideFrame = new HBox(10);


        // Add main frame, side frame to overall frame.
        mainMessageInterface.getChildren().addAll(sideFrame, mainFrame);


        // Center stuff.
        mainMessageInterface.setAlignment(Pos.CENTER);
        mainFrame.setAlignment(Pos.CENTER);
        messagesFrame.setAlignment(Pos.CENTER);
        sendMessageFrame.setAlignment(Pos.CENTER);
        sideFrame.setAlignment(Pos.CENTER);


        // Add the overall frame to a scene, add the scene to the primary stage, then display the stage.
        Scene scene = new Scene(mainMessageInterface);
        primaryStage.setMinWidth(600);
        primaryStage.setMinHeight(400);
        primaryStage.setScene(scene);
        primaryStage.setTitle("Message Interface");
        primaryStage.show();



        // Open/Close Door
        //buttons.get("").setOnAction(new ButtonHandler());
        //buttons.get("").setOnAction(new ButtonHandler());



        /* Initialise Our Objects */
        startSimulation();
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



    /*################################
     * Entry-Point for Stimulation
     *###############################*/

    /**
     * Constructs the fridge, freezer, room, and display references.
     */
    public static void startSimulation() {
        MessengerAPI api = new MessengerAPI("http://localhost/messenger/");
        if (!api.login("admin", "admin")) {
            alert("Login failed.");
        }
        else {
            alert("Login successful, maybe. Session token: " + api.getSessionToken());
        }
    }
}
