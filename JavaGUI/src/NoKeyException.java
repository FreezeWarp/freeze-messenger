
/**
 * A basic exception that is fired when a dictionary lookup fails.
 *
 * @author  Eric Fulwiler, Daniel Johnson, Joseph T. Parsons, Cory Stadther
 * @version 2.0
 * @since   2017-August-05
 */
public class NoKeyException extends RuntimeException {
    public NoKeyException(Object key) {
        super("The key does not exist: " + key);
    }
}
