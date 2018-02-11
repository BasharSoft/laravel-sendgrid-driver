<?php
namespace Sichikawa\LaravelSendgridDriver\Providers;

class MailServiceProvider extends \Illuminate\Mail\MailServiceProvider
{

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->app->register(EmailCheckerServiceProvider::class);

        parent::register();

        $this->app->register(SendgridTransportServiceProvider::class);
    }
}
