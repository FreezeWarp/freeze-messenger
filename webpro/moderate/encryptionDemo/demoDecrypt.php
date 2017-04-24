<?php
if ($_POST['decryptText']) {
    openssl_private_decrypt(base64_decode($_POST['decryptText']), $decrypted, $_POST['privateKey'], OPENSSL_PKCS1_OAEP_PADDING);

    echo container("Asymmetric Decryption Demo: Your Decrypted Text", "<textarea rows=\"3\" style=\"width: 100%; font-size: 2em;\">$decrypted</textarea>");
}
else {
    echo container('Decryption Demo', '
    <form method="post" action="moderate.php?do=demoDecrypt" style="zoom: 2; width: 250px; text-align: center;">
        Decrypt Base64:<br /><textarea name="decryptText" rows="10" style="width: 100%; font-size: .5em;"></textarea><br />
        Your Private Key:<br /><textarea name="privateKey" rows="10" style="width: 100%; font-size: .5em; font-family: monospace;"></textarea><br />
        <button type="submit">Decrypt</button>
    </form>');
}
