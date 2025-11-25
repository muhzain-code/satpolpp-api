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
            'no_ktp'            => 'nullable|string|numeric|digits:16',
            'email'             => 'nullable|email|max:100',
            'kontak_pemohon'    => 'required|string|max:50',
            'jenis_informasi'   => 'nullable|string|max:255',
            'informasi_diminta' => 'required|string',
            'alasan_permintaan' => 'required|string',
            'alamat_lengkap'    => 'required|string',
            'cara_memberikan'   => 'required|in:mengambil_langsung,kurir,pos,email,File',
        ];
    }

    public function messages(): array
    {
        return [
            'nama_pemohon.required'      => 'Nama pemohon wajib diisi.',
            'nama_pemohon.max'           => 'Nama pemohon tidak boleh lebih dari 150 karakter.',

            'no_ktp.numeric'             => 'Nomor KTP harus berupa angka.',
            'no_ktp.digits'              => 'Nomor KTP harus berjumlah 16 digit.',

            'email.email'                => 'Format email tidak valid.',
            'email.max'                  => 'Email tidak boleh lebih dari 100 karakter.',

            'kontak_pemohon.required'    => 'Kontak pemohon wajib diisi.',
            'kontak_pemohon.max'         => 'Kontak pemohon tidak boleh lebih dari 50 karakter.',

            'jenis_informasi.max'        => 'Jenis informasi tidak boleh lebih dari 255 karakter.',

            'informasi_diminta.required' => 'Informasi yang diminta wajib diisi.',

            'alasan_permintaan.required' => 'Alasan permintaan wajib diisi.',

            'alamat_lengkap.required'    => 'Alamat lengkap wajib diisi.',

            'cara_memberikan.required'   => 'Cara memberikan informasi wajib dipilih.',
            'cara_memberikan.in'         => 'Pilihan cara memberikan informasi tidak valid (Pilih: mengambil_langsung, kurir, pos, email, atau File).',
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
