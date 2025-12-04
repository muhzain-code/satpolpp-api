<?php

namespace App\Http\Requests\Auth;

use Illuminate\Validation\Rule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class UpdateUserRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $userId = $this->route('id');

        return [
            'name' => ['required', 'string', 'max:255'],

            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($userId),
            ],

            // Password nullable (opsional), hanya divalidasi jika diisi
            'password' => ['nullable', 'string', 'min:6'],

            // Validasi Foreign Key (pastikan data ada di tabel referensi)
            'anggota_id' => ['nullable', 'integer', 'exists:anggota,id'],

            // Validasi Role (pastikan role tersedia di tabel roles)
            // Asumsi menggunakan Spatie Permission, tabelnya 'roles', kolomnya 'name'
            'role' => ['nullable', 'string', 'exists:roles,name'],
        ];
    }

    /**
     * Pesan error kustom (Bahasa Indonesia).
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Nama lengkap wajib diisi.',
            'name.string'   => 'Nama harus berupa teks.',
            'name.max'      => 'Nama tidak boleh lebih dari 255 karakter.',

            'email.required' => 'Alamat email wajib diisi.',
            'email.email'    => 'Format email tidak valid.',
            'email.unique'   => 'Email ini sudah digunakan oleh pengguna lain.',

            'password.min' => 'Password minimal berjumlah 6 karakter.',

            'anggota_id.exists'    => 'Data anggota yang dipilih tidak valid atau tidak ditemukan.',

            'role.exists' => 'Role yang dipilih tidak tersedia di sistem.',
        ];
    }

    protected function failedValidation(Validator $validator)
    {
        $errors = $validator->errors();

        $response = response()->json([
            'message' => 'Validasi gagal. Mohon periksa kembali input Anda.',
            'errors' => $errors,
        ], 422);

        throw new HttpResponseException($response);
    }
}
