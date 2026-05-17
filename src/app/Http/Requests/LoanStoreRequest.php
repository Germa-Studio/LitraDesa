<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Models\Book;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;

class LoanStoreRequest extends FormRequest
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
            'user_id' => [
                'required',
                'exists:users,id',
                function ($attribute, $value, $fail) {
                    $user = User::find($value);
                    
                    if (!$user) {
                        $fail('Anggota tidak ditemukan.');
                        return;
                    }
                    
                    if (!$user->isMember()) {
                        $fail('Hanya anggota yang dapat meminjam buku.');
                        return;
                    }
                    
                    if (!$user->isActive()) {
                        $fail('Anggota tidak aktif. Status: ' . $user->status);
                        return;
                    }
                    
                    if (!$user->canBorrowMoreBooks()) {
                        $fail('Anggota sudah mencapai batas maksimal peminjaman (3 buku).');
                        return;
                    }
                    
                    if ($user->hasOverdueLoans()) {
                        $fail('Anggota memiliki peminjaman yang terlambat. Harap kembalikan terlebih dahulu.');
                        return;
                    }
                },
            ],
            'book_id' => [
                'required',
                'exists:books,id',
                function ($attribute, $value, $fail) {
                    $book = Book::find($value);
                    
                    if (!$book) {
                        $fail('Buku tidak ditemukan.');
                        return;
                    }
                    
                    if (!$book->is_available || $book->available_copies <= 0) {
                        $fail('Buku tidak tersedia untuk dipinjam.');
                        return;
                    }
                    
                    // Check if user already has active loan for this book
                    if ($this->user_id && $book->hasActiveLoanFor($this->user_id)) {
                        $fail('Anggota sudah meminjam buku ini.');
                        return;
                    }
                },
            ],
            'book_copy_id' => [
                'nullable',
                'exists:book_copies,id',
                function ($attribute, $value, $fail) {
                    if ($value) {
                        $bookCopy = \App\Models\BookCopy::find($value);
                        
                        if ($bookCopy && $bookCopy->book_id != $this->book_id) {
                            $fail('Salinan buku tidak sesuai dengan buku yang dipilih.');
                            return;
                        }
                        
                        if ($bookCopy && $bookCopy->status !== 'available') {
                            $fail('Salinan buku tidak tersedia.');
                            return;
                        }
                    }
                },
            ],
            'reservation_id' => [
                'nullable',
                'exists:reservations,id',
            ],
            'loan_date' => [
                'nullable',
                'date',
                'before_or_equal:today',
            ],
            'due_date' => [
                'nullable',
                'date',
                'after:loan_date',
            ],
            'notes' => [
                'nullable',
                'string',
                'max:1000',
            ],
            'book_condition_at_loan' => [
                'nullable',
                'in:excellent,good,fair,poor',
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
            'user_id' => 'anggota',
            'book_id' => 'buku',
            'book_copy_id' => 'salinan buku',
            'reservation_id' => 'reservasi',
            'loan_date' => 'tanggal pinjam',
            'due_date' => 'tanggal jatuh tempo',
            'notes' => 'catatan',
            'book_condition_at_loan' => 'kondisi buku',
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
            'user_id.required' => 'Anggota wajib dipilih.',
            'user_id.exists' => 'Anggota tidak ditemukan.',
            'book_id.required' => 'Buku wajib dipilih.',
            'book_id.exists' => 'Buku tidak ditemukan.',
            'loan_date.before_or_equal' => 'Tanggal pinjam tidak boleh di masa depan.',
            'due_date.after' => 'Tanggal jatuh tempo harus setelah tanggal pinjam.',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Set default loan date to today if not provided
        if (!$this->has('loan_date')) {
            $this->merge(['loan_date' => now()->toDateString()]);
        }

        // Set default due date to 14 days from loan date if not provided
        if (!$this->has('due_date') && $this->has('loan_date')) {
            $this->merge([
                'due_date' => now()->parse($this->loan_date)->addDays(14)->toDateString(),
            ]);
        }

        // Set default book condition
        if (!$this->has('book_condition_at_loan')) {
            $this->merge(['book_condition_at_loan' => 'good']);
        }
    }
}
