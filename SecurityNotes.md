# Password Hashing #

For the 3.0 release, FreezeMessenger will only use the Sha256 algorithm with a randomly generated salt(as of release, this hash involves mtrand, uniqueid, and the shuffle function for randomisation, with a microtime seed as well -- this might be slightly overkill) as well as an unchanging, admin-generated salt stored in config.php (which helps protect against security breeches in which only the database is accessed). This algorithm runs as such:

for ($i = 0; $i < $runs; $i++) {
> $password = sha256($password . $salt . $adminSalt);
}

FreezeMessenger **does not** support passwords being hashed prior to transit between the client and the server. This is to say, only plaintext passwords can be sent to the FreezeMessenger backend. **Ideally, these will be encrypted using SSL.** (The rational for this decision is that not using the salt on the first run of the algorithm could, in theory, make rainbow attacks slightly more likely).

FreezeMessenger is, however, designed to ensure that different algorithms could be implemented without too much hassle. The users table does include a column for which password hashing algorithm is used. As such, more secure algorithms could be implemented will relative hassle if necessary. Advanced users could likely make this modification with little hassle.

# Message Encryption #
**Partial** encryption of messages is possible to protect against database compromises. **The server will always be able to fully decrypt all messages.**

The message search index is **not** encrypted, however. In an attempt to partially obfuscate messages the index will be stored alphabetically (thus, the message "where is everybody" would be stored as "everybody is where") but this is obviously only going to provide limited obfuscation.

In general, encryption of messages is minimal but does provide a small security boost, especially if the search index is disabled.

# User Permissions #
It is worth noting that user permissions are fairly loose. They should in theory work, but as new as FreezeMessenger is it is nearly impossible t ensure that the user permissions system is without bugs (indeed, there are several oversights that were fixed in Beta 4 that could essentially allow anyone with proper no how to ignore the system entirely).

If an exploit is discovered, please report it to the developers immediately.