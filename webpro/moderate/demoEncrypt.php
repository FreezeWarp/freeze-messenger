<?php
if ($_POST['encryptText']) {
    openssl_public_encrypt(
            $_POST['encryptText'], // The text we want to encrypt.
            $encrypted, // The variable the encrypted text will be stored in.
            $_POST['publicKey'], // Our friend's public key.
            OPENSSL_PKCS1_OAEP_PADDING // A more secure padding, used by default in Zend RSA library. The default is vulnerable to "Bleichenbacher's chosen-ciphertext attack" (https://framework.zend.com/security/advisory/ZF2015-10)
    );
    echo container("Asymmetric Encryption Demo: Your Encrypted Text", '<textarea style="width: 100%" rows="10">' . base64_encode($encrypted) . '</textarea>');
}
else {
    echo container("Encryption Demo", '
    <form method="post" action="moderate.php?do=demoEncrypt" style="zoom: 2; width: 250px; text-align: center;">
        Encrypt Text:<br /><input type="text" name="encryptText" /><br />
        Your Friend\'s Public Key:<br /><textarea name="publicKey" rows="10" style="width: 100%; font-size: .5em; font-family: monospace;"></textarea><br />
        <button type="submit">Encrypt</button>
    </form>');
}