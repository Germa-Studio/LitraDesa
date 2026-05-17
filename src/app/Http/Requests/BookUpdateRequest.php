<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class BookUpdateRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->isAdmin();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $bookId = $this->route('book');

        return [
            'title' => ['required', 'string', 'max:255'],
            'author' => ['required', 'string', 'max:255'],
            'isbn' => ['nullable', 'string', 'max:20', Rule::unique('books', 'isbn')->ignore($bookId)],
            'book_category_id' => ['required', 'integer', 'exists:book_categories,id'],
            'description' => ['nullable', 'string'],
            'publisher' => ['nullable', 'string', 'max:255'],
            'publication_year' => ['nullable', 'integer', 'min:1000', 'max:' . (date('Y') + 1)],
            'language' => ['nullable', 'string', 'max:50'],
            'total_copies' => ['required', 'integer', 'min:1'],
            'available_copies' => ['required', 'integer', 'min:0'],
            'location' => ['nullable', 'string', 'max:100'],
            'cover_image' => ['nullable', 'image', 'mimes:jpeg,png,jpg,webp', 'max:2048'],
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
            'title' => 'judul buku',
            'author' => 'penulis',
            'isbn' => 'ISBN',
            'book_category_id' => 'kategori',
            'description' => 'deskripsi',
            'publisher' => 'penerbit',
            'publication_year' => 'tahun terbit',
            'language' => 'bahasa',
            'total_copies' => 'jumlah eksemplar',
            'available_copies' => 'eksemplar tersedia',
            'location' => 'lokasi',
            'cover_image' => 'gambar sampul',
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
            'title.required' => 'Judul buku wajib diisi.',
            'author.required' => 'Nama penulis wajib diisi.',
            'book_category_id.required' => 'Kategori buku wajib dipilih.',
            'book_category_id.exists' => 'Kategori yang dipilih tidak valid.',
            'isbn.unique' => 'ISBN ini sudah terdaftar untuk buku lain.',
            'total_copies.required' => 'Jumlah eksemplar wajib diisi.',
            'total_copies.min' => 'Jumlah eksemplar minimal 1.',
            'available_copies.required' => 'Jumlah eksemplar tersedia wajib diisi.',
            'available_copies.min' => 'Jumlah eksemplar tersedia tidak boleh negatif.',
            'cover_image.image' => 'File harus berupa gambar.',
            'cover_image.max' => 'Ukuran gambar maksimal 2MB.',
        ];
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $totalCopies = $this->input('total_copies');
            $availableCopies = $this->input('available_copies');

            if ($availableCopies > $totalCopies) {
                $validator->errors()->add(
                    'available_copies',
                    'Jumlah eksemplar tersedia tidak boleh lebih dari total eksemplar.'
                );
            }
        });
    }
}
