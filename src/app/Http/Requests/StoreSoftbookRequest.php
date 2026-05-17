<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Models\Softbook;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreSoftbookRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('create', Softbook::class);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'book_id' => [
                'required',
                'integer',
                'exists:books,id',
            ],
            'file' => [
                'required',
                'file',
                'mimes:pdf,epub',
                'max:51200', // 50MB in kilobytes
            ],
            'format' => [
                'required',
                'string',
                Rule::in(['pdf', 'epub']),
            ],
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
            'is_encrypted' => [
                'nullable',
                'boolean',
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
            'book_id.required' => 'Buku harus dipilih.',
            'book_id.exists' => 'Buku tidak ditemukan.',
            'file.required' => 'File softbook harus diunggah.',
            'file.file' => 'File yang diunggah tidak valid.',
            'file.mimes' => 'File harus berformat PDF atau EPUB.',
            'file.max' => 'Ukuran file maksimal 50MB.',
            'format.required' => 'Format file harus dipilih.',
            'format.in' => 'Format file tidak valid. Hanya PDF dan EPUB yang didukung.',
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
            'book_id' => 'buku',
            'file' => 'file',
            'format' => 'format',
            'pages' => 'jumlah halaman',
            'description' => 'deskripsi',
            'download_limit' => 'batas unduhan',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Auto-detect format from file extension if not provided
        if ($this->hasFile('file') && !$this->has('format')) {
            $extension = $this->file('file')->getClientOriginalExtension();
            $this->merge([
                'format' => strtolower($extension),
            ]);
        }
    }
}
