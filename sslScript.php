<?php
//credit to https://www.devdungeon.com/content/how-use-ssl-sockets-php
$certificateData = array(
    "countryName" => "US",
    "stateOrProvinceName" => "Texas",
    "localityName" => "Houston",
    "organizationName" => "DevDungeon.com",
    "organizationalUnitName" => "Development",
    "commonName" => "DevDungeon",
    "emailAddress" => "nanodano@devdungeon.com"
);

// Generate certificate
$privateKey = openssl_pkey_new();
$certificate = openssl_csr_new($certificateData, $privateKey);
$certificate = openssl_csr_sign($certificate, null, $privateKey, 365);

// Generate PEM file
$pem_passphrase = 'abracadabra'; // empty for no passphrase
$pem = array();
openssl_x509_export($certificate, $pem[0]);
openssl_pkey_export($privateKey, $pem[1], $pem_passphrase);
$pem = implode($pem);

// Save PEM file
$pemfile = './server.pem';
file_put_contents($pemfile, $pem);
?>