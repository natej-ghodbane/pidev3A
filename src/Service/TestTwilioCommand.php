<?php

namespace App\Service;

use Twilio\Rest\Client;
use Psr\Log\LoggerInterface;
use Twilio\Exceptions\TwilioException;

class TestTwilioCommand
{
    private $accountSid;
    private $authToken;
    private $fromNumber;
    private $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->accountSid = 'AC7938f8f81e9aca898ed5cfc6f08c5cd1';
        $this->authToken = '04dcd37eab39d85f4d234736360170a8';
        $this->fromNumber = '+17756557047';
        $this->logger = $logger;
    }

    public function sendSms(string $to, string $message): bool
    {
        try {
            // Vérifier les paramètres requis
            if (empty($this->accountSid) || empty($this->authToken) || empty($this->fromNumber)) {
                throw new \Exception('Les paramètres Twilio ne sont pas correctement configurés.');
            }

            // Formater le numéro de téléphone
            if (!str_starts_with($to, '+')) {
                $to = '+' . $to;
            }

            $this->logger->info('Tentative d\'envoi de SMS à ' . $to, [
                'account_sid' => $this->accountSid,
                'from_number' => $this->fromNumber,
                'to_number' => $to
            ]);

            // Initialiser le client Twilio
            $client = new Client($this->accountSid, $this->authToken);

            // Envoyer le message
            $result = $client->messages->create(
                $to,
                [
                    'from' => $this->fromNumber,
                    'body' => $message
                ]
            );

            $this->logger->info('SMS envoyé avec succès', [
                'message_sid' => $result->sid,
                'status' => $result->status,
                'to' => $to
            ]);

            return true;

        } catch (TwilioException $e) {
            $this->logger->error('Erreur Twilio lors de l\'envoi du SMS', [
                'error' => $e->getMessage(),
                'code' => $e->getCode(),
                'to' => $to,
                'account_sid' => $this->accountSid,
                'from_number' => $this->fromNumber
            ]);
            throw new \Exception('Erreur Twilio: ' . $e->getMessage());

        } catch (\Exception $e) {
            $this->logger->error('Erreur générale lors de l\'envoi du SMS', [
                'error' => $e->getMessage(),
                'to' => $to,
                'account_sid' => $this->accountSid,
                'from_number' => $this->fromNumber
            ]);
            throw new \Exception('Erreur lors de l\'envoi du SMS: ' . $e->getMessage());
        }
    }
} 