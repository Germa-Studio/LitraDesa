<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Models\Loan;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateLoanRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $loan = $this->route('loan');
        return $this->user()->can('update', $loan);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'action' => [
                'required',
                'string',
                Rule::in(['return', 'mark_lost', 'extend']),
            ],
            'return_date' => [
                'nullable',
                'required_if:action,return',
                'date',
                'before_or_equal:today',
            ],
            'book_condition_at_return' => [
                'nullable',
                'required_if:action,return',
                'string',
                Rule::in(['excellent', 'good', 'fair', 'poor']),
            ],
            'return_notes' => [
                'nullable',
                'string',
                'max:1000',
            ],
            'fine_paid' => [
                'nullable',
                'boolean',
            ],
            'extend_days' => [
                'nullable',
                'required_if:action,extend',
                'integer',
                'min:1',
                'max:14',
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
            'action.required' => 'Aksi harus dipilih.',
            'action.in' => 'Aksi tidak valid.',
            'return_date.required_if' => 'Tanggal pengembalian harus diisi.',
            'return_date.date' => 'Tanggal pengembalian harus berupa tanggal yang valid.',
            'return_date.before_or_equal' => 'Tanggal pengembalian tidak boleh di masa depan.',
            'book_condition_at_return.required_if' => 'Kondisi buku saat dikembalikan harus diisi.',
            'book_condition_at_return.in' => 'Kondisi buku tidak valid.',
            'return_notes.max' => 'Catatan pengembalian tidak boleh lebih dari 1000 karakter.',
            'extend_days.required_if' => 'Jumlah hari perpanjangan harus diisi.',
            'extend_days.integer' => 'Jumlah hari perpanjangan harus berupa angka.',
            'extend_days.min' => 'Perpanjangan minimal 1 hari.',
            'extend_days.max' => 'Perpanjangan maksimal 14 hari.',
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
            'action' => 'aksi',
            'return_date' => 'tanggal pengembalian',
            'book_condition_at_return' => 'kondisi buku',
            'return_notes' => 'catatan pengembalian',
            'extend_days' => 'jumlah hari perpanjangan',
        ];
    }
}
