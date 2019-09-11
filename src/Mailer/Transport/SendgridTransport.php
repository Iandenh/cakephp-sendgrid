<?php
/**
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 *
 * @author        Ian den Hartog (https://iandh.nl)
 * @link          https://iandh.nl
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */

namespace SendgridEmail\Mailer\Transport;

use Cake\Mailer\AbstractTransport;
use Cake\Mailer\Email;
use Cake\Mailer\Message;
use SendgridEmail\Mailer\Exception\SendgridEmailException;
use SendGrid\Mail\Attachment;
use SendGrid\Mail\Mail;

/**
 * Send mail using SendGrid
 */
class SendgridTransport extends AbstractTransport
{
    /**
     * Transport config for this class
     *
     * @var array
     */
    protected $_defaultConfig = [
        'api_key' => null,
        'click_tracking' => true,
        'open_tracking' => true,
    ];

    /**
     * Send mail
     *
     * @param \Cake\Mailer\Email $email Email instance.
     * @return array
     * @throws \SendgridEmail\Mailer\Exception\SendgridEmailException
     * @throws \SendGrid\Mail\TypeException
     */
    public function send(Message $email): array
    {
        $sendgridMail = new Mail();
        $sendgridMail->setFrom(key($email->getFrom()), current($email->getFrom()));
        $sendgridMail->setSubject($email->getSubject());

        $sendgridMail->addTos($email->getTo());
        $sendgridMail->addBccs($email->getBcc());
        $sendgridMail->addCcs($email->getCc());
        $sendgridMail->setReplyTo($email->getReplyTo() ? array_values($email->getReplyTo())[0] : key($email->getFrom()));

        if (!empty($email->message(Email::MESSAGE_TEXT))) {
            $sendgridMail->addContent("text/plain", $email->message(Email::MESSAGE_TEXT));
        }

        if (!empty($email->message(Email::MESSAGE_HTML))) {
            $sendgridMail->addContent("text/html", $email->message(Email::MESSAGE_HTML));
        }

        $sendgridMail->addAttachments($this->_attachments($email));
        $sendgridMail->setClickTracking($this->getConfig('click_tracking'));
        $sendgridMail->setOpenTracking($this->getConfig('open_tracking'));

        return $this->_send($sendgridMail);
    }

    /**
     * Send normal email
     *
     * @param Mail $email the sendgrid api
     * @return array Returns an array with the results from the SendGrid API
     */
    protected function _send(Mail $email): array
    {
        $sendgrid = new \SendGrid($this->getConfig('api_key'));
        $response = $sendgrid->send($email);
        if ($response->statusCode() >= 400) {
            $errors = [];
            foreach (json_decode($response->body())->errors as $error) {
                $errors[] = $error->field . ": " . $error->message;
            }

            throw new SendgridEmailException(sprintf(
                'SendGrid error %s: %s',
                $response->statusCode(),
                implode('; ', $errors)
            ));
        }

        return json_decode($response->body(), true);
    }

    /**
     * Format the attachments
     *
     * @param \Cake\Mailer\Message $email Message instance.
     * @return array Attachments
     */
    protected function _attachments(Message $email): array
    {
        $attachments = [];
        foreach ($email->getAttachments() as $filename => $attach) {
            $content = isset($attach['data']) ? base64_decode($attach['data']) : file_get_contents($attach['file']);
            $attachment = new Attachment($content, $attach['mimetype'], $filename);

            if (isset($attach['contentId'])) {
                $attachment->setContentID($attach['contentId']);
                $attachment->setDisposition('inline');
            }
            $attachments[] = $attachment;
        }

        return $attachments;
    }
}
