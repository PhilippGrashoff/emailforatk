<?php declare(strict_types=1);

namespace emailforatk;

use atk4\data\Model;
use traitsforatkdata\EncryptedFieldTrait;

class EmailAccount extends Model
{

    use EncryptedFieldTrait;

    public $table = 'email_account';


    protected function init(): void
    {
        parent::init();
        $this->addFields(
            [
                [
                    'name',
                    'type' => 'string',
                    'caption' => 'Email-Adresse'
                ],
                [
                    'sender_name',
                    'type' => 'string',
                    'caption' => 'Name des Versenders'
                ],
                [
                    'details',
                    'type' => 'text'
                ],
                [
                    'credentials',
                    'type' => 'text',
                    'system' => true
                ],
                [
                    'user',
                    'type' => 'string',
                    'caption' => 'Benutzername',
                    'system' => true,
                    'never_persist' => true,
                    'ui' => ['editable' => true]
                ],
                [
                    'password',
                    'type' => 'string',
                    'caption' => 'Passwort',
                    'system' => true,
                    'never_persist' => true,
                    'ui' => ['editable' => true]
                ],
                [
                    'imap_host',
                    'type' => 'string',
                    'caption' => 'IMAP Host',
                    'system' => true,
                    'never_persist' => true,
                    'ui' => ['editable' => true]
                ],
                [
                    'imap_port',
                    'type' => 'string',
                    'caption' => 'IMAP Port',
                    'system' => true,
                    'never_persist' => true,
                    'ui' => ['editable' => true]
                ],
                [
                    'imap_sent_folder',
                    'type' => 'string',
                    'caption' => 'IMAP: Gesendet-Ordner',
                    'system' => true,
                    'never_persist' => true,
                    'ui' => ['editable' => true]
                ],
                [
                    'smtp_host',
                    'type' => 'string',
                    'caption' => 'SMTP Host',
                    'system' => true,
                    'never_persist' => true,
                    'ui' => ['editable' => true]
                ],
                [
                    'smtp_port',
                    'type' => 'string',
                    'caption' => 'SMTP Port',
                    'system' => true,
                    'never_persist' => true,
                    'ui' => ['editable' => true]
                ],
                [
                    'allow_self_signed_ssl',
                    'type' => 'integer',
                    'caption' => 'SSL: Self-signed Zertifikate erlauben',
                    'system' => true,
                    'never_persist' => true,
                    'ui' => ['editable' => true, 'form' => ['DropDown', 'values' => [0 => 'Nein', '1' => 'Ja']]]
                ],
            ]
        );

        $this->encryptField($this->getField('credentials'), ENCRYPTFIELD_KEY);

        //after load, unserialize value field
        $this->onHook(
            Model::HOOK_AFTER_LOAD,
            function (self $model) {
                $a = unserialize($model->get('credentials'));
                foreach ($a as $key => $value) {
                    if ($model->hasField($key)) {
                        $model->set($key, $value);
                    }
                }
            }
        );

        //before save, serialize value field
        $this->onHook(
            Model::HOOK_BEFORE_SAVE,
            function (self $model) {
                $a = [
                    'user' => $model->get('user'),
                    'password' => $model->get('password'),
                    'imap_host' => $model->get('imap_host'),
                    'imap_port' => $model->get('imap_port'),
                    'imap_sent_folder' => $model->get('imap_sent_folder'),
                    'smtp_host' => $model->get('smtp_host'),
                    'smtp_port' => $model->get('smtp_port'),
                    'allow_self_signed_ssl' => $model->get('allow_self_signed_ssl'),
                ];

                $model->set('credentials', serialize($a));
            }
        );
    }
}