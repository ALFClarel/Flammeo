<?php

namespace App\Service;

use App\Entity\Order;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;

class OrderConfirmationMailer
{
    public function __construct(
        private readonly MailerInterface $mailer,
        private readonly string $mailerFrom,
    ) {
    }

    public function send(Order $order): void
    {
        $customerEmail = $order->getCustomerEmail();

        if (!$customerEmail) {
            return;
        }

        $email = (new TemplatedEmail())
            ->from(new Address($this->mailerFrom, 'Flammeo'))
            ->to($customerEmail)
            ->subject('Confirmation de ta commande Flammeo')
            ->htmlTemplate('emails/order_confirmation.html.twig')
            ->context([
                'order' => $order,
            ]);

        $this->mailer->send($email);
    }
}
