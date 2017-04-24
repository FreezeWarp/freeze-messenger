<?php
if ($_POST['encryptText']) {
    $iv_size = openssl_cipher_iv_length('AES-256-CTR'); // Get the length of the IV for the method used
    $iv = random_bytes($iv_size);

    $encrypted = openssl_encrypt(
        $_POST['encryptText'], // Our text being encrypted.
        'AES-256-CTR', // A fairly strong cipher suite.
        $_POST['sharedSecret'], // The shared secret.
        OPENSSL_RAW_DATA | OPENSSL_ZERO_PADDING,
        $iv // A randomly generated init. vector
    );
    echo base64_encode($encrypted);

    echo container("Symmetric Encryption Demo: Your Encrypted Text", '<textarea style="width: 100%" rows="10">' . base64_encode($encrypted . $iv) . '</textarea>');
}
else {
    echo container("Encryption Demo", '
    <form method="post" action="moderate.php?do=demoEncryptSym" style="zoom: 2; width: 250px; text-align: center;">
        Encrypt Text:<br /><input type="text" name="encryptText" /><br />
        Shared Secret:<br /><textarea name="sharedSecret" rows="10" style="width: 100%; font-size: .5em; font-family: monospace;"></textarea><br />
        <button type="submit">Encrypt</button>
    </form>');
}