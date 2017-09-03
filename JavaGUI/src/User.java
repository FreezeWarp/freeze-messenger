import java.io.Serializable;

/**
 * Created by Administrator on 9/3/2017.
 */
public class User implements Serializable {
    private String name = "";

    public String getName() {
        return name;
    }

    public void setName(String name) {
        this.name = name;
    }
}
