import javafx.beans.binding.Bindings;
import javafx.beans.binding.ObjectBinding;
import javafx.beans.property.*;
import javafx.scene.image.Image;
import javafx.scene.image.ImageView;
import javafx.scene.image.ImageViewBuilder;

import java.io.Serializable;
import java.util.HashMap;
import java.util.Map;

/**
 * Created by Administrator on 9/3/2017.
 */
public class User implements Serializable {
    /**
     * A map between image URLs and image objects. Used mainly for caching.
     */
    public static Map<String, Image> images = new HashMap<>();

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
    }

    public StringProperty avatarProperty() {
        return this.avatar;
    }

    /**
     * Get an ImageView representation of the user's avatar. Note that ImageViews should not be reused.
     * @return An ImageView containing the user's avatar URL, resized.
     */
    public ImageView getAvatarImageView() {
        if (!images.containsKey(getAvatar())) {
            images.put(getAvatar(), new Image(getAvatar(), 24, 24, false, true));
        }

        return ImageViewBuilder.create()
                .image(images.get(getAvatar()))
                .build();
    }
}
