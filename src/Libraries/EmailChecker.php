<?php
namespace Sichikawa\LaravelSendgridDriver\Libraries;

use Sichikawa\LaravelSendgridDriver\Contracts\EmailCheckerContract;

class EmailChecker implements EmailCheckerContract
{

    /**
     * {@inheritdoc}
     */
    public function isValidEmail(string $emailAddress)
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function getValidEmails(array $emailsAddresses)
    {
        return $emailsAddresses;
    }
}
