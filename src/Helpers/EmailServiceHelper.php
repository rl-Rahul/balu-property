<?php

namespace App\Helpers;

/**
 * This file is part of the Balu 2.0 package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;


/**
 * Email Services Manager
 *
 * Email Service actions.
 *
 * @package         Balu 2.0
 * @subpackage      App
 * @author          pitsolutions.ch
 */
class EmailServiceHelper
{
    /**
     * @var MailerInterface $mailer
     */
    private MailerInterface $mailer;

    /**
     * Constructor
     *
     * @param MailerInterface $mailer
     */
    public function __construct(MailerInterface $mailer)
    {
        $this->mailer = $mailer;
    }

    /**
     * Email Sending function
     *
     * @param string subject
     * @param string message
     * @param string fromEmail
     * @param string toEmail
     * @return void
     */
    public function sendEmail(string $subject, string $message, string $fromEmail, $toEmail): void
    {
        $email = (new Email())
            ->from($fromEmail)
            ->to($toEmail)
            ->subject($subject)
            ->html($message);

        $this->mailer->send($email);
    }
}