<?php
namespace Sichikawa\LaravelSendgridDriver\Providers;

class MailServiceProvider extends \Illuminate\Mail\MailServiceProvider
{
    public function register()
    {
        parent::register();

        $this->app->register(SendgridTransportServiceProvider::class);
    }
}
