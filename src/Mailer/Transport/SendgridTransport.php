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
use Cake\Mailer\Message;
use SendGrid;
use SendgridEmail\Mailer\Exception\SendgridEmailException;
use SendGrid\Mail\Attachment;
use SendGrid\Mail\Mail;

/**
 * Send mail using SendGrid
 */
class SendgridTransport extends AbstractTransport
{
    /**
     * Default config for this class
     *
     * @var array
     */
    protected array $_defaultConfig = [
        'api_key' => null,
        'click_tracking' => true,
        'open_tracking' => true,
    ];

    /**
     * Send mail
     *
     * @param \Cake\Mailer\Message $message Email message
     * @return array
     * @throws \SendgridEmail\Mailer\Exception\SendgridEmailException
     * @throws \SendGrid\Mail\TypeException
     */
    public function send(Message $message): array
    {
        $sendgridMail = new Mail();
        $sendgridMail->setFrom(key($message->getFrom()), current($message->getFrom()));
        $sendgridMail->setSubject($message->getOriginalSubject());

        $sendgridMail->addTos($this->wrapIllegalLocalPartInDoubleQuote($message->getTo()));

        if (!empty($message->getCc())) {
            $sendgridMail->addCcs($this->wrapIllegalLocalPartInDoubleQuote($message->getCc()));
        }

        if (!empty($message->getBcc())) {
            $sendgridMail->addBccs($this->wrapIllegalLocalPartInDoubleQuote($message->getBcc()));
        }

        $sendgridMail->setReplyTo($message->getReplyTo() ? key($message->getReplyTo()) : key($message->getFrom()));

        if (!empty($message->getBodyText())) {
            $sendgridMail->addContent('text/plain', $message->getBodyText());
        }

        if (!empty($message->getBodyHtml())) {
            $sendgridMail->addContent('text/html', $message->getBodyHtml());
        }

        $sendgridMail->addAttachments($this->attachments($message));
        $sendgridMail->setClickTracking($this->getConfig('click_tracking'));
        $sendgridMail->setOpenTracking($this->getConfig('open_tracking'));

        return $this->sendMail($sendgridMail);
    }

    /**
     * Send normal email
     *
     * @param \SendGrid\Mail\Mail $email the sendgrid api
     * @return array Returns an array with the results from the SendGrid API
     */
    private function sendMail(Mail $email): array
    {
        $sendgrid = new SendGrid($this->getConfig('api_key'));
        $response = $sendgrid->send($email);

        if ($response->statusCode() >= 400) {
            $errors = [];
            foreach (json_decode($response->body(), false)->errors as $error) {
                $errors[] = $error->field . ": " . $error->message;
            }

            throw new SendgridEmailException(sprintf(
                'SendGrid error %s: %s',
                $response->statusCode(),
                implode('; ', $errors)
            ));
        }

        return ['message' => 'success'];
    }

    /**
     * Format the attachments
     *
     * @param \Cake\Mailer\Message $email Message instance.
     * @return \SendGrid\Mail\Attachment[]
     * @throws \SendGrid\Mail\TypeException
     */
    private function attachments(Message $email): array
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

    /**
     * Wrap illegal local part in double quote
     *
     * @param array $rawArray Array with email as key, name as value
     * @return array $array
     */
    private function wrapIllegalLocalPartInDoubleQuote(array $rawArray): array
    {
        $array = [];
        foreach ($rawArray as $mail => $name) {
            if (preg_match('/^(\.[^@]*|(?=[^@]*\.{2,})[^@]*|[^@]*\.)@.*$/', $mail)) {
                $mail = preg_replace('/([^@]+)(@.*)$/', '"$1"$2', $mail);
            }
            $array[$mail] = $name;
        }

        return $array;
    }
}
