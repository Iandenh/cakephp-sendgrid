<?php

namespace SendGridEmail\Network\Email;

use Cake\Mailer\AbstractTransport;
use Cake\Mailer\Email;
use Cake\Network\Exception\SocketException;
use Cake\Network\Http\Client;
use Cake\Utility\Hash;

/**
 * Created by PhpStorm.
 * User: ian
 * Date: 26-2-16
 * Time: 11:19
 * @property \Cake\Network\Http\Client $http
 */
class SendGridTransport extends AbstractTransport
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
            'subject' => mb_decode_mimeheader($email->subject()), // Decode because Mandrill is encoding
            'from' => key($email->from()), // Make sure the domain is registered and verified within Mandrill
            'fromname' => current($email->from()),
            'to' => [],
            'toname' => [],
            'cc' => [],
            'ccname' => [],
            'bcc' => [],
            'bccname' => [],
            'replyto' => $email->replyTo(),
            'recipient_metadata' => [],
            'attachments' => [],
            'images' => []
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
        $this->_send($message);
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
        if (!$response) {
            throw new SocketException($response->code);
        }

        return $response->json;
    }

    /**
     * Format the attachments
     *
     * @param Email $email
     * @param type $message
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