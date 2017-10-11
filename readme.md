# CakePHP 3.1+ SendGrid plugin

This is a Sendgrid Email Transport plugin for CakePHP 3.

## Installation

You can install this plugin into your CakePHP application using [composer](http://getcomposer.org).

```
composer require iandenh/cakephp-sendgrid
```

## Setting up your CakePHP application ##


    'Email' => [
        'Sendgrid' => [
            'transport' => 'SendgridEmail',
        ],
        
    ],
    'EmailTransport' => [
        'SendgridEmail' => [
            'className' => 'SendgridEmail.Sendgrid',
            'api_key' => 'API_KEY_HERE'
        ]
    ]


Based of [Lennaert/cakephp3-mandrill](https://github.com/Lennaert/cakephp3-mandrill)
