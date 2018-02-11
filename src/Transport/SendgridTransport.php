<?php
namespace Sichikawa\LaravelSendgridDriver\Transport;

use DateTime;
use Exception;
use Illuminate\Mail\Transport\Transport;
use Illuminate\Support\Collection;
use SendGrid;
use SendGrid\Attachment;
use SendGrid\Content;
use SendGrid\Email;
use SendGrid\Mail;
use SendGrid\MailSettings;
use SendGrid\Personalization;
use SendGrid\ReplyTo;
use SendGrid\SandBoxMode;
use Sichikawa\LaravelSendgridDriver\Helpers\MailParams;
use Swift_Attachment;
use Swift_Image;
use Swift_Mime_SimpleMessage;
use Swift_MimePart;
use function collect;

class SendgridTransport extends Transport
{

    const MAXIMUM_FILE_SIZE = 7340032;

    protected $numberOfRecipients = 0;

    /**
     * The Sendgrid config array
     * 
     * @var Collection 
     */
    protected $sendgridConfig;

    /**
     * The Laravel mail config array
     * 
     * @var Collection 
     */
    protected $mailConfig;

    /**
     * The SendGrid client object
     * 
     * @var SendGrid
     */
    protected $sendgridClient;

    public function __construct($sendgridClient, $sendgridConfig, $mailConfig)
    {
        $this->sendgridClient = $sendgridClient;
        $this->sendgridConfig = collect($sendgridConfig);
        $this->mailConfig     = collect($mailConfig);
    }

    /**
     * {@inheritdoc}
     */
    public function send(Swift_Mime_SimpleMessage $message, &$failedRecipients = null)
    {
        $this->beforeSendPerformed($message);

        $mail = $this->buildMail($message);

        $response = $this->sendgridClient->client->mail()->send()->post($mail);

        if (method_exists($response, 'getHeaderLine')) {
            $message->getHeaders()->addTextHeader('X-Message-Id', $response->getHeaderLine('X-Message-Id'));
        }

        if (is_callable([$this, "sendPerformed"])) {
            $this->sendPerformed($message);
        }

        if (is_callable([$this, "numberOfRecipients"])) {
            return $this->numberOfRecipients ?: $this->numberOfRecipients($message);
        }

        return $response;
    }

    /**
     * Build the mail object
     * 
     * @param Swift_Mime_SimpleMessage $message
     * 
     * @return Mail
     */
    protected function buildMail(Swift_Mime_SimpleMessage $message)
    {
        $from     = $this->getFrom($message);
        $subject  = $message->getSubject();
        $contents = $this->getContents($message);

        $mail = new Mail($from, $subject, null, null);

        // Set the mail content
        $mail->contents = [];
        foreach ($contents as $content) {
            $mail->addContent($content);
        }

        // Set the reply to field
        if ($replyToEmail = $this->getReplyTo($message)) {
            $mail->setReplyTo($replyToEmail);
        }

        // Set the attachments
        $attachments = $this->getAttachments($message);
        foreach ($attachments as $attachment) {
            $mail->addAttachment($attachment);
        }

        // Set the personalizations array including the main personalization
        $mail->personalization = [];

        $personalizations = $this->getPersonalizations($message);
        foreach ($personalizations as $personalization) {
            $mail->addPersonalization($personalization);
        }

        // Set the mail settings
        $mail->setMailSettings($this->getMailSettings());

        // Set the mail extra params like the category & send at
        $this->setMailParams($mail, $message);

        return $mail;
    }

    /**
     * Extract the mail personalizations from the message
     * 
     * @param Swift_Mime_SimpleMessage $message
     * 
     * @return Personalization
     */
    protected function getPersonalizations(Swift_Mime_SimpleMessage $message)
    {
        $personalizations = [];

        $personalization = new Personalization();
        $this->setRecipients($personalization, $message);

        $personalizations[] = $personalization;

        return $personalizations;
    }

    /**
     * Get From Addresses.
     *
     * @param Swift_Mime_SimpleMessage $message
     * @return array
     */
    protected function getFrom(Swift_Mime_SimpleMessage $message)
    {
        $fromEmail = null;

        if ($message->getFrom()) {
            foreach ($message->getFrom() as $email => $name) {
                $fromEmail = new Email($name, $email);
                break;
            }
        } else {
            $defaultFrom = $this->mailConfig->get('from');
            $fromEmail   = new Email($defaultFrom['name'], $defaultFrom['address']);
        }

        return $fromEmail;
    }

    /**
     * Get ReplyTo Addresses.
     *
     * @param Swift_Mime_SimpleMessage $message
     * 
     * @return Email
     */
    protected function getReplyTo(Swift_Mime_SimpleMessage $message)
    {
        $replyTo = null;

        if ($message->getReplyTo()) {
            foreach ($message->getReplyTo() as $email => $name) {
                $replyTo = new ReplyTo($email, $name);
                break;
            }
        }

        return $replyTo;
    }

    /**
     * Get contents.
     *
     * @param Swift_Mime_SimpleMessage $message
     * 
     * @return Content[]
     */
    protected function getContents(Swift_Mime_SimpleMessage $message)
    {
        $contents = [];

        $contentType = $message->getContentType();
        switch ($contentType) {
            case 'text/plain':
                $contents[] = new Content('text/plain', $message->getBody());
                break;
            case 'text/html':
                $contents[] = new Content('text/html', $message->getBody());
                break;
        }

        if (empty($contents)) {
            // Following RFC 1341, text/html after text/plain in multipart
            foreach ($message->getChildren() as $child) {
                if ($child instanceof Swift_MimePart && $child->getContentType() === 'text/plain') {
                    $contents[] = new Content('text/plain', $child->getBody());
                }
            }

            $contents[] = new Content('text/html', $message->getBody());
        }


        return $contents;
    }

    /**
     * Extract the attachments from the message
     * 
     * @param Swift_Mime_SimpleMessage $message
     * 
     * @return array
     */
    protected function getAttachments(Swift_Mime_SimpleMessage $message)
    {
        $attachments = [];

        foreach ($message->getChildren() as $child) {
            if (
                ($child instanceof Swift_Attachment || $child instanceof Swift_Image) &&
                !($child->getBody() instanceof MailParams) &&
                strlen($child->getBody()) <= self::MAXIMUM_FILE_SIZE
            ) {
                $attachment = new Attachment();
                $attachment->setContent(base64_encode($child->getBody()));
                $attachment->setType($child->getContentType());
                $attachment->setFilename($child->getFilename());
                $attachment->setDisposition($child->getDisposition());
                $attachment->setContentId($child->getId());

                $attachments[] = $attachment;
            }
        }

        return $attachments;
    }

    /**
     * Set Request Body Parameters
     *
     * @param Swift_Mime_SimpleMessage $message
     * @param array                    $data
     * 
     * @return array
     * 
     * @throws Exception
     */
    protected function setMailParams(Mail $mail, Swift_Mime_SimpleMessage $message)
    {
        foreach ($message->getChildren() as $child) {
            if (
                $child instanceof Swift_Image &&
                $child->getBody() instanceof MailParams
            ) {
                $childBody = $child->getBody();

                if ($childBody instanceof MailParams) {
                    if (null != $sendAt = $childBody->getSendAt()) {
                        $mail->setSendAt($sendAt);

                        $datetime         = ((new DateTime())->setTimestamp($sendAt));
                        $dateTimeformated = $datetime->format('D, d M Y H:i:s O');
                        $mail->addHeader('Date', $dateTimeformated);
                    }

                    foreach (array_unique($childBody->getCategories()) as $category) {
                        $mail->addCategory($category);
                    }
                }
            }
        }
    }

    /**
     * Get the mail settings object
     * 
     * @return MailSettings
     */
    protected function getMailSettings()
    {
        $settings = new MailSettings();

        // Set the SandBox mode
        $sandboxMode     = new SandBoxMode();
        $isInSandboxMode = (bool) $this->sendgridConfig->get('sandbox_mode', false);
        $sandboxMode->setEnable($isInSandboxMode);

        $settings->setSandboxMode($sandboxMode);

        return $settings;
    }

    /**
     * Set the Recipients to the personalization object
     * 
     * @param Personalization $personalization
     * @param Swift_Mime_SimpleMessage $message
     * 
     * @return void
     */
    protected function setRecipients(Personalization $personalization, Swift_Mime_SimpleMessage $message)
    {
        $isInTestMode = (bool) $this->mailConfig->get('testing.is_enabled', false);
        $recipients   = [];

        if (!$isInTestMode) {
            foreach ($message->getTo() as $email => $name) {
                if (!in_array($email, $recipients)) {
                    $personalization->addTo(new Email($name, $email));
                    ++$this->numberOfRecipients;
                    $recipients[] = $email;
                }
            }

            if ($cc = $message->getCc()) {
                foreach ($cc as $email => $name) {
                    if (!in_array($email, $recipients)) {
                        $personalization->addCc(new Email($name, $email));
                        ++$this->numberOfRecipients;
                        $recipients[] = $email;
                    }
                }
            }

            if ($bcc = $message->getBcc()) {
                foreach ($bcc as $email => $name) {
                    if (!in_array($email, $recipients)) {
                        $personalization->addBcc(new Email($name, $email));
                        ++$this->numberOfRecipients;
                        $recipients[] = $email;
                    }
                }
            }
        } else {
            $testingAddress = $this->mailConfig->get('testing.address');

            $personalization->addTo(new Email('Testing Team', $testingAddress));
            ++$this->numberOfRecipients;
        }
    }

    public function isValidEmail($emailAddress)
    {
        
    }
}
