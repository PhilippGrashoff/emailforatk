<?php

declare(strict_types=1);

namespace emailforatk;

use atk4\core\AppScopeTrait;
use atk4\core\DIContainerTrait;
use atk4\core\Exception;
use atk4\ui\App;
use Throwable;

class PHPMailer extends \PHPMailer\PHPMailer\PHPMailer {

    use DIContainerTrait;
    use AppScopeTrait;

    //the PMRAtk\Data\Email\EmailAccount to send from. If not set, use first one
    public $emailAccount;

    //header and footer which will be added to email before send
    public $header;
    public $footer;
    public $headerTemplate  = 'default_header.html';
    public $footerTemplate  = 'default_footer.html';


    public $addImapDebugInfo = false;
    public $imapErrors = [];
    public $appendedByIMAP = false;


    /*
     *
     */
    public function __construct(App $app, array $defaults = []) {
        $this->app = $app;
        $this->setDefaults($defaults);
        $this->CharSet = 'utf-8';
        //set SMTP sending
        $this->isSMTP();
        $this->SMTPDebug = 0;
        $this->SMTPAuth = true;

        parent::__construct();

        $this->header = $this->app->loadEmailTemplate($this->headerTemplate);
        $this->header->setSTDValues();
        $this->footer = $this->app->loadEmailTemplate($this->footerTemplate);
        $this->footer->setSTDValues();
    }


    /*
     *
     */
    public function setBody(string $body) {
        $this->Body = $this->header->render().$body.$this->footer->render();
        $this->AltBody = $this->html2text($this->Body);
    }


    /*
     * for testing: add uuid to email subject if set
     */
    public function send():bool {
        $this->_setEmailAccount();
        return parent::send();
    }


    /*
     * load default EmailAccount if none is set
     */
    protected function _setEmailAccount() {
        if($this->emailAccount instanceof EmailAccount
            && $this->emailAccount->loaded()) {
            $this->_copySettingsFromEmailAccount();
            return;
        }
        //maybe just the ID of the emailaccount was passed?
        elseif(is_scalar($this->emailAccount)) {
            $val = $this->emailAccount;
            $this->emailAccount = new EmailAccount($this->app->db);
            if ($val) {
                $this->emailAccount->tryLoad($val);
                if ($this->emailAccount->loaded()) {
                    $this->_copySettingsFromEmailAccount();
                    return;
                }
            }
        }

        //none found? load default
        $this->emailAccount = new EmailAccount($this->app->db);
        $this->emailAccount->tryLoadAny();

        if(!$this->emailAccount->loaded()) {
            throw new Exception('No EmailAccount to send from found!');
        }
        $this->_copySettingsFromEmailAccount();
    }


    /**
     *
     */
    protected function _copySettingsFromEmailAccount() {
        $this->Host = $this->emailAccount->get('smtp_host');
        $this->Port = $this->emailAccount->get('smtp_port');
        $this->Username = $this->emailAccount->get('user');
        $this->Password = $this->emailAccount->get('password');
        $this->setFrom($this->emailAccount->get('name'), $this->emailAccount->get('sender_name'));
        if($this->emailAccount->get('allow_self_signed_ssl')) {
            $this->SMTPOptions = [
                'ssl' => [
                    'verify_peer' => false,
                    'verify_peer_name' => false,
                    'allow_self_signed' => true
                ]
            ];
        }
    }


    /**
     * add Email to IMAP if set
     * TODO: Find some nice Lib for this
     */
    public function addSentEmailByIMAP():bool {
        $this->_setEmailAccount();
        if(!$this->emailAccount->get('imap_host')
            || !$this->emailAccount->get('imap_port')) {
            $this->appendedByIMAP = false;
            return $this->appendedByIMAP;
        }
        $imap_mailbox = '{'.$this->emailAccount->get('imap_host').':'.$this->emailAccount->get('imap_port').'/imap/ssl}'.$this->emailAccount->get('imap_sent_folder');

        try {
            $imapStream = imap_open(
                $imap_mailbox,
                $this->emailAccount->get('user'),
                $this->emailAccount->get('password'));
            $this->appendedByIMAP = imap_append($imapStream, $imap_mailbox, $this->getSentMIMEMessage());
            if($this->addImapDebugInfo) {
                $imapErrors = imap_errors();
                $imapNotices = imap_alerts();
                if($imapErrors) {
                    $this->imapErrors = $imapErrors;
                }
                if($imapNotices) {
                    $this->imapErrors = array_merge($this->imapErrors, $imapNotices);
                }
                $mailboxes = imap_list($imapStream, '{'.$this->emailAccount->get('imap_host').':'.$this->emailAccount->get('imap_port').'/imap/ssl}', '*');
                if(is_array($mailboxes)) {
                    $this->imapErrors[] = 'Vorhandene Mailboxen: '.implode(', ', $mailboxes);
                }
            }
            imap_close($imapStream);
        }
        catch (Throwable $e) {
            $this->appendedByIMAP = false;
        }

        return $this->appendedByIMAP;
    }
}