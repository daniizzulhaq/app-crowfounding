<?php

namespace App\Http\Requests;

use App\Rules\IndonesianPhone;
use App\Services\DonationCalculator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreDonationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $amount = $this->input('amount');

        $this->merge([
            // Accept 25000, "25000", "25.000" or "Rp25.000" — always integer rupiah, never floats.
            'amount' => is_int($amount) ? $amount : (int) preg_replace('/\D+/', '', (string) $amount),
            'is_anonymous' => $this->boolean('is_anonymous'),
            'donor_name' => is_string($this->donor_name) ? trim(strip_tags($this->donor_name)) : null,
            'prayer' => is_string($this->prayer) ? trim(strip_tags($this->prayer)) : null,
        ]);
    }

    public function rules(): array
    {
        return [
            ...DonationCalculator::rules(),
            'campaign' => ['nullable', 'string', 'exists:campaigns,slug'],
            'amount' => [
                'required', 'integer',
                'min:'.config('alkiroma.donation.min_amount'),
                'max:'.config('alkiroma.donation.max_amount'),
            ],
            'is_anonymous' => ['boolean'],
            'donor_name' => [Rule::requiredIf(! $this->boolean('is_anonymous')), 'nullable', 'string', 'max:60'],
            'phone' => ['required', 'string', 'max:20', new IndonesianPhone],
            'prayer' => ['nullable', 'string', 'max:500'],
            'website' => ['prohibited'], // honeypot
        ];
    }

    public function messages(): array
    {
        return [
            'amount.min' => 'Minimal donasi '.rupiah(config('alkiroma.donation.min_amount')).'.',
            'amount.max' => 'Maksimal donasi '.rupiah(config('alkiroma.donation.max_amount')).'.',
            'amount.required' => 'Pilih atau isi nominal donasi.',
            'donor_name.required' => 'Isi nama, atau aktifkan "Sembunyikan nama".',
            'phone.required' => 'Isi nomor WhatsApp yang aktif.',
            'prayer.max' => 'Doa maksimal 500 karakter.',
            'campaign.exists' => 'Campaign tidak ditemukan.',
        ];
    }

    /**
     * @return array{donor_name: ?string, is_anonymous: bool, phone: string, amount: int, prayer: ?string}
     */
    public function donationData(): array
    {
        return [
            'donor_name' => $this->validated('donor_name'),
            'is_anonymous' => (bool) $this->validated('is_anonymous'),
            'phone' => $this->validated('phone'),
            'amount' => (int) $this->validated('amount'),
            'prayer' => $this->validated('prayer'),
            'quantity' => $this->validated('quantity'),
            'expected_type' => $this->validated('expected_type'),
            'payment_mode' => $this->validated('payment_mode'),
            'calculation_input' => $this->validated('calculation_input'),
        ];
    }
}
