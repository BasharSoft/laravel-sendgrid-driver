<?php
namespace Sichikawa\LaravelSendgridDriver\Providers;

use Illuminate\Support\ServiceProvider;
use Sichikawa\LaravelSendgridDriver\Contracts\EmailCheckerContract;
use Sichikawa\LaravelSendgridDriver\Libraries\EmailChecker;

class EmailCheckerServiceProvider extends ServiceProvider
{

    /**
     * Register Email Checker service.
     *
     * @return void
     */
    public function register()
    {
        $this->app->bind(
            EmailCheckerContract::class, EmailChecker::class
        );
    }
}
