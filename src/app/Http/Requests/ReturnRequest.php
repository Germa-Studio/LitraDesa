<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Models\Loan;
use Illuminate\Foundation\Http\FormRequest;

class ReturnRequest extends FormRequest
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
            'loan_id' => [
                'required',
                'exists:loans,id',
                function ($attribute, $value, $fail) {
                    $loan = Loan::find($value);
                    
                    if (!$loan) {
                        $fail('Peminjaman tidak ditemukan.');
                        return;
                    }
                    
                    if ($loan->status === 'returned') {
                        $fail('Buku sudah dikembalikan sebelumnya.');
                        return;
                    }
                    
                    if ($loan->status === 'lost') {
                        $fail('Buku sudah ditandai sebagai hilang.');
                        return;
                    }
                },
            ],
            'return_date' => [
                'nullable',
                'date',
                'before_or_equal:today',
                function ($attribute, $value, $fail) {
                    if ($value && $this->loan_id) {
                        $loan = Loan::find($this->loan_id);
                        if ($loan && $value < $loan->loan_date) {
                            $fail('Tanggal pengembalian tidak boleh sebelum tanggal peminjaman.');
                        }
                    }
                },
            ],
            'book_condition_at_return' => [
                'required',
                'in:excellent,good,fair,poor',
            ],
            'return_notes' => [
                'nullable',
                'string',
                'max:1000',
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
            'loan_id' => 'peminjaman',
            'return_date' => 'tanggal pengembalian',
            'book_condition_at_return' => 'kondisi buku',
            'return_notes' => 'catatan pengembalian',
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
            'loan_id.required' => 'Peminjaman wajib dipilih.',
            'loan_id.exists' => 'Peminjaman tidak ditemukan.',
            'return_date.before_or_equal' => 'Tanggal pengembalian tidak boleh di masa depan.',
            'book_condition_at_return.required' => 'Kondisi buku wajib diisi.',
            'book_condition_at_return.in' => 'Kondisi buku tidak valid.',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Set default return date to today if not provided
        if (!$this->has('return_date')) {
            $this->merge(['return_date' => now()->toDateString()]);
        }
    }
}
