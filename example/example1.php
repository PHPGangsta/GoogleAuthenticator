<?php

require_once '../PHPGangsta/GoogleAuthenticator.php';

$secret = PHPGangsta_GoogleAuthenticator::createSecret();
echo "Secret is: ".$secret."\n\n";

$qrCodeUrl = PHPGangsta_GoogleAuthenticator::getQRCodeGoogleUrl('Blog', $secret);
echo "Google Charts URL for the QR-Code: ".$qrCodeUrl."\n\n";


$oneCode = PHPGangsta_GoogleAuthenticator::getCode($secret);
echo "Checking Code '$oneCode' and Secret '$secret':\n";

$checkResult = PHPGangsta_GoogleAuthenticator::verifyCode($secret, $oneCode, 2);    // 2 = 2*30sec clock tolerance
if ($checkResult) {
    echo 'OK';
} else {
    echo 'FAILED';
}