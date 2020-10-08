<?php declare(strict_types=1);

namespace emailforatk\tests;


use PMRAtk\Data\Email\EmailThrowableToAdminTrait;
use PMRAtk\tests\phpunit\TestCase;

/**
 *
 */
class ThrowableTest {
    use EmailThrowableToAdminTrait;

    public $app;


    public function throwThat() {
        try {
            throw new \Exception();
        }
        catch (\Throwable $e) {
            $this->sendErrorEmailToAdmin($e, 'Error', ['test3@easyoutdooroffice.com']);
            return false;
        }
    }
}


/**
 *
 */
class EmailThrowableToAdminTest extends TestCase {
    /*
     *
     */
    public function testNewPHPMailer() {
        $this->_addStandardEmailAccount();
        $tt = new ThrowableTest();
        $tt->app = self::$app;
        self::assertFalse($tt->throwThat());
    }
}
