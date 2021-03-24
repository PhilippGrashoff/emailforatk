<?php declare(strict_types=1);

namespace emailforatk;

use Atk4\Data\Model;

class EmailRecipient extends Model
{

    public $table = 'email_recipient';


    protected function init(): void
    {
        parent::init();
        $this->addFields(
            [
                [
                    'email',
                    'type' => 'string',
                    'caption' => 'Email-Adresse'
                ],
                [
                    'firstname',
                    'type' => 'string',
                    'caption' => 'Vorname'
                ],
                [
                    'lastname',
                    'type' => 'string',
                    'caption' => 'Nachname'
                ],
            ]
        );
    }
}