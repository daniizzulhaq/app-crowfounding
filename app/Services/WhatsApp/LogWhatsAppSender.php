<?php

namespace App\Services\WhatsApp;

use App\Support\PhoneNumber;
use Illuminate\Support\Facades\Log;

class LogWhatsAppSender implements WhatsAppSender
{
    public function send(string $phone, string $message): void
    {
        Log::info('[WhatsApp:log] Pesan ke '.PhoneNumber::mask($phone), ['message' => $message]);
    }
}
