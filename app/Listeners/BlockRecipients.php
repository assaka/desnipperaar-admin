<?php

namespace App\Listeners;

use Illuminate\Mail\Events\MessageSending;
use Illuminate\Support\Facades\Log;
use Symfony\Component\Mime\Email;

/**
 * Tijdelijke blokkade: mail naar een adres in desnipperaar.mail_block gaat
 * niet de deur uit. Bedoeld om een klant niets te sturen terwijl we iets
 * testen; haal het adres weer uit de config zodra de test bevestigd is.
 *
 * MessageSending annuleert de verzending als een luisteraar false teruggeeft.
 * Er komt dan geen MessageSent, dus ook geen regel in de ordergeschiedenis,
 * wel een waarschuwing in de log.
 */
class BlockRecipients
{
    public function handle(MessageSending $event): ?bool
    {
        $blocked = array_map('strtolower', (array) config('desnipperaar.mail_block', []));
        if ($blocked === [] || ! $event->message instanceof Email) {
            return null;
        }

        $message = $event->message;
        $recipients = array_merge($message->getTo(), $message->getCc(), $message->getBcc());

        foreach ($recipients as $address) {
            if (in_array(strtolower($address->getAddress()), $blocked, true)) {
                Log::warning('Mail geblokkeerd (desnipperaar.mail_block)', [
                    'to'      => $address->getAddress(),
                    'subject' => $message->getSubject(),
                ]);

                return false;
            }
        }

        return null;
    }
}
