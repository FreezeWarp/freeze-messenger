/**
 * Created by Administrator on 9/21/2017.
 */
import java.net.URLStreamHandler;
import java.net.URLStreamHandlerFactory;

public class URLInterceptorFactory implements URLStreamHandlerFactory {
    /**
     * The callback to run on URL navigation.
     */
    URLCallback callback;

    /**
     * The handler registered for HTTP connections.
     */
    URLInterceptorHttp httpHandler = new URLInterceptorHttp(null);

    /**
     * The handler registered for HTTPS connections.
     */
    URLInterceptorHttps httpsHandler = new URLInterceptorHttps(null);

    public URLInterceptorFactory(URLCallback callback) {
        this.callback = callback;
    }

    public void setCallback(URLCallback callback) {
        this.callback = callback;
        httpHandler.setCallback(callback);
        httpsHandler.setCallback(callback);
    }

    public URLStreamHandler createURLStreamHandler(String protocol)
    {
        if(protocol.equals("http")) {
            return httpHandler;
        } else if(protocol.equals("https")) {
            return httpsHandler;
        }
        return null;
    }
}
