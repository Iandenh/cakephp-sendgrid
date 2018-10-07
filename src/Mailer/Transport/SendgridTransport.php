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

use Cake\Http\Client;
use Cake\Mailer\AbstractTransport;
use Cake\Mailer\Email;
use Cake\Network\Exception\SocketException;
use SendgridEmail\Mailer\Exception\SendgridEmailException;

/**
 * Send mail using SendGrid
 */
class SendgridTransport extends AbstractTransport
{
    /**
     * Http client
     *
     * @var \Cake\Network\Http\Client
     */
    private $http;

    /**
     * Transport config for this class
     *
     * @var array
     */
    protected $_defaultConfig = [
        'api_key' => null,
    ];

    /**
     * Send mail
     *
     * @param \Cake\Mailer\Email $email Email instance.
     * @return array
     * @throws \SendgridEmail\Mailer\Exception\SendgridEmailException
     */
    public function send(Email $email)
    {
        $message = [
            'html' => $email->message(Email::MESSAGE_HTML),
            'text' => $email->message(Email::MESSAGE_TEXT),
            'subject' => mb_decode_mimeheader($email->getSubject()), // Decode because SendGrid is encoding
            'from' => key($email->getFrom()),
            'fromname' => current($email->getFrom()),
            'to' => [],
            'toname' => [],
            'cc' => [],
            'ccname' => [],
            'bcc' => [],
            'bccname' => [],
            'replyto' => $email->getReplyTo() ? array_values($email->getReplyTo())[0] : key($email->getFrom()),
        ];
        // Add recipients
        $recipients = [
            'to' => $email->getTo(),
            'cc' => $email->getCc(),
            'bcc' => $email->getBcc()
        ];
        foreach ($recipients as $type => $emails) {
            foreach ($emails as $mail => $name) {
                $message[$type][] = $mail;
                $message[$type . 'name'][] = $name;
            }
        }

        // Create a new scoped Http Client
        $this->http = new Client([
            'host' => 'api.sendgrid.com',
            'scheme' => 'https',
            'headers' => [
                'User-Agent' => 'CakePHP SendGrid Plugin'
            ]
        ]);

        $message = $this->_attachments($email, $message);

        return $this->_send($message);
    }

    /**
     * Send normal email
     *
     * @param  array $message The Message Array
     * @return array Returns an array with the results from the SendGrid API
     * @throws \SendgridEmail\Mailer\Exception\SendgridEmailException
     */
    protected function _send($message)
    {
        $options = [
            'headers' => ['Authorization' => 'Bearer ' . $this->getConfig('api_key')]
        ];
        $response = $this->http->post('/api/mail.send.json', $message, $options);
        if ($response->getStatusCode() !== 200) {
            throw new SendgridEmailException(sprintf(
                'SendGrid error %s %s: %s',
                $response->getStatusCode(),
                $response->getReasonPhrase(),
                implode('; ', $response->json['errors'])
            ));
        }

        return $response->json;
    }

    /**
     * Format the attachments
     *
     * @param \Cake\Mailer\Email $email Email instance.
     * @param array $message A message array.
     * @return array Message
     */
    protected function _attachments(Email $email, array $message = [])
    {
        foreach ($email->getAttachments() as $filename => $attach) {
            $content = isset($attach['data']) ? base64_decode($attach['data']) : file_get_contents($attach['file']);
            $message['files'][$filename] = $content;
            if (isset($attach['contentId'])) {
                $message['content'][$filename] = $attach['contentId'];
            }
        }

        return $message;
    }
}
