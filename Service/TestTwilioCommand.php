<?php

namespace App\Service;

use Twilio\Rest\Client;

class TestTwilioCommand
{
    private $twilio;

    public function __construct()
    {
        // Remplacez ces valeurs par vos identifiants Twilio  
        $sid = 'UScdebd366bb9ff20b8ba9bd25daea5fa9';
        $token = '04dcd37eab39d85f4d234736360170a8';
        $this->twilio = new Client($sid, $token);
    }


    public function sendSms($phoneNumber, $message): bool
    {
        try {
            $this->twilio->messages->create(
                $phoneNumber, // Numéro de téléphone
                [
                    'from' => '+17756557047 ', // Numéro Twilio
                    'body' => $message, // Message SMS
                ]
            );
            return true; // SMS envoyé avec succès
        } catch (\Exception $e) {
            // Logger l'erreur
            return false; // En cas d'erreur
        }
    }
}