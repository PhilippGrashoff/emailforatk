<?php declare(strict_types=1);

namespace emailforatk;

use secondarymodelforatk\SecondaryModel;

class EmailAddress extends SecondaryModel
{
    public $table = 'email_address';
    public $caption = 'Email-Adresse';
}