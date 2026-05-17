<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CategoryStoreRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->is_admin ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'parent_id' => [
                'nullable',
                'integer',
                'exists:book_categories,id',
            ],
            'name' => [
                'required',
                'string',
                'max:100',
                Rule::unique('book_categories')->where(function ($query) {
                    return $query->where('parent_id', $this->parent_id);
                }),
            ],
            'slug' => [
                'nullable',
                'string',
                'max:100',
                'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/',
                Rule::unique('book_categories')->where(function ($query) {
                    return $query->where('parent_id', $this->parent_id);
                }),
            ],
            'description' => [
                'nullable',
                'string',
                'max:1000',
            ],
            'is_active' => [
                'boolean',
            ],
            'sort_order' => [
                'integer',
                'min:0',
            ],
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
            'parent_id' => 'kategori induk',
            'name' => 'nama kategori',
            'slug' => 'slug',
            'description' => 'deskripsi',
            'is_active' => 'status aktif',
            'sort_order' => 'urutan',
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
            'name.required' => 'Nama kategori wajib diisi.',
            'name.unique' => 'Nama kategori sudah digunakan pada kategori induk yang sama.',
            'slug.unique' => 'Slug sudah digunakan pada kategori induk yang sama.',
            'slug.regex' => 'Slug hanya boleh berisi huruf kecil, angka, dan tanda hubung.',
            'parent_id.exists' => 'Kategori induk tidak ditemukan.',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Convert empty parent_id to null
        if ($this->parent_id === '' || $this->parent_id === '0') {
            $this->merge(['parent_id' => null]);
        }

        // Set default values
        if (!$this->has('is_active')) {
            $this->merge(['is_active' => true]);
        }

        if (!$this->has('sort_order')) {
            $this->merge(['sort_order' => 0]);
        }
    }
}
