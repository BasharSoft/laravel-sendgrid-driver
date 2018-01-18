Laravel SendGrid Driver
====

[![SensioLabsInsight](https://insight.sensiolabs.com/projects/4232643f-006c-473b-97ff-d0f67fa497ee/big.png)](https://insight.sensiolabs.com/projects/4232643f-006c-473b-97ff-d0f67fa497ee)
[![Build Status](https://scrutinizer-ci.com/g/s-ichikawa/laravel-sendgrid-driver/badges/build.png?b=master)](https://scrutinizer-ci.com/g/s-ichikawa/laravel-sendgrid-driver/build-status/master)

A Mail Driver with support for Sendgrid Web API, using the original Laravel API.
This library extends the original Laravel classes, so it uses exactly the same methods.

To use this package required your [Sendgrid Api Key](https://sendgrid.com/docs/User_Guide/Settings/api_keys.html).
Please make it [Here](https://app.sendgrid.com/settings/api_keys).

# Notification

If your project using guzzlehttp/guzzle 6.2.0 or less, you can use version [1.0.0](https://github.com/s-ichikawa/laravel-sendgrid-driver/tree/1.0.0)
But the old version has [security issues](https://github.com/guzzle/guzzle/releases/tag/6.2.1), 

# Install (Laravel)

Add the package to your composer.json and run composer update.
```json
"require": {
    "s-ichikawa/laravel-sendgrid-driver": "~2.0"
},
```

or installed with composer
```
$ composer require s-ichikawa/laravel-sendgrid-driver
```

# Install (Lumen)

Add the package to your composer.json and run composer update.
```json
"require": {
    "s-ichikawa/laravel-sendgrid-driver": ~2.0
},
```

or installed with composer
```
$ composer require s-ichikawa/laravel-sendgrid-driver
```

Add the sendgrid service provider in bootstrap/app.php
```php
$app->configure('mail');
$app->configure('services');
$app->register(Sichikawa\LaravelSendgridDriver\MailServiceProvider::class);

unset($app->availableBindings['mailer']);
```

Create mail config files.
config/mail.php
```
<?php
return [
    'driver' => env('MAIL_DRIVER', 'sendgrid'),
];
```

## Configure

.env
```
MAIL_DRIVER=sendgrid
SENDGRID_API_KEY='YOUR_SENDGRID_API_KEY'
SENDGRID_SANDBOX_MODE=false
```

config/services.php (In using lumen, require creating config directory and file.)
```
    'sendgrid'  => [
        'api_key'      => env('SENDGRID_API_KEY', ''),
        'sandbox_mode' => env('SENDGRID_SANDBOX_MODE', false),
    ],
```


config/mail.php
```
    'testing'       => [
        'is_enabled' => env('MAIL_TEST_MODE', false),
        'address'    => env('MAIL_TEST_ADDRESS', 'testing@basharsoft.com')
    ]
```

## Request Body Parameters

Every request made to /v3/mail/send will require a request body formatted in JSON containing your email’s content and metadata.
Required parameters are set by Laravel's usually mail sending, but you can also use useful features like "categories" and "send_at".

```
\Mail::send('view', $data, function (Message $message) {
    $message
        ->to('foo@example.com', 'foo_name')
        ->from('bar@example.com', 'bar_name')
        ->embedData([
            'categories' => ['user_group1'],
            'send_at' => $send_at->getTimestamp(),
        ], 'sendgrid/x-smtpapi');
});
```

more info
https://sendgrid.com/docs/API_Reference/Web_API_v3/Mail/index.html#-Request-Body-Parameters


## API v3

```
\Mail::send('view', $data, function (Message $message) {
    $message
        ->to('foo@example.com', 'foo_name')
        ->from('bar@example.com', 'bar_name')
        ->replyTo('foo@bar.com', 'foobar');
        ->embedData([
            'personalizations' => [
                [
                    'to' => [
                        'email' => 'user1@example.com',
                        'name' => 'user1',
                    ],
                    'substitutions' => [
                        '-email-' => 'user1@example.com',
                    ],
                ],
                [
                    'to' => [
                        'email' => 'user2@example.com',
                        'name' => 'user2',
                    ],
                    'substitutions' => [
                        '-email-' => 'user2@example.com',
                    ],
                ],
            ],
            'categories' => ['user_group1'],
            'custom_args' => [
                'user_id' => "123" // Make sure this is a string value
            ]
        ], 'sendgrid/x-smtpapi');
});
```

- custom_args values have to be strings. Sendgrid API gives a non-descriptive error message when you enter non-string values.


## Use in Mailable

```php
<?
use Sichikawa\LaravelSendgridDriver\Helpers\MailExtender;
use Sichikawa\LaravelSendgridDriver\Helpers\MailParams;

class SendGridSample extends Mailable
{
    use MailExtender;
    
    public function build()
    {

        $mailParams = new MailParams();
        $mailParams
            ->setSendAt(time())
            ->addCategory('Test')
            ->addCategory('Test2')
        ;

        return $this
            ->view('template name')
            ->subject('subject')
            ->from('from@example.com')
            ->to(['to@example.com'])
            ->withParams($mailParams);

    }
}
```

