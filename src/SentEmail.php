<?php

declare(strict_types=1);

namespace emailforatk;

use atk4\data\Model;
use secondarymodelforatk\SecondaryModel;

class SentEmail extends SecondaryModel
{

    public $table = 'sent_email';

    protected function init(): void
    {
        parent::init();

        $this->addFields(
            [
                [
                    'param1',
                    'type' => 'string'
                ],
                //TODO: Use created_date? sent_date seems unneccessary
                [
                    'sent_date',
                    'type' => 'datetime',
                    'caption' => 'Gesendet am'
                ],
            ]
        );

        $this->setOrder(['sent_date' => 'desc']);

        $this->onHook(
            Model::HOOK_BEFORE_SAVE,
            function (self $model, $isUpdate) {
                if (is_null($model->get('sent_date'))) {
                    $model->set('sent_date', new \DateTime());
                }
            }
        );
    }
}
