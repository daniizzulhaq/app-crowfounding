<?php

namespace App\Services\Payment;

use App\Models\Donation;

interface PaymentGateway
{
    /**
     * Create a Snap transaction for a pending donation.
     *
     * @throws PaymentGatewayException
     */
    public function createTransaction(Donation $donation): SnapTransaction;

    /**
     * Fetch the transaction status payload straight from the gateway (server-to-server).
     *
     * @return array<string, mixed>
     *
     * @throws PaymentGatewayException
     */
    public function fetchStatus(string $orderId): array;

    /**
     * Verify signature_key = sha512(order_id + status_code + gross_amount + server_key).
     *
     * @param  array<string, mixed>  $payload
     */
    public function hasValidSignature(array $payload): bool;
}
