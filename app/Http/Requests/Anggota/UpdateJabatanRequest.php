<?php

namespace App\Http\Requests\Anggota;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;

class UpdateJabatanRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $jabatanId = $this->route('id'); // Ambil id dari route untuk ignore unique

        return [
            'nama' => [
                'required',
                'string',
                'max:255',
                Rule::unique('jabatan', 'nama')->ignore($jabatanId),
            ],
            'keterangan' => 'nullable|string|max:500',
        ];
    }

    public function messages(): array
    {
        return [
            'nama.required' => 'Nama jabatan wajib diisi.',
            'nama.string' => 'Nama jabatan harus berupa teks.',
            'nama.max' => 'Nama jabatan maksimal 255 karakter.',
            'nama.unique' => 'Nama jabatan sudah digunakan.',
            'keterangan.string' => 'Keterangan harus berupa teks.',
            'keterangan.max' => 'Keterangan maksimal 500 karakter.',
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
