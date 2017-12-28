/**
 * Created by Administrator on 9/21/2017.
 */
import java.io.IOException;
import java.io.InputStream;
import java.io.OutputStream;
import java.net.HttpURLConnection;
import java.net.URL;
import java.net.URLConnection;
import java.net.URLStreamHandler;

public class URLInterceptorHttp extends sun.net.www.protocol.http.Handler {
    URLCallback callback;

    public URLInterceptorHttp(URLCallback callback) {
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