<?php declare(strict_types=1);

namespace emailforatk;

use secondarymodelforatk\SecondaryModel;

class EmailTemplate extends SecondaryModel
{

    public $table = 'email_template';

    protected function init(): void
    {
        parent::init();

        $this->addFields(
            [
                [
                    'ident',
                    'type' => 'string',
                    'system' => true
                ],
            ]
        );

        $this->setOrder('ident');
    }
}
