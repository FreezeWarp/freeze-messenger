import java.io.IOException;
import java.io.ObjectInputStream;
import java.io.ObjectOutputStream;
import java.io.Serializable;

/**
 * Created by Administrator on 9/3/2017.
 */
public class LoggedInUser extends User implements Serializable {
    /**
     * The user's password. Really, we probably shouldn't store this by default.
     */
    private String password = "";

    /**
     * The last refresh token we were issued. This should be good to re-login within the next week or so.
     */
    private String refreshToken = "";


    public String getPassword() {
        return password;
    }

    public void setPassword(String password) {
        this.password = password;
    }

    public String getRefreshToken() {
        return refreshToken;
    }

    public void setRefreshToken(String refreshToken) {
        this.refreshToken = refreshToken;
    }

    private void writeObject(ObjectOutputStream oos) throws IOException {
        oos.writeObject(getRefreshToken());
        oos.writeObject(getName());
        oos.writeObject(getPassword());
    }

    private void readObject(ObjectInputStream ois) throws ClassNotFoundException, IOException {
        setRefreshToken((String) ois.readObject());
        setName((String) ois.readObject());
        setPassword((String) ois.readObject());
    }
}
