<?php declare(strict_types=1);

namespace emailforatk\tests;

use PMRAtk\Data\Email\BaseEmail;
use PMRAtk\Data\Email\EmailAccount;
use PMRAtk\Data\Email\EmailTemplate;
use PMRAtk\Data\Email\PHPMailer;
use PMRAtk\tests\TestClasses\BaseEmailTestClasses\EditPerRecipientEmail;
use PMRAtk\tests\TestClasses\BaseEmailTestClasses\SomeBaseEmailImplementation;
use PMRAtk\tests\TestClasses\BaseEmailTestClasses\UserWithSignature;
use PMRAtk\tests\TestClasses\BaseModelClasses\BaseModelA;
use PMRAtk\tests\TestClasses\BaseModelClasses\BaseModelB;
use PMRAtk\tests\phpunit\TestCase;
use PMRAtk\Data\Email;


class BaseEmailTest extends TestCase {

    public function testAddRecipient() {
        $this->_addStandardEmailAccount();
        $base_email = new BaseEmail(self::$app->db);
        $base_email->save();

        //pass a Guide, should have an email set
        $g = new BaseModelA(self::$app->db);
        $g->set('firstname', 'Lala');
        $g->set('lastname', 'Dusu');
        $g->save();
        $g->addSecondaryModelRecord(Email::class, 'test1@easyoutdooroffice.com');
        self::assertTrue($base_email->addRecipient($g));
        self::assertEquals(1, $base_email->ref('email_recipient')->action('count')->getOne());

        //adding the same guide again shouldnt change anything
        self::assertFalse($base_email->addRecipient($g));
        self::assertEquals(1, $base_email->ref('email_recipient')->action('count')->getOne());

        //pass a non-loaded Guide
        $g = new BaseModelA(self::$app->db);
        self::assertFalse($base_email->addRecipient($g));

        //pass a Guide without an existing Email
        $g = new BaseModelA(self::$app->db);
        $g->save();
        self::assertFalse($base_email->addRecipient($g));

        //pass an email id
        $g = new BaseModelA(self::$app->db);
        $g->save();
        $e = $g->addSecondaryModelRecord(Email::class, 'test3@easyoutdooroffice.com');
        self::assertTrue($base_email->addRecipient($e->get('id')));
        self::assertEquals(2, $base_email->ref('email_recipient')->action('count')->getOne());

        //pass a non existing email id
        self::assertFalse($base_email->addRecipient(111111));

        //pass existing email id that does not belong to any parent model
        $e = new Email(self::$app->db);
        $e->set('value', 'test1@easyoutdooroffice.com');
        $e->save();
        self::assertFalse($base_email->addRecipient($e->get('id')));


        //pass a valid Email
        self::assertTrue($base_email->addRecipient('philipp@spame.de'));
        self::assertEquals(3, $base_email->ref('email_recipient')->action('count')->getOne());

        //pass an invalid email
        self::assertFalse($base_email->addRecipient('hannsedfsgs'));

        //now remove all
        foreach($base_email->ref('email_recipient') as $rec) {
            self::assertTrue($base_email->removeRecipient($rec->get('id')));
        }
        self::assertEquals(0, $base_email->ref('email_recipient')->action('count')->getOne());

        //remove some non_existing EmailRecipient
        self::assertFalse($base_email->removeRecipient('11111'));

        //test adding not the first, but some other email
        $g = new BaseModelA(self::$app->db);
        $g->save();
        $g->addSecondaryModelRecord(Email::class, 'test1@easyoutdooroffice.com');
        $test2_id = $g->addSecondaryModelRecord(Email::class, 'test2@easyoutdooroffice.com');
        self::assertTrue($base_email->addRecipient($g, $test2_id->get('id')));
        //now there should be a single recipient and its email should be test2...
        foreach($base_email->ref('email_recipient') as $rec) {
            self::assertEquals($rec->get('email'), 'test2@easyoutdooroffice.com');
        }
    }

    public function testSend() {
        $this->_addStandardEmailAccount();
        //no recipients, should return false
        $base_email = new BaseEmail(self::$app->db);
        self::assertFalse($base_email->send());

        //one recipient, should return true
        $base_email = new BaseEmail(self::$app->db);
        $base_email->set('subject', 'Hello from PHPUnit');
        $base_email->set('message', 'Hello from PHPUnit');
        self::assertTrue($base_email->addRecipient('test2@easyoutdooroffice.com'));
        self::assertTrue($base_email->send());
    }

    public function testloadInitialValues() {
        $this->_addStandardEmailAccount();
        $base_email = new BaseEmail(self::$app->db);
        $base_email->loadInitialValues();
        self::assertTrue(true);
    }

    public function testAttachments() {
        $this->_addStandardEmailAccount();
        $base_email = new BaseEmail(self::$app->db);
        $base_email->save();
        $file = $this->createTestFile('test.jpg');
        $base_email->addAttachment($file->get('id'));
        self::assertEquals(1, count($base_email->get('attachments')));

        $base_email->removeAttachment($file->get('id'));
        self::assertEquals(0, count($base_email->get('attachments')));
    }

    public function testSendAttachments() {
        $this->_addStandardEmailAccount();
        $base_email = new BaseEmail(self::$app->db);
        $base_email->save();
        $file = $this->createTestFile('test.jpg');
        $base_email->addAttachment($file->get('id'));
        self::assertTrue($base_email->addRecipient('test1@easyoutdooroffice.com'));
        self::assertTrue($base_email->send());
    }

    public function testInitialTemplateLoading() {
        $this->_addStandardEmailAccount();
        $base_email = new BaseEmail(self::$app->db, ['template' => 'testemailtemplate.html']);
        $base_email->loadInitialValues();
        self::assertEquals($base_email->get('subject'), 'TestBetreff');
        self::assertTrue(strpos($base_email->get('message'), 'TestInhalt') !== false);
    }

    public function testInitialTemplateLoadingByString() {
        $this->_addStandardEmailAccount();
        $base_email = new BaseEmail(self::$app->db, ['template' => '{Subject}Hellow{/Subject}Magada']);
        $base_email->loadInitialValues();
        self::assertEquals($base_email->get('subject'), 'Hellow');
        self::assertTrue(strpos($base_email->get('message'), 'Magada') !== false);
    }


    /*
     * Disabled until ATK login works with current Data version
     *
    public function testLoadSignatureByUserSignature() {
        $this->_addStandardEmailAccount();
        if(isset(self::$app->auth->user)) {
            $initial = self::$app->auth->user;
        }
        self::$app->auth->user = null;
        self::$app->auth->user = new UserWithSignature(self::$app->db);
        $base_email = new BaseEmail(self::$app->db, ['template' => '{Subject}Hellow{/Subject}Magada{Signature}{/Signature}']);
        $base_email->loadInitialValues();
        self::assertTrue(strpos($base_email->get('message'), 'TestSignature') !== false);
        if(isset($initial)) {
            self::$app->auth->user = $initial;
        }
    }

    public function testloadSignatureBySetting() {
        $this->_addStandardEmailAccount();
        $_ENV['STD_EMAIL_SIGNATURE'] = 'TestSigSetting';
        $base_email = new BaseEmail(self::$app->db, ['template' => '{Subject}Hellow{/Subject}Magada{Signature}{/Signature}']);
        $base_email->loadInitialValues();
        self::assertTrue(strpos($base_email->get('message'), 'TestSigSetting') !== false);
    }
    /*   */

    public function testSMTPKeepAlive() {
        $this->_addStandardEmailAccount();
        $base_email = new BaseEmail(self::$app->db, ['template' => '{Subject}TestMoreThanOneRecipient{/Subject}TestMoreThanOneRecipient{Signature}{/Signature}']);
        $base_email->loadInitialValues();
        $base_email->save();
        self::assertTrue($base_email->addRecipient('test1@easyoutdooroffice.com'));
        self::assertTrue($base_email->addRecipient('test2@easyoutdooroffice.com'));
        $base_email->send();
    }

    public function testProcessSubjectAndMessagePerRecipient() {
        $this->_addStandardEmailAccount();
        $base_email = new EditPerRecipientEmail(self::$app->db, ['template' => '{Subject}BlaDu{$testsubject}{/Subject}BlaDu{$testbody}']);
        $base_email->loadInitialValues();
        $base_email->processSubjectPerRecipient = function($recipient, $template) {
            $template->set('testsubject', 'HARALD');
        };
        $base_email->processMessagePerRecipient = function($recipient, $template) {
            $template->set('testbody', 'MARTOR');
        };
        $base_email->addRecipient('test1@easyoutdooroffice.com');
        self::assertTrue($base_email->send());
        self::assertTrue(strpos($base_email->phpMailer->getSentMIMEMessage(), 'HARALD') !== false);
        self::assertTrue(strpos($base_email->phpMailer->getSentMIMEMessage(), 'MARTOR') !== false);
    }

    public function testProcessMessageFunction() {
        $this->_addStandardEmailAccount();
        $base_email = new BaseEmail(self::$app->db, ['template' => '{Subject}BlaDu{$testsubject}{/Subject}BlaDu{$testbody}']);
        $base_email->processMessageTemplate = function($template, $model) {
            $template->set('testbody', 'HALLELUJA');
        };
        $base_email->processSubjectTemplate = function($template, $model) {
            $template->set('testsubject', 'HALLELUJA');
        };
        $base_email->loadInitialValues();
        self::assertTrue(strpos($base_email->get('message'), 'HALLELUJA') !== false);
        self::assertTrue(strpos($base_email->get('subject'), 'HALLELUJA') !== false);
    }

    public function testOnSuccessFunction() {
        $this->_addStandardEmailAccount();
        $base_email = new BaseEmail(self::$app->db, ['template' => '{Subject}BlaDu{$testsubject}{/Subject}BlaDu{$testbody}']);
        $base_email->loadInitialValues();
        $base_email->model = new BaseModelA(self::$app->db);
        $base_email->onSuccess = function($model) {
            $model->set('name', 'PIPI');
        };
        $base_email->addRecipient('test1@easyoutdooroffice.com');
        self::assertTrue($base_email->send());
        self::assertEquals('PIPI', $base_email->model->get('name'));
    }

    /**
     * F***ing ref() function on non-loaded models!.
     * Make sure non-saved BaseEmail does not accidently
     * load any EmailRecipients
     */
    public function testNonLoadedBaseEmailHasNoRefEmailRecipients() {
        $this->_addStandardEmailAccount();
        //first create a baseEmail and some EmailRecipients
        $be1 = new BaseEmail(self::$app->db);
        $be1->save();
        //this baseEmail should not be sent. $be2->ref('email_recipient') will reference
        //the 2 EmailRecipients above as $be2->loaded() = false. BaseEmail needs to check this!
        $be2 = new BaseEmail(self::$app->db);
        self::assertFalse($be2->send());
    }

    public function testEmailSendFail() {
        $this->_addStandardEmailAccount();
        $be = new BaseEmail(self::$app->db);
        $be->phpMailer = new class(self::$app) extends PHPMailer { public function send():bool {return false;}};
        $be->addRecipient('test2@easyoutdooroffice.com');
        $be->set('subject', __FUNCTION__);
        $be->save();
        $messages = self::$app->userMessages;
        self::assertFalse($be->send());
        //should add message to app
        $new_messages = self::$app->userMessages;
        self::assertEquals(count($messages) + 1, count($new_messages));
    }

    public function testGetModelVars() {
        $this->_addStandardEmailAccount();
        $be = new BaseEmail(self::$app->db);
        $res = $be->getModelVars(new BaseModelB(self::$app->db));
        self::assertEquals(['name' => 'AName', 'time_test' => 'Startzeit', 'date_test' => 'Startdatum'], $res);

        $res = $be->getModelVars(new BaseModelA(self::$app->db));
        self::assertEquals(['name' => 'Name', 'firstname' => 'Vorname'], $res);
    }

    public function testGetModelVarsPrefix() {
        $this->_addStandardEmailAccount();
        $be = new BaseEmail(self::$app->db);
        $res = $be->getModelVars(new BaseModelA(self::$app->db), 'tour_');
        self::assertEquals(['tour_name' => 'Name', 'tour_firstname' => 'Vorname'], $res);
    }

    public function testgetTemplateEditVars() {
        $this->_addStandardEmailAccount();
        $be = new BaseEmail(self::$app->db);
        $be->model = new BaseModelA(self::$app->db);
        self::assertEquals(['BMACAPTION' => ['basemodela_name' => 'Name', 'basemodela_firstname' => 'Vorname']], $be->getTemplateEditVars());
    }

    public function testSendFromOtherEmailAccount() {
        $this->_addStandardEmailAccount();

        $ea = new EmailAccount(self::$app->db);
        $ea->set('name',        STD_EMAIL);
        $ea->set('sender_name', 'TESTSENDERNAME');
        $ea->set('user',        EMAIL_USERNAME);
        $ea->set('password',    EMAIL_PASSWORD);
        $ea->set('smtp_host',   EMAIL_HOST);
        $ea->set('smtp_port',   EMAIL_PORT);
        $ea->set('imap_host',   IMAP_HOST);
        $ea->set('imap_port',   IMAP_PORT);
        $ea->set('imap_sent_folder', IMAP_SENT_FOLDER);
        $ea->save();

        $be = new BaseEmail(self::$app->db);
        $be->addRecipient('test3@easyoutdooroffice.com');
        $be->set('email_account_id', $ea->get('id'));
        $be->set('subject', __FUNCTION__);

        self::assertTrue($be->send());
        self::assertEquals('TESTSENDERNAME', $be->phpMailer->FromName);
    }

    public function testGetDefaultEmailAccountId() {
        $be = new BaseEmail(self::$app->db);
        self::assertNull($be->getDefaultEmailAccountId());
        $this->_addStandardEmailAccount();
        self::assertNotEmpty($be->getDefaultEmailAccountId());
    }

    public function testGetAllImplementations() {
        $res = (new BaseEmail(self::$app->db))->getAllImplementations(
            [
                FILE_BASE_PATH . 'tests/TestClasses/BaseEmailTestClasses' => '\\PMRAtk\tests\\TestClasses\\BaseEmailTestClasses\\'
            ]
        );

        self::assertCount(2, $res);
        self::assertTrue($res['\\' . SomeBaseEmailImplementation::class] instanceof SomeBaseEmailImplementation);
    }

    public function testPassEmailTemplateId() {
        $et = new EmailTemplate(self::$app->db);
        $et->set('value', '{Subject}LALADU{/Subject}Hammergut');
        $et->save();
        $be = new SomeBaseEmailImplementation(self::$app->db, ['emailTemplateId' => $et->get('id')]);
        $be->loadInitialValues();
        self::assertEquals('LALADU', $be->get('subject'));
        self::assertEquals('Hammergut', $be->get('message'));
    }


    /**
     * TODO
     */
    /*public function testSignatureUsesLineBreaks() {
    }

    /**/
}
