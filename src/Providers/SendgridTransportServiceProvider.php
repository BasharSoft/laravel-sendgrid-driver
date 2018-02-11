<?php
namespace Sichikawa\LaravelSendgridDriver\Providers;

use Illuminate\Mail\TransportManager;
use Illuminate\Support\ServiceProvider;
use SendGrid;
use Sichikawa\LaravelSendgridDriver\Contracts\EmailCheckerContract;
use Sichikawa\LaravelSendgridDriver\Transport\SendgridTransport;
use function app;

class SendgridTransportServiceProvider extends ServiceProvider
{

    /**
     * Register the Swift Transport instance.
     *
     * @return void
     */
    public function register()
    {
        $this->app->afterResolving(TransportManager::class, function(TransportManager $manager) {
            $this->extendTransportManager($manager);
        });
    }

    public function extendTransportManager(TransportManager $manager)
    {
        $manager->extend('sendgrid', function() {
            $sendgridConfig = $this->app['config']->get('services.sendgrid', []);
            $mailConfig     = $this->app['config']->get('mail', []);

            $sendgridApiKey = $sendgridConfig['api_key'];

            $sendgrid = new SendGrid($sendgridApiKey);

            $emailChecker = app()->make(EmailCheckerContract::class);

            return new SendgridTransport($sendgrid, $emailChecker, $sendgridConfig, $mailConfig);
        });
    }
}
