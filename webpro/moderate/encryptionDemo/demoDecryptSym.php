<?php
if ($_POST['decryptText']) {
    // We store the IV at the end of the encrypted text (before base64ing) for convenience. We pull them apart here.
    $iv_size = openssl_cipher_iv_length('AES-256-CTR');

    $decryptText = base64_decode($_POST['decryptText']);
    $decryptIv = substr($decryptText, -1 * $iv_size);
    $decryptText = substr($decryptText, 0, -1 * $iv_size);

    // Decrypt the data.
    $decrypted = openssl_decrypt(
        $decryptText,
        'AES-256-CTR',
        $_POST['sharedSecret'],
        OPENSSL_RAW_DATA | OPENSSL_ZERO_PADDING,
        $decryptIv
    );

    echo container("Symmetric Decryption Demo: Your Decrypted Data", "<textarea style=\"width: 100%\" rows=\"30\">$decrypted</textarea>");
}
else {
    echo container('Decryption Demo', '
    <form method="post" action="moderate.php?do=demoDecryptSym" style="zoom: 2; width: 250px; text-align: center;">
        Decrypt Base64:<br /><textarea name="decryptText" rows="10" style="width: 100%; font-size: .5em;"></textarea><br />
        Shared Secret:<br /><textarea name="sharedSecret" rows="10" style="width: 100%; font-size: .5em; font-family: monospace;"></textarea><br />
        <button type="submit">Decrypt</button>
    </form>');
}