<?php

declare(strict_types=1);

namespace emailforatk\tests;


use PMRAtk\Data\Email\EmailAccount;
use PMRAtk\Data\Email\PHPMailer;
use PMRAtk\tests\phpunit\TestCase;

/**
 * Class PHPMailerTest
 */
class PHPMailerTest extends TestCase {


    /*
     *
     */
    public function testAddUUID() {
        $this->_addStandardEmailAccount();
        $tt = new PHPMailer(self::$app);
        self::$app->isTestMode = true;
        $_ENV['TEST_EMAIL_UUID'] = 'DUDUDU';
        self::assertFalse($tt->send());
    }


    /*
     *
     */
    public function testCustomEmailAccount() {
        $this->_addStandardEmailAccount();
        $pm = new PHPMailer(self::$app);

        $ea = new EmailAccount(self::$app->db);
        $ea->set('name',        'DUDU');
        $ea->set('sender_name', 'DUDU');
        $ea->set('user',        'DUDU');
        $ea->set('password',    'DUDU');
        $ea->set('smtp_host',   'DUDU');
        $ea->set('smtp_port',   'DUDU');
        $ea->set('imap_host',   'DUDU');
        $ea->set('imap_port',   'DUDU');
        $ea->set('imap_sent_folder', 'DUDU');
        $ea->save();

        $pm = new PHPMailer(self::$app, ['emailAccount' => $ea]);
        $this->callProtected($pm, '_setEmailAccount');
        self::assertEquals('DUDU', $pm->Host);
    }


    /*
     *
     */
    public function testCustomEmailAccountById() {
        $this->_addStandardEmailAccount();
        $pm = new PHPMailer(self::$app);

        $ea = new EmailAccount(self::$app->db);
        $ea->set('name',        'DUDU');
        $ea->set('sender_name', 'DUDU');
        $ea->set('user',        'DUDU');
        $ea->set('password',    'DUDU');
        $ea->set('smtp_host',   'DUDU');
        $ea->set('smtp_port',   'DUDU');
        $ea->set('imap_host',   'DUDU');
        $ea->set('imap_port',   'DUDU');
        $ea->set('imap_sent_folder', 'DUDU');
        $ea->save();

        $pm = new PHPMailer(self::$app, ['emailAccount' => $ea->get('id')]);
        $this->callProtected($pm, '_setEmailAccount');
        self::assertEquals('DUDU', $pm->Host);
    }


    /**
     *
     */
    public function testaddSentEmailByIMAP()
    {
        $ea = $this->_addStandardEmailAccount();
        $imapHost = $ea->get('imap_host');

        //first unset some needed Imap field
        $ea->set('imap_host', '');
        $ea->save();
        $pm = new PHPMailer(self::$app, ['emailAccount' => $ea->get('id')]);
        self::assertFalse($pm->addSentEmailByIMAP());

        //now set it to some false value
        $ea->set('imap_host', 'fsdfd');
        $ea->save();
        $pm = new PHPMailer(self::$app, ['emailAccount' => $ea->get('id')]);
        self::assertFalse($pm->addSentEmailByIMAP());

        //now back to initial value, should work
        $ea->set('imap_host', $imapHost);
        $ea->save();
        $pm = new PHPMailer(self::$app, ['emailAccount' => $ea->get('id')]);
        $pm->addAddress($ea->get('name'));
        $pm->setBody('JJAA');
        $pm->Subject = 'KKAA';
        self::assertTrue($pm->send());
        self::assertTrue($pm->addSentEmailByIMAP());
    }


    /**
     *
     */
    public function testAllowSelfSignedSSLCertificate() {
        $ea = $this->_addStandardEmailAccount();
        $ea->set('allow_self_signed_ssl', 1);
        $ea->save();
        $ea->reload();
        $pm = new PHPMailer(self::$app, ['emailAccount' => $ea->get('id')]);
        $pm->addAddress($ea->get('name'));
        $pm->setBody('ssltest');
        $pm->Subject = 'ssltest';
        self::assertTrue($pm->send());
        self::assertNotEmpty($pm->SMTPOptions);
    }


    /**
     *
     */
    public function testExceptionNoEmailAccountAvailable() {
        $pm = new PHPMailer(self::$app);
        self::expectException(\atk4\core\Exception::class);
        $this->callProtected($pm, '_setEmailAccount');
    }


    /**
     *
     */
    public function testIMAPCollectImapDebugInfo()
    {
        $ea = $this->_addStandardEmailAccount();
        //now back to initial value, should work
        $ea->set('imap_sent_folder', 'SomeNonExistantFolder');
        $ea->save();
        $pm = new PHPMailer(self::$app, ['emailAccount' => $ea->get('id')]);
        $pm->addAddress($ea->get('name'));
        $pm->addImapDebugInfo = true;
        $pm->setBody('JJAA');
        $pm->Subject = 'KKAA';
        self::assertTrue($pm->send());
        self::assertFalse($pm->addSentEmailByIMAP());
        self::assertTrue(count($pm->imapErrors) > 0);
    }
}
