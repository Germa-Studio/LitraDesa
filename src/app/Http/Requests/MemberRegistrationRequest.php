<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class MemberRegistrationRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Public registration allowed
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'confirmed', Password::defaults()],
            'phone' => ['required', 'string', 'max:20', 'regex:/^(\+62|62|0)[0-9]{9,12}$/'],
            'address' => ['required', 'string', 'max:500'],
            'ktp_number' => ['required', 'string', 'size:16', 'regex:/^[0-9]{16}$/', 'unique:users,ktp_number'],
            'ktp_photo' => ['nullable', 'image', 'mimes:jpeg,jpg,png', 'max:2048'], // 2MB max
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
            'phone.regex' => 'Nomor telepon harus dalam format Indonesia yang valid (contoh: 081234567890).',
            'ktp_number.size' => 'Nomor KTP harus 16 digit.',
            'ktp_number.regex' => 'Nomor KTP hanya boleh berisi angka.',
            'ktp_number.unique' => 'Nomor KTP sudah terdaftar.',
            'ktp_photo.image' => 'File harus berupa gambar.',
            'ktp_photo.mimes' => 'Foto KTP harus berformat JPEG, JPG, atau PNG.',
            'ktp_photo.max' => 'Ukuran foto KTP maksimal 2MB.',
        ];
    }
}

// Made with Bob
