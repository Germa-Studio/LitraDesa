<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Models\Softbook;
use Illuminate\Foundation\Http\FormRequest;

class UpdateSoftbookRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $softbook = $this->route('softbook');
        return $this->user()->can('update', $softbook);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'pages' => [
                'nullable',
                'integer',
                'min:1',
            ],
            'description' => [
                'nullable',
                'string',
                'max:1000',
            ],
            'download_limit' => [
                'nullable',
                'integer',
                'min:1',
                'max:100',
            ],
            'is_active' => [
                'nullable',
                'boolean',
            ],
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'pages.integer' => 'Jumlah halaman harus berupa angka.',
            'pages.min' => 'Jumlah halaman minimal 1.',
            'description.max' => 'Deskripsi tidak boleh lebih dari 1000 karakter.',
            'download_limit.integer' => 'Batas unduhan harus berupa angka.',
            'download_limit.min' => 'Batas unduhan minimal 1.',
            'download_limit.max' => 'Batas unduhan maksimal 100.',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'pages' => 'jumlah halaman',
            'description' => 'deskripsi',
            'download_limit' => 'batas unduhan',
        ];
    }
}
