<?php
// Generate a key pair.
$resource = openssl_pkey_new(["private_key_bits" => ($_GET['bits'] ? (int) $_GET['bits'] : 4096)]);

// Extract $privKey from $res
openssl_pkey_export($resource, $privateKey);

// Fetch public key and store it
$publicKey = openssl_pkey_get_details($resource)['key'];

echo container('Your Public Key', '<textarea style="width: 100%; font-size: .8em; font-family: monospace;" rows="8">' . $publicKey . '</textarea>');
echo container('Your Private Key', '<textarea style="width: 100%; font-size: .8em; font-family: monospace;" rows="20">' . $privateKey . '</textarea>');
echo container('A Random Key', '<textarea style="width: 100%; font-size: .8em; font-family: monospace;" rows="8">' . hash('sha512', random_bytes(4096)) . '</textarea>');