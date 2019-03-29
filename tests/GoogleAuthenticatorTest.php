<?php

require_once __DIR__.'/../vendor/autoload.php';

if (!class_exists('PHPUnit_Framework_TestCase') && class_exists('\PHPUnit\Framework\TestCase')) {
    class PHPUnit_Framework_TestCase extends \PHPUnit\Framework\TestCase
    {
    }
}

class GoogleAuthenticatorTest extends PHPUnit_Framework_TestCase
{
    /* @var $googleAuthenticator PHPGangsta_GoogleAuthenticator */
    protected $googleAuthenticator;

    protected function setUp()
    {
        $this->googleAuthenticator = new PHPGangsta_GoogleAuthenticator();
    }

    public function codeProvider()
    {
        // Secret, timeSlice, code, codeLength, algo
        return array(
            array('SECRET', '0', '200470'),
            array('SECRET', '1385909245', '780018'),
            array('SECRET', '1378934578', '705013'),

            array('GEZDGNBVGY3TQOJQGEZDGNBVGY3TQOJQ', '1', '94287082', 8, 'SHA1'),
            array('GEZDGNBVGY3TQOJQGEZDGNBVGY3TQOJQ', '37037036', '07081804', 8, 'SHA1'),
            array('GEZDGNBVGY3TQOJQGEZDGNBVGY3TQOJQ', '37037037', '14050471', 8, 'SHA1'),
            array('GEZDGNBVGY3TQOJQGEZDGNBVGY3TQOJQ', '41152263', '89005924', 8, 'SHA1'),
            array('GEZDGNBVGY3TQOJQGEZDGNBVGY3TQOJQ', '66666666', '69279037', 8, 'SHA1'),
            array('GEZDGNBVGY3TQOJQGEZDGNBVGY3TQOJQ', '666666666', '65353130', 8, 'SHA1'),

            array('GEZDGNBVGY3TQOJQGEZDGNBVGY3TQOJQGEZDGNBVGY3TQOJQGEZA', '1', '46119246', 8, 'SHA256'),
            array('GEZDGNBVGY3TQOJQGEZDGNBVGY3TQOJQGEZDGNBVGY3TQOJQGEZA', '37037036', '68084774', 8, 'SHA256'),
            array('GEZDGNBVGY3TQOJQGEZDGNBVGY3TQOJQGEZDGNBVGY3TQOJQGEZA', '37037037', '67062674', 8, 'SHA256'),
            array('GEZDGNBVGY3TQOJQGEZDGNBVGY3TQOJQGEZDGNBVGY3TQOJQGEZA', '41152263', '91819424', 8, 'SHA256'),
            array('GEZDGNBVGY3TQOJQGEZDGNBVGY3TQOJQGEZDGNBVGY3TQOJQGEZA', '66666666', '90698825', 8, 'SHA256'),
            array('GEZDGNBVGY3TQOJQGEZDGNBVGY3TQOJQGEZDGNBVGY3TQOJQGEZA', '666666666', '77737706', 8, 'SHA256'),

            array('GEZDGNBVGY3TQOJQGEZDGNBVGY3TQOJQGEZDGNBVGY3TQOJQGEZDGNBVGY3TQOJQGEZDGNBVGY3TQOJQGEZDGNBVGY3TQOJQGEZDGNA', '1', '90693936', 8, 'SHA512'),
            array('GEZDGNBVGY3TQOJQGEZDGNBVGY3TQOJQGEZDGNBVGY3TQOJQGEZDGNBVGY3TQOJQGEZDGNBVGY3TQOJQGEZDGNBVGY3TQOJQGEZDGNA', '37037036', '25091201', 8, 'SHA512'),
            array('GEZDGNBVGY3TQOJQGEZDGNBVGY3TQOJQGEZDGNBVGY3TQOJQGEZDGNBVGY3TQOJQGEZDGNBVGY3TQOJQGEZDGNBVGY3TQOJQGEZDGNA', '37037037', '99943326', 8, 'SHA512'),
            array('GEZDGNBVGY3TQOJQGEZDGNBVGY3TQOJQGEZDGNBVGY3TQOJQGEZDGNBVGY3TQOJQGEZDGNBVGY3TQOJQGEZDGNBVGY3TQOJQGEZDGNA', '41152263', '93441116', 8, 'SHA512'),
            array('GEZDGNBVGY3TQOJQGEZDGNBVGY3TQOJQGEZDGNBVGY3TQOJQGEZDGNBVGY3TQOJQGEZDGNBVGY3TQOJQGEZDGNBVGY3TQOJQGEZDGNA', '66666666', '38618901', 8, 'SHA512'),
            array('GEZDGNBVGY3TQOJQGEZDGNBVGY3TQOJQGEZDGNBVGY3TQOJQGEZDGNBVGY3TQOJQGEZDGNBVGY3TQOJQGEZDGNBVGY3TQOJQGEZDGNA', '666666666', '47863826', 8, 'SHA512'),
        );
    }

    public function testItCanBeInstantiated()
    {
        $ga = new PHPGangsta_GoogleAuthenticator();

        $this->assertInstanceOf('PHPGangsta_GoogleAuthenticator', $ga);
    }

    public function testCreateSecretDefaultsToSixteenCharacters()
    {
        $ga = $this->googleAuthenticator;
        $secret = $ga->createSecret();

        $this->assertEquals(strlen($secret), 16);
    }

    public function testCreateSecretLengthCanBeSpecified()
    {
        $ga = $this->googleAuthenticator;

        for ($secretLength = 16; $secretLength < 100; ++$secretLength) {
            $secret = $ga->createSecret($secretLength);

            $this->assertEquals(strlen($secret), $secretLength);
        }
    }

    /**
     * @dataProvider codeProvider
     */
    public function testGetCodeReturnsCorrectValues($secret, $timeSlice, $code, $length = 6, $algo = 'SHA1')
    {
        $this->googleAuthenticator->setCodeLength($length);
        $generatedCode = $this->googleAuthenticator->getCode($secret, $timeSlice, $algo);

        $this->assertEquals($code, $generatedCode);
    }

    public function testGetQRCodeGoogleUrlReturnsCorrectUrl()
    {
        $secret = 'SECRET';
        $name = 'Test';
        $url = $this->googleAuthenticator->getQRCodeGoogleUrl($name, $secret);
        $urlParts = parse_url($url);

        parse_str($urlParts['query'], $queryStringArray);

        $this->assertEquals($urlParts['scheme'], 'https');
        $this->assertEquals($urlParts['host'], 'api.qrserver.com');
        $this->assertEquals($urlParts['path'], '/v1/create-qr-code/');

        $expectedChl = 'otpauth://totp/'.$name.'?secret='.$secret;

        $this->assertEquals($queryStringArray['data'], $expectedChl);
    }

    public function testVerifyCode()
    {
        $secret = 'SECRET';
        $code = $this->googleAuthenticator->getCode($secret);
        $result = $this->googleAuthenticator->verifyCode($secret, $code);

        $this->assertEquals(true, $result);

        $code = 'INVALIDCODE';
        $result = $this->googleAuthenticator->verifyCode($secret, $code);

        $this->assertEquals(false, $result);
    }

    public function testVerifyCodeWithLeadingZero()
    {
        $secret = 'SECRET';
        $code = $this->googleAuthenticator->getCode($secret);
        $result = $this->googleAuthenticator->verifyCode($secret, $code);
        $this->assertEquals(true, $result);

        $code = '0'.$code;
        $result = $this->googleAuthenticator->verifyCode($secret, $code);
        $this->assertEquals(false, $result);
    }

    public function testSetCodeLength()
    {
        $result = $this->googleAuthenticator->setCodeLength(6);

        $this->assertInstanceOf('PHPGangsta_GoogleAuthenticator', $result);
    }

    public function testValidateCorrectCodeLength()
    {
        $secret = 'SECRET';
        $this->googleAuthenticator->setCodeLength(8);
        $this->assertEquals(true, $this->googleAuthenticator->verifyCode($secret, $this->googleAuthenticator->getCode($secret)));
    }
}
