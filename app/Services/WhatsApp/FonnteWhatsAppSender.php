<?php

namespace App\Services\WhatsApp;

use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * Example real driver (Fonnte). Activate with WHATSAPP_DRIVER=fonnte & WHATSAPP_TOKEN.
 */
class FonnteWhatsAppSender implements WhatsAppSender
{
    public function __construct(private readonly string $token) {}

    public function send(string $phone, string $message): void
    {
        $response = Http::asForm()
            ->withHeaders(['Authorization' => $this->token])
            ->timeout(15)
            ->post('https://api.fonnte.com/send', [
                'target' => $phone,
                'message' => $message,
            ]);

        if ($response->failed()) {
            throw new RuntimeException('Gagal mengirim WhatsApp: '.$response->status());
        }
    }
}
