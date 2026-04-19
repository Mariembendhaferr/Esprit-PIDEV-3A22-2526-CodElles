<?php

namespace App\Service;

use Twilio\Rest\Client;

class WhatsAppService
{
    private string $sid;
    private string $token;
    private string $from;

    public function __construct(string $twilioSid, string $twilioToken, string $twilioFrom)
    {
        $this->sid   = $twilioSid;
        $this->token = $twilioToken;
        $this->from  = $twilioFrom;
    }

    public function sendMessage(string $phoneNumber, string $message): bool
    {
        if (empty($this->sid) || empty($this->token) || empty($this->from)) {
            error_log('❌ Twilio credentials missing');
            return false;
        }

        try {
            $clean = preg_replace('/[^0-9]/', '', $phoneNumber);
            if (strlen($clean) === 8) {
                $clean = '216' . $clean;
            }
            $formatted = '+' . $clean;

            error_log("📱 Twilio: Sending to {$formatted} from {$this->from}");

            $client = new Client($this->sid, $this->token);
            $client->messages->create($formatted, [
                'from' => $this->from,
                'body' => $message,
            ]);
            
            error_log("✅ Twilio SMS sent to {$formatted}");
            return true;
            
        } catch (\Twilio\Exceptions\RestException $e) {
            error_log("❌ Twilio API error [{$e->getCode()}]: {$e->getMessage()}");
            return false;
        } catch (\Exception $e) {
            error_log('❌ Twilio error: ' . $e->getMessage());
            return false;
        }
    }

    public function sendResetCode(string $phoneNumber, string $code, string $prenom): bool
    {
        $message = "🔐 DOURA MONDO\n\nBonjour {$prenom},\nVotre code de réinitialisation : {$code}\n\nCe code expire dans 15 minutes.";
        return $this->sendMessage($phoneNumber, $message);
    }
}