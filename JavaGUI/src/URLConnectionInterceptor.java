import java.io.IOException;
import java.net.URL;
import java.net.URLConnection;

/**
 * Created by Administrator on 9/21/2017.
 */
public class URLConnectionInterceptor extends URLConnection {

    protected URLConnectionInterceptor(URL url) {
        super(url);
    }

    @Override
    public void connect() throws IOException {
        // Do your job here. As of now it merely prints "Connected!".
        System.out.println("Connected!");
    }

}
