<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class VerifyOtpRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['code' => preg_replace('/\D+/', '', (string) $this->code)]);
    }

    public function rules(): array
    {
        return [
            'code' => ['required', 'digits:'.config('alkiroma.otp.length')],
        ];
    }

    public function messages(): array
    {
        return [
            'code.required' => 'Masukkan kode yang kami kirim ke WhatsApp Anda.',
            'code.digits' => 'Kode terdiri dari '.config('alkiroma.otp.length').' angka.',
        ];
    }
}
