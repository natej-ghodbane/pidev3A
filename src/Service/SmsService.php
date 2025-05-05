<?php

namespace App\Service;

use Symfony\Component\Notifier\Message\SmsMessage;
use Symfony\Component\Notifier\TexterInterface;

class SmsService
{
    private $texter;

    public function __construct(TexterInterface $texter)
    {
        $this->texter = $texter;
    }

    public function sendSms(string $phoneNumber, string $message): void
    {
        try {
            // Format international du numéro de téléphone
            if (!str_starts_with($phoneNumber, '+')) {
                $phoneNumber = '+' . $phoneNumber;
            }

            $sms = new SmsMessage(
                $phoneNumber,
                $message
            );

            $this->texter->send($sms);
        } catch (\Exception $e) {
            throw new \Exception('Error sending SMS: ' . $e->getMessage());
        }
    }
} 