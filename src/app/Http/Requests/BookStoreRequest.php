<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class BookStoreRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->is_admin ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'author' => ['required', 'string', 'max:255'],
            'isbn' => ['nullable', 'string', 'max:20', 'unique:books,isbn'],
            'book_category_id' => ['required', 'integer', 'exists:book_categories,id'],
            'description' => ['nullable', 'string'],
            'publisher' => ['nullable', 'string', 'max:255'],
            'publication_year' => ['nullable', 'integer', 'min:1000', 'max:' . (date('Y') + 1)],
            'language' => ['nullable', 'string', 'max:50'],
            'total_copies' => ['required', 'integer', 'min:1'],
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
            'cover_image.image' => 'File harus berupa gambar.',
            'cover_image.max' => 'Ukuran gambar maksimal 2MB.',
        ];
    }
}
