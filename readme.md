# CakePHP SendGrid plugin

This is a Sendgrid Email Transport plugin for CakePHP 3, 4 and 5.
This branch contains the code for CakePHP 5.

## Installation

You can install this plugin into your CakePHP application using [composer](https://getcomposer.org).

```
composer require iandenh/cakephp-sendgrid
```

## Setting up your CakePHP application ##
In you `app.php`:


    'Email' => [
        'Sendgrid' => [
            'transport' => 'SendgridEmail',
        ],
    ],
    'EmailTransport' => [
        'SendgridEmail' => [
            'className' => 'SendgridEmail.Sendgrid',
            'api_key' => 'API_KEY_HERE',
            // Config options below are added in version 2
            "click_tracking" => false, // true or false to toggle sendgrids click tracking
            "open_tracking" => false, // true or false to toggle sendgrids open tracking
        ]
    ]


Inspired by [Lennaert/cakephp3-mandrill](https://github.com/Lennaert/cakephp3-mandrill)
