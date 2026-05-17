<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Models\BookCopy;
use App\Models\Loan;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreLoanRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('create', Loan::class);
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
                'integer',
                'exists:users,id',
                function ($attribute, $value, $fail) {
                    $user = User::find($value);
                    
                    // Check if user is active member
                    if (!$user || $user->role !== 'member' || $user->status !== 'active') {
                        $fail('Member tidak aktif atau tidak valid.');
                        return;
                    }

                    // Check if member has overdue books
                    $hasOverdue = Loan::where('user_id', $value)
                        ->where(function ($query) {
                            $query->where('status', 'overdue')
                                ->orWhere(function ($q) {
                                    $q->where('status', 'active')
                                      ->where('due_date', '<', now()->toDateString());
                                });
                        })
                        ->exists();

                    if ($hasOverdue) {
                        $fail('Member memiliki buku yang terlambat dikembalikan. Harap kembalikan terlebih dahulu.');
                        return;
                    }

                    // Check maximum active loans (3 books)
                    $activeLoans = Loan::where('user_id', $value)
                        ->whereIn('status', ['active', 'overdue'])
                        ->count();

                    if ($activeLoans >= 3) {
                        $fail('Member sudah meminjam maksimal 3 buku. Harap kembalikan buku terlebih dahulu.');
                        return;
                    }
                },
            ],
            'book_copy_id' => [
                'required',
                'integer',
                'exists:book_copies,id',
                function ($attribute, $value, $fail) {
                    $bookCopy = BookCopy::find($value);
                    
                    if (!$bookCopy) {
                        $fail('Salinan buku tidak ditemukan.');
                        return;
                    }

                    // Check if book copy is available
                    if ($bookCopy->status !== 'available') {
                        $fail('Salinan buku tidak tersedia untuk dipinjam.');
                        return;
                    }

                    // Check if member already has active loan for this book
                    if ($this->has('user_id')) {
                        $hasActiveBookLoan = Loan::where('user_id', $this->input('user_id'))
                            ->where('book_id', $bookCopy->book_id)
                            ->whereIn('status', ['active', 'overdue'])
                            ->exists();

                        if ($hasActiveBookLoan) {
                            $fail('Member sudah meminjam buku ini. Harap kembalikan terlebih dahulu.');
                            return;
                        }
                    }
                },
            ],
            'reservation_id' => [
                'nullable',
                'integer',
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
                'string',
                Rule::in(['excellent', 'good', 'fair', 'poor']),
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
            'user_id.required' => 'Member harus dipilih.',
            'user_id.exists' => 'Member tidak ditemukan.',
            'book_copy_id.required' => 'Salinan buku harus dipilih.',
            'book_copy_id.exists' => 'Salinan buku tidak ditemukan.',
            'loan_date.date' => 'Tanggal peminjaman harus berupa tanggal yang valid.',
            'loan_date.before_or_equal' => 'Tanggal peminjaman tidak boleh di masa depan.',
            'due_date.date' => 'Tanggal jatuh tempo harus berupa tanggal yang valid.',
            'due_date.after' => 'Tanggal jatuh tempo harus setelah tanggal peminjaman.',
            'notes.max' => 'Catatan tidak boleh lebih dari 1000 karakter.',
            'book_condition_at_loan.in' => 'Kondisi buku tidak valid.',
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
            'user_id' => 'member',
            'book_copy_id' => 'salinan buku',
            'loan_date' => 'tanggal peminjaman',
            'due_date' => 'tanggal jatuh tempo',
            'notes' => 'catatan',
            'book_condition_at_loan' => 'kondisi buku',
        ];
    }
}
