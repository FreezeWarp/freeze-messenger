/**
 * Created by joseph on 28/08/17.
 */

import com.fasterxml.jackson.databind.JsonNode;
import javafx.application.Application;
import javafx.application.Platform;
import javafx.concurrent.Worker;
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
import javafx.scene.web.WebEngine;
import javafx.scene.web.WebView;
import javafx.stage.Stage;

import java.io.*;
import java.net.URL;
import java.net.URLConnection;
import java.net.URLStreamHandler;
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


    /**
     * A file object corresponding to the persistence file, whether or whether not it exists.
     */
    private static final File persistenceFile = new File(new File(System.getProperty("user.dir")), "messenger-persistence.bin");



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


    // Current Stage
    public Stage primaryStage;


    /**
     * The textbox used when typing a new message.
     */
    TextField newMessageTextField = new TextField();

    /**
     * The factory used to monitor construct URL requests. It is used here to monitor URL requests.
     */
    URLInterceptorFactory urlInterceptorFactory = new URLInterceptorFactory(null);


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


    public Stage getPrimaryStage() {
        return primaryStage;
    }



    /*################################
     * JavaFX Entry-Point
     *###############################*/

    /**
     * Entry-point for JavaFX.
     *
     * @param primaryStage Set by JavaFx.
     */
    public void mainScene(Stage primaryStage) {
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
            ex.printStackTrace();
        }
    }

    public void loginScene(Stage primaryStage) {
        try {
            /* Load the Login Scene FXML */
            FXMLLoader loader = new FXMLLoader(getClass().getResource("LoginGUI.fxml"));
            Parent root = loader.load();
            LoginGUI loginController = loader.getController();


            /* Process Normal Logins on Button Press */
            loginController.loginButton.setOnAction(new EventHandler<ActionEvent>() {
                @Override
                public void handle(ActionEvent e) {
                    if (!GUIDisplay.api.login(loginController.username.getText(), loginController.password.getText())) {
                        GUIDisplay.alert("Login failed.");
                    } else {
                        api.getUser().setPassword(loginController.password.getText());

                        primaryStage.hide();
                        mainScene(primaryStage);
                    }
                }
            });


            /* Process Google Logins on Button Press */
            loginController.googleButton.setOnAction(new EventHandler<ActionEvent>() {
                /**
                 * The browser used to display web pages.
                 */
                WebView browser = new WebView();

                @Override
                public void handle(ActionEvent e) {
                    System.setProperty("sun.net.http.allowRestrictedHeaders", "true"); // required for Google logins to work
                    URL.setURLStreamHandlerFactory(urlInterceptorFactory);

                    // Watch for redirects.
                    urlInterceptorFactory.setCallback(url -> {
                        if (url.toString().startsWith(GUIDisplay.api.getServerUrl() + "?sessionHash=")) {
                            String hash = url.toString().replace(GUIDisplay.api.getServerUrl() + "?sessionHash=", "");

                            Platform.runLater(() -> {
                                browser.getEngine().load(null); // Clear the webEngine from memory.
                                urlInterceptorFactory.setCallback(null); // Unset the URL interception callback.

                                if (api.login(hash)) {
                                    mainScene(primaryStage); // If the login is successful, switch to the main scene.
                                } else {
                                    loginScene(primaryStage); // If the login fails, return to the login scene.
                                }
                            });
                        }
                    });

                    // Load the googleLogin webpage.
                    browser.getEngine().load(GUIDisplay.api.getServerUrl() + "validate.php?integrationMethod=google");

                    // Display the browser in a new scene.
                    Scene scene = new Scene(browser);
                    primaryStage.setResizable(true);
                    primaryStage.setMinWidth(800);
                    primaryStage.setMinHeight(800);
                    primaryStage.setScene(scene);
                }
            });


            // Display the login scene.
            Scene scene = new Scene(root);
            primaryStage.setResizable(false);
            primaryStage.setScene(scene);
            primaryStage.setTitle("FlexChat: Welcome");
            primaryStage.show();

        } catch (Exception ex) {
            System.out.println("Exception: " + ex);
            ex.printStackTrace();
        }
    }

    public void start(Stage primaryStage) {
        this.primaryStage = primaryStage;

        api = new MessengerAPI("http://josephtparsons.com/messenger/");

        try {
            FileInputStream in = new FileInputStream(persistenceFile);
            ObjectInputStream ois = new ObjectInputStream(in);

            api.user = (LoggedInUser) ois.readObject();
        } catch (Exception ex) {
            System.out.println("Exception: " + ex);
            ex.printStackTrace();
        }

        if (api.user.getRefreshToken().length() > 0 && api.loginRefresh(api.user.getRefreshToken())) {
            mainScene(primaryStage);
        }
        else if (api.user.getPassword().length() > 0 && api.login(api.user.getName(), api.user.getPassword())) {
            mainScene(primaryStage);
        }
        else {
            loginScene(primaryStage);
        }
    }

    @Override
    public void stop(){
        try {
            FileOutputStream out = new FileOutputStream(persistenceFile);
            ObjectOutputStream oos = new ObjectOutputStream(out);

            oos.writeObject(api.user);
            oos.flush();
        } catch (Exception ex) {
            System.err.println("Saving data failed: " + ex);
            ex.printStackTrace();
        }

        MainPane.timer.cancel();
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
