<?php
namespace Sichikawa\LaravelSendgridDriver\Helpers;

trait MailExtender
{

    /**
     * Embed the SendGrid params to the email message
     * 
     * @param null|array $params
     * 
     * @return $this
     */
    public function sendgrid($params)
    {
        $this->withSwiftMessage(function (Swift_Message $message) use ($params) {
            $message->embed(new Swift_Image($params));
        });

        return $this;
    }
}
