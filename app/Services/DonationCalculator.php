<?php

namespace App\Services;

use App\Models\Campaign;
use App\Models\Category;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/** Shared web/mobile calculator. Money is integer rupiah; no client supplied pricing. */
final class DonationCalculator
{
    public const TYPES = [
        'nominal' => 'Nominal bebas',
        'unit' => 'Berdasarkan jumlah barang',
        'zakat_income' => 'Zakat pendapatan',
        'zakat_mal' => 'Zakat mal (harta simpanan)',
    ];

    public static function config(?Category $category): array
    {
        $type = $category?->donation_type ?: 'nominal';

        return [
            'type' => $type,
            'label' => self::TYPES[$type] ?? self::TYPES['nominal'],
            'unit_label' => $category?->unit_label ?: 'mushaf',
            'unit_price' => (int) ($category?->unit_price ?? 0),
            'zakat_rate' => (string) ($category?->zakat_rate ?? '2.50'),
            'zakat_nisab' => (int) ($category?->zakat_nisab ?? 0),
            'ready' => match ($type) {
                'unit' => ($category?->unit_price ?? 0) > 0,
                'zakat_income', 'zakat_mal' => ($category?->zakat_nisab ?? 0) > 0 && ($category?->zakat_rate ?? 0) > 0,
                default => true,
            },
        ];
    }

    public static function rules(): array
    {
        return [
            'campaign' => ['nullable', 'string', 'exists:campaigns,slug'],
            'amount' => ['nullable', 'integer', 'min:0', 'max:'.config('alkiroma.donation.max_amount')],
            'quantity' => ['nullable', 'integer', 'min:1', 'max:100000'],
            'expected_type' => ['nullable', Rule::in(array_keys(self::TYPES))],
            'payment_mode' => ['nullable', Rule::in(['direct', 'calculate'])],
            'calculation_input' => ['nullable', 'array:period,income,bonus,cash,gold,investments,other,debts,haul'],
            'calculation_input.period' => ['nullable', Rule::in(['monthly', 'annual'])],
            'calculation_input.haul' => ['nullable', 'boolean'],
            ...collect(['income', 'bonus', 'cash', 'gold', 'investments', 'other', 'debts'])
                ->mapWithKeys(fn ($key) => ['calculation_input.'.$key => ['nullable', 'integer', 'min:0', 'max:1000000000000']])->all(),
        ];
    }

    public function quote(?Campaign $campaign, array $data, bool $forPayment = false): array
    {
        Validator::make($data, self::rules())->validate();
        $campaign?->loadMissing('category');
        $config = self::config($campaign?->category);
        $type = $config['type'];
        if (isset($data['expected_type']) && $data['expected_type'] !== $type) {
            $this->fail('Pengaturan program berubah. Buka ulang formulir donasi.');
        }
        $amount = (int) ($data['amount'] ?? 0);
        $snapshot = ['type' => $type, 'label' => $config['label'], 'mode' => 'direct'];
        $eligible = true;
        $message = '';
        $quantity = null;

        if ($type === 'unit') {
            if (! $config['ready']) {
                $this->fail('Harga satuan belum diatur oleh admin.');
            }
            $price = $config['unit_price'];
            $quantity = isset($data['quantity']) ? (int) $data['quantity'] : null;
            if ($quantity === null) {
                if ($amount < $price || $amount % $price !== 0) {
                    $this->fail('Nominal harus kelipatan '.rupiah($price).' per '.$config['unit_label'].'.', 'amount');
                }
                $quantity = intdiv($amount, $price);
            }
            $amount = $price * $quantity;
            if ($quantity > 100000) {
                $this->fail('Maksimal 100.000 satuan per transaksi.', 'quantity');
            }
            $snapshot += ['quantity' => $quantity, 'unit_label' => $config['unit_label'], 'unit_price' => $price];
            $snapshot['mode'] = 'quantity';
            $message = $quantity.' '.$config['unit_label'].' × '.rupiah($price);
        } elseif (in_array($type, ['zakat_income', 'zakat_mal'], true) && ($data['payment_mode'] ?? 'direct') === 'calculate') {
            if (! $config['ready']) {
                $this->fail('Nisab dan tarif zakat belum diatur oleh admin.');
            }
            $input = $data['calculation_input'] ?? [];
            $period = $type === 'zakat_income' ? ($input['period'] ?? 'monthly') : 'annual';
            $nisab = $period === 'monthly' ? intdiv($config['zakat_nisab'] + 11, 12) : $config['zakat_nisab'];
            if ($type === 'zakat_income') {
                if (! isset($input['income'])) {
                    $this->fail('Isi pendapatan untuk periode yang dipilih.', 'calculation_input.income');
                }
                $base = (int) $input['income'] + (int) ($input['bonus'] ?? 0);
                $haul = true;
            } else {
                $base = max(0, array_sum(array_map(fn ($key) => (int) ($input[$key] ?? 0), ['cash', 'gold', 'investments', 'other'])) - (int) ($input['debts'] ?? 0));
                $haul = (bool) ($input['haul'] ?? false);
            }
            $eligible = $base >= $nisab && $haul;
            $basisPoints = (int) round((float) $config['zakat_rate'] * 100);
            $amount = $eligible ? intdiv($base * $basisPoints + 9999, 10000) : 0;
            $snapshot += ['period' => $period, 'rate' => $config['zakat_rate'], 'nisab' => $nisab];
            $snapshot['mode'] = 'calculate';
            $message = ! $haul ? 'Haul satu tahun hijriah belum terpenuhi.' : (! $eligible ? 'Belum mencapai nisab '.rupiah($nisab).'.' : 'Zakat '.$config['zakat_rate'].'% dari harta/penghasilan yang dihitung.');
            // Personal wealth/income inputs are used transiently, never stored on the donation.
        }

        if ($amount > (int) config('alkiroma.donation.max_amount')) {
            $this->fail('Maksimal pembayaran '.rupiah(config('alkiroma.donation.max_amount')).'.', 'amount');
        }
        if ($forPayment) {
            if (! $eligible || $amount < (int) config('alkiroma.donation.min_amount')) {
                $this->fail($message ?: 'Minimal pembayaran '.rupiah(config('alkiroma.donation.min_amount')).'.', 'amount');
            }
            if (isset($data['amount']) && (int) $data['amount'] !== $amount) {
                $this->fail('Nominal tidak sesuai pengaturan terbaru. Muat ulang formulir, lalu hitung kembali sebelum membayar.', 'amount');
            }
        }

        return compact('amount', 'quantity', 'eligible', 'message') + ['calculation' => $snapshot];
    }

    private function fail(string $message, string $field = 'calculation_input'): never
    {
        throw ValidationException::withMessages([$field => $message]);
    }
}
