import java.io.Serializable;

/**
 * Created by Administrator on 9/3/2017.
 */
public class LoggedInUser extends User implements Serializable {
    private String password = "";

    public String getPassword() {
        return password;
    }

    public void setPassword(String password) {
        this.password = password;
    }
}
