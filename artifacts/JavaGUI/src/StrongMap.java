import java.util.HashMap;
import java.util.Map;

/**
 * A map that stores named (or otherwise identified) parameters, and prevents new entries from being added after initialisation.
 *
 * @author  Eric Fulwiler, Daniel Johnson, Joseph T. Parsons, Cory Stadther
 * @version 2.0
 * @since   2017-August-05
 */
public class StrongMap<K, V> {
    /**
     * The map storing our StrongMap.
     */
    private Map<K, V> map = new HashMap();


    /**
     * Create the map with a list of defaultKeys and defaultValues.
     *
     * @param defaultKeys The keys that the map should be created with.
     * @param defaultValues The values that the map should be created with.
     */
    public StrongMap(K[] defaultKeys, V[] defaultValues) {
        if (defaultKeys.length != defaultValues.length) {
            throw new RuntimeException("Configuration map requires that default keys and default values have the same number of entries.");
        }
        else {
            for (int i = 0; i < defaultKeys.length; i++) {
                map.put(defaultKeys[i], defaultValues[i]);
            }
        }
    }


    /**
     * Get a value identified by key.
     *
     * @param key The key.
     *
     * @throws NoKeyException If key does not exist in the map.
     *
     * @return The value identified by key.
     */
    public V get(K key) {
        if (!map.containsKey(key)) {
            throw new NoKeyException(key);
        }
        else {
            return map.get(key);
        }
    }


    /**
     * Update a value given a key.
     *
     * @param key The key.
     * @param value The new value.
     *
     * @throws NoKeyException If key does not exist in the map.
     */
    public void put(K key, V value) {
        if (!map.containsKey(key)) {
            throw new NoKeyException(key);
        }
        else {
            map.put(key, value);
        }
    }
}