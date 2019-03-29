<?php

require_once __DIR__.'/../vendor/autoload.php';

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
        // Secret, time, code
        return array(
            array('SECRET', '0', '200470'),
            array('SECRET', '1385909245', '780018'),
            array('SECRET', '1378934578', '705013'),
        );
    }
    
    public function paramsProvider()
    {
        return array(
            array(null, null, null, '200x200', 'M'),
            array(-1, -1, null, '200x200', 'M'),
            array(250, 250, 'L', '250x250', 'L'),
            array(250, 250, 'M', '250x250', 'M'),
            array(250, 250, 'Q', '250x250', 'Q'),
            array(250, 250, 'H', '250x250', 'H'),
            array(250, 250, 'Z', '250x250', 'M'),
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
    public function testGetCodeReturnsCorrectValues($secret, $timeSlice, $code)
    {
        $generatedCode = $this->googleAuthenticator->getCode($secret, $timeSlice);

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
    
    /**
     * @dataProvider paramsProvider
     */
    public function testGetQRCodeGoogleUrlReturnsCorrectUrlWithOptionalParameters($width, $height, $level, $expectedSize, $expectedLevel)
    {
        $secret = 'SECRET';
        $name = 'Test';
        $url = $this->googleAuthenticator->getQRCodeGoogleUrl(
            $name, 
            $secret, 
            null,
            array(
                'width' => $width,
                'height' => $height,
                'level' => $level
            ));
        $urlParts = parse_url($url);

        parse_str($urlParts['query'], $queryStringArray);
        
        $this->assertEquals($queryStringArray['chs'], $expectedSize);
        $this->assertEquals($queryStringArray['chld'], $expectedLevel.'|0');
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
}
