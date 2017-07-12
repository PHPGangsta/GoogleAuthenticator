<?php

use PHPGangsta\GoogleAuthenticator;
use PHPUnit\Framework\TestCase;
require_once __DIR__ . '/../vendor/autoload.php';

/**
 * Class GoogleAuthenticatorTest
 */
class GoogleAuthenticatorTest extends TestCase {
    /* @var $googleAuthenticator GoogleAuthenticator */
    protected $googleAuthenticator;

    /**
     * setUp
     *
     * @return void
     */
    protected function setUp() {
        $this->googleAuthenticator = new GoogleAuthenticator();
    }

    /**
     * codeProvider
     *
     * @return array
     */
    public function codeProvider() {
        // Secret, time, code
        return [
            ['SECRET', '0', '200470'],
            ['SECRET', '1385909245', '780018'],
            ['SECRET', '1378934578', '705013'],
        ];
    }

    /**
     * testItCanBeInstantiated
     *
     * @return void
     */
    public function testItCanBeInstantiated() {
        $ga = $this->googleAuthenticator;

        $this->assertInstanceOf(GoogleAuthenticator::class, $ga);
    }

    /**
     * testCreateSecretDefaultsToSixteenCharacters
     *
     * @return void
     */
    public function testCreateSecretDefaultsToSixteenCharacters() {
        $ga     = $this->googleAuthenticator;
        $secret = $ga->createSecret();

        $this->assertEquals(strlen($secret), 16);
    }

    /**
     * testCreateSecretLengthCanBeSpecified
     *
     * @return void
     */
    public function testCreateSecretLengthCanBeSpecified() {
        $ga = $this->googleAuthenticator;

        for ($secretLength = 16; $secretLength < 100; ++$secretLength) {
            $secret = $ga->createSecret($secretLength);

            $this->assertEquals(strlen($secret), $secretLength);
        }
    }

    /**
     * testGetCodeReturnsCorrectValues
     *
     * @dataProvider codeProvider
     *
     * @return void
     */
    public function testGetCodeReturnsCorrectValues($secret, $timeSlice, $code) {
        $generatedCode = $this->googleAuthenticator->getCode($secret, $timeSlice);

        $this->assertEquals($code, $generatedCode);
    }

    /**
     * testGetQRCodeGoogleUrlReturnsCorrectUrl
     *
     * @return void
     */
    public function testGetQRCodeGoogleUrlReturnsCorrectUrl() {
        $secret   = 'SECRET';
        $name     = 'Test';
        $url      = $this->googleAuthenticator->getQRCodeGoogleUrl($name, $secret);
        $urlParts = parse_url($url);

        parse_str($urlParts['query'], $queryStringArray);

        $this->assertEquals($urlParts['scheme'], 'https');
        $this->assertEquals($urlParts['host'], 'chart.googleapis.com');
        $this->assertEquals($urlParts['path'], '/chart');

        $expectedChl = 'otpauth://totp/' . $name . '?secret=' . $secret;

        $this->assertEquals($queryStringArray['chl'], $expectedChl);
    }

    /**
     * testVerifyCode
     *
     * @return void
     */
    public function testVerifyCode() {
        $secret = 'SECRET';
        $code   = $this->googleAuthenticator->getCode($secret);
        $result = $this->googleAuthenticator->verifyCode($secret, $code);

        $this->assertEquals(true, $result);

        $code   = 'INVALIDCODE';
        $result = $this->googleAuthenticator->verifyCode($secret, $code);

        $this->assertEquals(false, $result);
    }

    /**
     * testVerifyCodeWithLeadingZero
     *
     * @return void
     */
    public function testVerifyCodeWithLeadingZero() {
        $secret = 'SECRET';
        $code   = $this->googleAuthenticator->getCode($secret);
        $result = $this->googleAuthenticator->verifyCode($secret, $code);
        $this->assertEquals(true, $result);

        $code   = '0' . $code;
        $result = $this->googleAuthenticator->verifyCode($secret, $code);
        $this->assertEquals(false, $result);
    }

    /**
     * testSetCodeLength
     *
     * @return void
     */
    public function testSetCodeLength() {
        $result = $this->googleAuthenticator->setCodeLength(6);

        $this->assertInstanceOf(GoogleAuthenticator::class, $result);
    }

}
