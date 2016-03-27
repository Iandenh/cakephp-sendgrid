# CakePHP 3.1+ SendGrid plugin

This is a Sendgrid Email Transport plugin for CakePHP 3.

To install this plugin, you're best off using composer. Add:

    "iandenh/cakephp-sendgrid": "*"

to your `composer.json` file and run.

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

