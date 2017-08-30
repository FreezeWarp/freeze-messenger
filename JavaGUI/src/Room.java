import com.fasterxml.jackson.databind.JsonNode;
import javafx.application.Platform;
import javafx.scene.web.WebEngine;
import javafx.scene.web.WebView;
import org.w3c.dom.Element;

/**
 * This stores the current working data for a single room's messages.
 */
public class Room {
    /**
     * The ID of the room.
     */
    int id;

    /**
     * The last (that is, greatest) message ID received in the current room.
     */
    int lastMessageId;

    /**
     * If the message archive has already been fetched for this room.
     */
    boolean archiveFetched;


    /**
     * The web view used to render the room's messages.
     */
    WebView messagesWebView = new WebView();

    /**
     * The web engine used to render the room's messages.
     */
    final WebEngine webEngine = messagesWebView.getEngine();


    public Room(int id) {
        this.id = id;
        webEngine.loadContent("<html><body><div id=\"messages\"></div><span style=\"font-weight: bold\">Hi!</span></body></html>");
    }


    public int getId() {
        return id;
    }

    public int getLastMessageId() {
        return lastMessageId;
    }

    public void setLastMessageId(int lastMessageId) {
        if (lastMessageId > this.lastMessageId) {
            this.lastMessageId = lastMessageId;
        }
    }

    public boolean isArchiveFetched() {
        return archiveFetched;
    }

    public void setArchiveFetched(boolean archiveFetched) {
        this.archiveFetched = archiveFetched;
    }

    public WebView getMessagesWebView() {
        return messagesWebView;
    }


    public void addNewMessage(JsonNode message) {
        this.setLastMessageId(message.get("messageId").intValue());

        Element wholeMessage = webEngine.getDocument().createElement("div");
        Element userName = webEngine.getDocument().createElement("strong");
        userName.setAttribute("css", "font-weight: bold;");
        Element messageTime = webEngine.getDocument().createElement("span");
        Element messageText = webEngine.getDocument().createElement("span");

        userName.setTextContent("temporary");
        messageTime.setTextContent(message.get("messageTime").textValue());
        messageText.setTextContent(message.get("messageText").textValue());

        wholeMessage.setAttribute("messageId", message.get("messageId").asText());
        wholeMessage.appendChild(userName);
        wholeMessage.appendChild(messageTime);
        wholeMessage.appendChild(messageText);

        Platform.runLater(new Runnable() {
            @Override public void run() {
                webEngine.getDocument().getElementById("messages").appendChild(wholeMessage);
                webEngine.reload();
                System.out.println((String) webEngine.executeScript("document.documentElement.outerHTML"));
            }
        });
    }
}
