<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Models\Book;
use Illuminate\Foundation\Http\FormRequest;

class ReservationRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Members can create their own reservations
        return $this->user()?->isMember() ?? false;
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
                'exists:books,id',
                function ($attribute, $value, $fail) {
                    $book = Book::find($value);
                    $user = $this->user();
                    
                    if (!$book) {
                        $fail('Buku tidak ditemukan.');
                        return;
                    }
                    
                    // Check if user already has active reservation for this book
                    if ($book->hasActiveReservationFor($user->id)) {
                        $fail('Anda sudah memiliki reservasi aktif untuk buku ini.');
                        return;
                    }
                    
                    // Check if user already has active loan for this book
                    if ($book->hasActiveLoanFor($user->id)) {
                        $fail('Anda sedang meminjam buku ini.');
                        return;
                    }
                    
                    // Check if user can borrow more books
                    if (!$user->canBorrowMoreBooks()) {
                        $fail('Anda sudah mencapai batas maksimal peminjaman (3 buku).');
                        return;
                    }
                    
                    // Check if user has overdue loans
                    if ($user->hasOverdueLoans()) {
                        $fail('Anda memiliki peminjaman yang terlambat. Harap kembalikan terlebih dahulu.');
                        return;
                    }
                    
                    // Check if book can be reserved (not exceeding waitlist limit)
                    if (!$book->canBeReserved()) {
                        $fail('Buku ini tidak dapat direservasi saat ini. Daftar tunggu penuh.');
                        return;
                    }
                },
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
            'book_id' => 'buku',
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
            'book_id.required' => 'Buku wajib dipilih.',
            'book_id.exists' => 'Buku tidak ditemukan.',
        ];
    }
}
