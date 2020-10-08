<?php declare(strict_types=1);

namespace emailforatk;

use atk4\core\Exception;
use Throwable;

trait EmailThrowableToAdminTrait {

    /*
     * Sends an Email if an Exception was thrown
     * TODO: ALTER THAT, NOT AS TRAIT BUT IN APP. PHPMAILER SHOULD ONLY BE IN ONE PLACE, IN APP
     */
    public function sendErrorEmailToAdmin(Throwable $e, string $subject, array $additional_recipients = []) {
        if(!isset($this->phpMailer)
        || !$this->phpMailer instanceof PHPMailer) {
            $this->phpMailer = new PHPMailer($this->app);
        }
        //always send to tech admin
        $this->phpMailer->addAddress(TECH_ADMIN_EMAIL);
        foreach ($additional_recipients as $email_address) {
            $this->phpMailer->addAddress($email_address);
        }
        $this->phpMailer->Subject = $subject;
        $this->phpMailer->setBody('Folgender Fehler ist aufgetreten: <br />' .
            ($e instanceOf Exception ? $e->getHTML() : $e->getMessage() . '<br />Line: ' . $e->getLine() . '<br />' . nl2br($e->getTraceAsString())) . '<br />Der Technische Administrator ' . TECH_ADMIN_NAME . ' wurde informiert.');
        $this->phpMailer->send();
    }
}