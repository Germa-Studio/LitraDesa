<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CategoryUpdateRequest extends FormRequest
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
        $categoryId = $this->route('category');

        return [
            'parent_id' => [
                'nullable',
                'integer',
                'exists:book_categories,id',
                function ($attribute, $value, $fail) use ($categoryId) {
                    // Prevent setting parent to self
                    if ($value == $categoryId) {
                        $fail('Kategori tidak dapat menjadi induk dari dirinya sendiri.');
                    }
                    
                    // Prevent circular references (setting parent to own descendant)
                    if ($value) {
                        $category = \App\Models\BookCategory::find($categoryId);
                        if ($category) {
                            $descendants = $this->getDescendantIds($category);
                            if (in_array($value, $descendants)) {
                                $fail('Kategori tidak dapat menjadi induk dari kategori turunannya.');
                            }
                        }
                    }
                },
            ],
            'name' => [
                'required',
                'string',
                'max:100',
                Rule::unique('book_categories')->where(function ($query) {
                    return $query->where('parent_id', $this->parent_id);
                })->ignore($categoryId),
            ],
            'slug' => [
                'nullable',
                'string',
                'max:100',
                'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/',
                Rule::unique('book_categories')->where(function ($query) {
                    return $query->where('parent_id', $this->parent_id);
                })->ignore($categoryId),
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
     * Get all descendant IDs of a category.
     */
    private function getDescendantIds($category): array
    {
        $ids = [];
        
        foreach ($category->children as $child) {
            $ids[] = $child->id;
            $ids = array_merge($ids, $this->getDescendantIds($child));
        }
        
        return $ids;
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
    }
}
