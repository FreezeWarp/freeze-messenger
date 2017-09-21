/**
 * Created by Administrator on 9/21/2017.
 */
import java.net.URLStreamHandler;
import java.net.URLStreamHandlerFactory;

public class URLInterceptorFactory implements URLStreamHandlerFactory {
    URLCallback callback;

    public URLInterceptorFactory(URLCallback callback) {
        this.callback = callback;
    }

    public URLStreamHandler createURLStreamHandler(String protocol)
    {
        if(protocol.equals("http")) {
            return new URLInterceptorHttp(callback);
        } else if(protocol.equals("https")) {
            return new sun.net.www.protocol.https.Handler();
        }
        return null;
    }
}
