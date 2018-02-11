<?php
namespace Sichikawa\LaravelSendgridDriver\Contracts;

interface EmailCheckerContract
{

    /**
     * Check whether an email address is valid or not
     * 
     * @param string $emailAddress  The email address
     * 
     * @return boolean
     */
    public function isValidEmail(string $emailAddress);

    /**
     * Get the valid emails addresses out of a list
     * 
     * @param array $emailsAddresses    The list of emails addresses
     * 
     * @return string[]
     */
    public function getValidEmails(array $emailsAddresses);
}
