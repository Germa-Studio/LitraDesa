<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Log;

class MemberApprovalRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        Log::info('MemberApprovalRequest::authorize() called', [
            'user_id' => $this->user()?->id,
            'user_role' => $this->user()?->role,
        ]);

        // Authorization is handled by middleware and controller policy
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        Log::info('MemberApprovalRequest::rules() called', [
            'request_data' => $this->all(),
        ]);

        return [
            'status' => ['required', 'in:active,rejected'],
            'rejection_reason' => ['required_if:status,rejected', 'nullable', 'string', 'max:500'],
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
            'status.in' => 'Status harus berupa active atau rejected.',
            'rejection_reason.required_if' => 'Alasan penolakan wajib diisi jika status ditolak.',
        ];
    }

    /**
     * Handle a failed validation attempt.
     */
    protected function failedValidation(\Illuminate\Contracts\Validation\Validator $validator)
    {
        Log::error('MemberApprovalRequest validation failed', [
            'errors' => $validator->errors()->toArray(),
            'request_data' => $this->all(),
        ]);

        parent::failedValidation($validator);
    }
}

// Made with Bob
