<?php
/**
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 *
 * @author        Ian den Hartog (http://iandh.nl)
 * @link          http://iandh.nl
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace SendgridEmail\Mailer\Transport;

use Cake\Mailer\AbstractTransport;
use Cake\Mailer\Email;
use Cake\Network\Exception\InternalErrorException;
use Cake\Network\Exception\SocketException;
use Cake\Network\Http\Client;
use Cake\Utility\Hash;

/**
 * @property \Cake\Network\Http\Client $http
 */
class SendgridTransport extends AbstractTransport
{
    public $http;

    public $transportConfig = [
        'api_key' => null,
    ];

    /**
     * Send mail
     *
     * @param \Cake\Mailer\Email $email Email instance.
     * @return array
     */
    public function send(Email $email)
    {
        $this->transportConfig = Hash::merge($this->transportConfig, $this->_config);

        $message = [
            'html' => $email->message(Email::MESSAGE_HTML),
            'text' => $email->message(Email::MESSAGE_TEXT),
            'subject' => mb_decode_mimeheader($email->subject()), // Decode because SendGrid is encoding
            'from' => key($email->from()),
            'fromname' => current($email->from()),
            'to' => [],
            'toname' => [],
            'cc' => [],
            'ccname' => [],
            'bcc' => [],
            'bccname' => [],
            'replyto' => !empty(array_keys($email->replyTo())[0])
                ? array_keys($email->replyTo())[0]
                : key($email->from()),
        ];
        // Add receipients
        foreach (['to', 'cc', 'bcc'] as $type) {
            foreach ($email->{$type}() as $mail => $name) {
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
     * @throws SocketException
     */
    protected function _send($message)
    {
        $options = [
            'headers' => ['Authorization' => 'Bearer ' . $this->transportConfig['api_key']]
        ];
        $response = $this->http->post('/api/mail.send.json', $message, $options);
        if ($response->code !== 200) {
            throw new SocketException(
                'SendGrid error ' .
                $response->getStatusCode() . ' ' .
                $response->getReasonPhrase() . ': ' .
                implode('; ', $response->json['errors'])
            );
        }

        return $response->json;
    }

    /**
     * Format the attachments
     *
     * @param \Cake\Mailer\Email $email Email instance.
     * @param type $message A message array.
     * @return array Message
     */
    protected function _attachments(Email $email, $message = [])
    {
        foreach ($email->attachments() as $filename => $attach) {
            $content = file_get_contents($attach['file']);
            $message['files'][$filename] = $content;
            if (isset($attach['contentId'])) {
                $message['content'][$filename] = $attach['contentId'];
            }
        }

        return $message;
    }
}
