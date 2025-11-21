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
            'nama_pemohon' => 'required|string|max:150',
            'kontak_pemohon' => 'required|string|max:50',
            'informasi_diminta' => 'required|string|max:2000',
            'alasan_permintaan' => 'required|string|max:2000',
        ];
    }

    public function messages(): array
    {
        return [
            'nama_pemohon.required' => 'Nama pemohon wajib diisi.',
            'kontak_pemohon.required' => 'Kontak pemohon wajib diisi.',
            'informasi_diminta.required' => 'Informasi yang diminta wajib diisi.',
            'alasan_permintaan.required' => 'Alasan permintaan wajib diisi.',
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
