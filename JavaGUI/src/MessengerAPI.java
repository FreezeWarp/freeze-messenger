import com.fasterxml.jackson.databind.JsonNode;
import com.fasterxml.jackson.databind.ObjectMapper;
import org.apache.http.HttpEntity;
import org.apache.http.HttpResponse;
import org.apache.http.StatusLine;
import org.apache.http.client.HttpResponseException;
import org.apache.http.client.ResponseHandler;
import org.apache.http.client.fluent.Request;
import org.apache.http.entity.ContentType;
import org.apache.http.util.EntityUtils;

import java.io.IOException;
import java.util.concurrent.Callable;

/**
 * Created by joseph on 28/08/17.
 */
public class MessengerAPI {
    /**
     * The client ID of this application. Currently WebPro, since that's an available client ID.
     */
    private String clientId = "WebPro";

    /**
     * The messenger server URL.
     */
    private String serverUrl = "";

    /**
     * JSON mapper object.
     */
    private ObjectMapper mapper = new ObjectMapper();

    /**
     * An exception handler to use when HTTP errors occur.
     */
    private Callable exceptionHandler;

    ResponseHandler<String> responseHandler = new ResponseHandler<String>() {
        public String handleResponse(HttpResponse response) throws IOException {
            StatusLine statusLine = response.getStatusLine();
            HttpEntity entity = response.getEntity();

            if (statusLine.getStatusCode() >= 300) {
                System.err.println("Bad HTTP reponse. Data: ");
                System.err.println(EntityUtils.toString(entity));

                throw new HttpResponseException(
                        statusLine.getStatusCode(),
                        statusLine.getReasonPhrase());
            }

            return EntityUtils.toString(entity);
        }
    };

    /**
     * The session token used to make all requests. Will be set by login().
     */
    private String sessionToken;

    /**
     * The user's current permissions.
     */
    private UserPermissions permissions;


    /**
     * Initialise API with server URL.
     * @param serverUrl {@link MessengerAPI#serverUrl}
     */
    public MessengerAPI(String serverUrl) {
        this.serverUrl = serverUrl;
    }


    /**
     * Login and obtain user information. The session token will be stored to {@link MessengerAPI#sessionToken},...
     * @param username The username to login with.
     * @param password The password to login with.
     * @throws IOException
     */
    public boolean login(String username, String password) {
        try {
            JsonNode json = httpPOST("validate.php","client_id=" + clientId + "&grant_type=password&username=" + username + "&password=" + password).get("login");
            sessionToken = json.get("access_token").asText();
            permissions = mapper.treeToValue(json.get("permissions"), UserPermissions.class);
        } catch (Exception ex) {
            System.err.println("Exception: " + ex);
            return false;
        }

        return true;
    }


    /**
     * Performs simple HTTP GET request, returning JSON body.
     * @param path The URL (relative to {@link MessengerAPI#serverUrl} to use.
     * @return The JSON response to the request.
     * @throws IOException
     */
    public JsonNode httpGET(String path) throws IOException {
        System.out.println("Fetching: " + serverUrl + path);

        try {
            String json = Request.Get(serverUrl + path)
                    .connectTimeout(1000)
                    .socketTimeout(1000)
                    .execute().handleResponse(responseHandler);

            JsonNode root = mapper.readTree(json);
            return root;
        } catch (IOException ex) {
            System.err.println("IO exception: " + ex);
            return null;
        }
    }


    /**
     * Performs simple HTTP GET request, returning JSON body.
     * @param path The URL (relative to {@link MessengerAPI#serverUrl} to use.
     * @return The JSON response to the request.
     * @throws IOException
     */
    public JsonNode httpPOST(String path, String requestBody) throws IOException {
        System.out.println("Fetching: " + serverUrl + path + ", with body:");
        System.out.println(requestBody);

        try {
            String json = Request.Post(serverUrl + path)
                    .bodyString(requestBody, ContentType.APPLICATION_FORM_URLENCODED)
                    .connectTimeout(1000)
                    .socketTimeout(1000)
                    .execute().handleResponse(responseHandler);

            JsonNode root = mapper.readTree(json);
            return root;
        } catch (IOException ex) {
            System.err.println("IO exception: " + ex);
            return null;
        }
    }


    /**
     * @return {@link MessengerAPI#sessionToken}
     */
    public String getSessionToken() {
        return sessionToken;
    }


    /**
     * @return {@link MessengerAPI#serverUrl}
     */
    public String getServerUrl() {
        return serverUrl;
    }


    /**
     * @return {@link MessengerAPI#permissions}
     */
    public UserPermissions getPermissions() {
        return permissions;
    }
}
