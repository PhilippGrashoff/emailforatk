<?php declare(strict_types=1);

namespace emailforatk\tests;

use PMRAtk\Data\BaseModel;
use PMRAtk\Data\Email\EmailAccount;
use PMRAtk\tests\phpunit\TestCase;

/**
 * Class EANoDecrypt
 */
class EANoDecrypt extends BaseModel {

    public $table = 'email_account';


    protected function init(): void
    {
        parent::init();

        $this->addFields([
            ['credentials', 'type' => 'string'],
        ]);
    }
}


/**
 *
 */
class EmailAccountTest extends TestCase {

    /*
     *
     */
    public function testHooks() {
        $ea = new EmailAccount(self::$app->db);
        $ea->set('user',      'some1');
        $ea->set('password',  'some2');
        $ea->set('imap_host', 'some3');
        $ea->set('imap_port', 'some4');
        $ea->set('smtp_host', 'some5');
        $ea->set('smtp_port', 'some6');
        $ea->save();

        //check if its encrypted by using normal setting
        $setting = new EANoDecrypt(self::$app->db);
        $setting->load($ea->id);
        //if encrypted, it shouldnt be unserializable
        self::assertFalse(@unserialize($setting->get('credentials')));
        self::assertFalse(strpos($setting->get('credentials'), 'some1'));

        $ea2 = new EmailAccount(self::$app->db);
        $ea2->load($ea->id);
        self::assertEquals($ea2->get('user'),       'some1');
        self::assertEquals($ea2->get('password'),   'some2');
        self::assertEquals($ea2->get('imap_host'),  'some3');
        self::assertEquals($ea2->get('imap_port'),  'some4');
        self::assertEquals($ea2->get('smtp_host'),  'some5');
        self::assertEquals($ea2->get('smtp_port'),  'some6');
    }
}
