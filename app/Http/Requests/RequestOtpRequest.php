<?php

namespace App\Http\Requests;

use App\Rules\IndonesianPhone;
use App\Support\PhoneNumber;
use Illuminate\Foundation\Http\FormRequest;

class RequestOtpRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'phone' => ['required', 'string', 'max:20', new IndonesianPhone],
        ];
    }

    public function messages(): array
    {
        return ['phone.required' => 'Isi nomor WhatsApp yang Anda pakai saat berdonasi.'];
    }

    public function phone(): string
    {
        return PhoneNumber::normalize($this->validated('phone'));
    }
}
