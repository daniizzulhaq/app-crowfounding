<?php

namespace App\Exports;

use App\Models\Donation;
use Illuminate\Database\Eloquent\Builder;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class DonationsExport
{
    public function download(Builder $query): BinaryFileResponse
    {
        $rows = (function () use ($query) {
            foreach ($query->clone()->with('campaign:id,title')->reorder('id')->lazyById(500) as $donation) {
                /** @var Donation $donation */
                yield [
                    $donation->order_id,
                    $donation->created_at?->format('Y-m-d H:i'),
                    $donation->paid_at?->format('Y-m-d H:i'),
                    $donation->target_label,
                    $donation->donor_name,
                    $donation->is_anonymous ? 'Ya' : 'Tidak',
                    $donation->phone,
                    $donation->amount,
                    $donation->payment_method?->getLabel(),
                    $donation->status->getLabel(),
                    $donation->prayer,
                ];
            }
        })();

        return XlsxExport::download(
            'donasi-alkiroma-amanah-'.now()->format('Ymd-His').'.xlsx',
            ['Order ID', 'Dibuat', 'Dibayar', 'Campaign', 'Nama', 'Anonim', 'WhatsApp', 'Nominal', 'Metode', 'Status', 'Doa'],
            $rows,
        );
    }
}
