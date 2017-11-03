/**
 * Created by Administrator on 9/21/2017.
 */
import java.io.IOException;
import java.net.URL;
import java.net.URLConnection;

public class URLInterceptorHttps extends sun.net.www.protocol.https.Handler {
    URLCallback callback;

    public URLInterceptorHttps(URLCallback callback) {
        this.callback = callback;
    }

    public void setCallback(URLCallback callback) {
        this.callback = callback;
    }

    @Override
    protected URLConnection openConnection(URL url) throws IOException {
        if (callback != null) {
            callback.run(url);
        }

        System.out.println("Requested:" + url);
        return super.openConnection(url);
    }
}