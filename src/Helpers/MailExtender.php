<?php
namespace Sichikawa\LaravelSendgridDriver\Helpers;

use Swift_Image;
use Swift_Message;

trait MailExtender
{

    /**
     * Embed the mail params object to the email message
     * 
     * @param null|array $params
     * 
     * @return $this
     */
    public function withParams(MailParams $params)
    {
        $this->withSwiftMessage(function (Swift_Message $message) use ($params) {
            $message->embed(new Swift_Image($params));
        });

        return $this;
    }
}
