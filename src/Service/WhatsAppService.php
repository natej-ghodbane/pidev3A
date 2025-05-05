<?php

    namespace App\Service;

    use Twilio\Rest\Client;
    
    class WhatsAppService
    {
        private Client $twilio;
        private string $from;
    
        public function __construct(string $sid, string $token, string $from)
        {
            $this->twilio = new Client($sid, $token);
            $this->from = $from;
        }
        
    
        public function sendMessage(string $to, string $message): void
{
    try {
        $this->twilio->messages->create(
            $to, // To number must be in the format: 'whatsapp:+21624354335'
            [
                'from' => $this->from, // From number must be your WhatsApp-enabled Twilio number, e.g., 'whatsapp:+14155238886'
                'body' => $message,
            ]
        );
    } catch (\Twilio\Exceptions\RestException $e) {
        // Log the error message for better debugging
        echo "Error: " . $e->getMessage();
    }
}
}