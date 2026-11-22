<?php

namespace App\Services\WhatsApp;

/**
 * Driver contract for sending WhatsApp messages.
 * Initial implementation logs messages; add FonnteSender / WablasSender later.
 */
interface WhatsAppSender
{
    public function send(string $phone, string $message): void;
}
