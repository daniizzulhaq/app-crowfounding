<?php

namespace App\Http\Requests;

use App\Models\Campaign;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CampaignIndexRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Invalid filters are silently dropped rather than erroring on a public page.
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'q' => is_string($this->q) ? mb_substr(trim($this->q), 0, 100) : null,
            'kategori' => is_string($this->kategori) ? $this->kategori : null,
            'urut' => array_key_exists((string) $this->urut, Campaign::SORTS) ? $this->urut : 'terbaru',
        ]);
    }

    public function rules(): array
    {
        return [
            'q' => ['nullable', 'string', 'max:100'],
            'kategori' => ['nullable', 'string', 'max:100'],
            'urut' => ['required', Rule::in(array_keys(Campaign::SORTS))],
        ];
    }
}
