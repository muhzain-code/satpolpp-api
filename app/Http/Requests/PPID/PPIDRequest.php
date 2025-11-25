<?php

namespace App\Http\Requests\PPID;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class PPIDRequest extends FormRequest
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
        return [
            'nama_pemohon'      => 'required|string|max:150',
            'no_ktp' => 'nullable|string|numeric|digits:16',
            'email'             => 'nullable|email|max:100',
            'kontak_pemohon'    => 'required|string|max:50',
            'informasi_diminta' => 'required|string',
            'alasan_permintaan' => 'required|string',
            'kecamatan_id'      => 'nullable|integer|exists:kecamatan,id',
            'desa_id'           => 'nullable|integer|exists:desa,id',
        ];
    }

    public function messages(): array
    {
        return [
            'nama_pemohon.required'      => 'Nama pemohon wajib diisi.',
            'nama_pemohon.max'           => 'Nama pemohon tidak boleh lebih dari 150 karakter.',

            'no_ktp.max'                 => 'Nomor KTP tidak boleh lebih dari 16 karakter.',

            'email.email'                => 'Format email tidak valid.',
            'email.max'                  => 'Email tidak boleh lebih dari 100 karakter.',

            'kontak_pemohon.required'    => 'Kontak pemohon wajib diisi.',
            'kontak_pemohon.max'         => 'Kontak pemohon tidak boleh lebih dari 50 karakter.',

            'informasi_diminta.required' => 'Informasi yang diminta wajib diisi.',

            'alasan_permintaan.required' => 'Alasan permintaan wajib diisi.',

            'kecamatan_id.exists'        => 'Data kecamatan yang dipilih tidak valid.',
            'kecamatan_id.integer'       => 'ID Kecamatan harus berupa angka.',

            'desa_id.exists'             => 'Data desa yang dipilih tidak valid.',
            'desa_id.integer'            => 'ID Desa harus berupa angka.',
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
