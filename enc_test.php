<?php
$data = 'This is my password to encrypt!';
$method = "AES-128-ECB";
$passphrase = "c0D3_6eN3R4t0REIT";
$options = 0;
$iv = '';
$string = openssl_encrypt($data, $method, $passphrase, $options = 0, $iv);
echo 'Encrypted pwd is: '.$string;

$decrypted = openssl_decrypt($string, $method, $passphrase, $options, $iv);
echo '<br>Decrypted pwd is: '.$decrypted;

?>