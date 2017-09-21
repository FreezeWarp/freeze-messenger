import javafx.beans.binding.Bindings;
import javafx.beans.binding.ObjectBinding;
import javafx.beans.property.*;
import javafx.scene.image.ImageView;

import java.io.Serializable;

/**
 * Created by Administrator on 9/3/2017.
 */
public class User {
    /**
     * The user's ID.
     */
    private IntegerProperty id = new SimpleIntegerProperty();

    /**
     * The user's name.
     */
    private StringProperty name = new SimpleStringProperty("");

    /**
     * The user's avatar URL.
     */
    private StringProperty avatar = new SimpleStringProperty("");

    /**
     * The user's avatar, as an ImageView.
     */
    private ObjectProperty<ImageView> avatarImageView = new SimpleObjectProperty<>();


    public int getId() {
        return id.get();
    }

    public void setId(int id) {
        this.id.set(id);
    }

    public IntegerProperty idProperty() {
        return this.id;
    }


    public String getName() {
        return name.get();
    }

    public void setName(String name) {
        this.name.set(name);
    }

    public StringProperty nameProperty() {
        return this.name;
    }

    public String getAvatar() {
        return avatar.get();
    }

    public void setAvatar(String avatar) {
        this.avatar.set(avatar);
        this.avatarImageView.set(MainPane.getAvatar(getAvatar()));
    }

    public ImageView getAvatarImageView() {
        return this.avatarImageView.get();
    }

    public ObjectProperty<ImageView> getAvatarImageViewProperty() {
        return this.avatarImageView;
    }

    public StringProperty avatarProperty() {
        return this.avatar;
    }
}
