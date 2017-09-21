import java.io.IOException;
import java.io.ObjectInputStream;
import java.io.ObjectOutputStream;
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

    private void writeObject(ObjectOutputStream oos) throws IOException {
        oos.writeObject(getName());
        oos.writeObject(getPassword());
    }

    private void readObject(ObjectInputStream ois) throws ClassNotFoundException, IOException {
        setName((String) ois.readObject());
        setPassword((String) ois.readObject());
    }
}
