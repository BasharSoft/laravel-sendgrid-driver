<?php
namespace Sichikawa\LaravelSendgridDriver;

use Illuminate\Mail\Mailable\Helpers;
use Sichikawa\LaravelSendgridDriver\Transport\SendgridTransport;
use Swift_Message;

trait MailExtender
{

    /**
     * 
     * 
     * @param null|array $params
     * 
     * @return $this
     */
    public function sendgrid($params)
    {
        $this->withSwiftMessage(function (Swift_Message $message) use ($params) {
            $message->embed(new \Swift_Image($params));
        });

        return $this;
    }
}
