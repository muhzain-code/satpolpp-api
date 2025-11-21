<?php

namespace App\Http\Requests\PPID;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class LacakPPIDRequest extends FormRequest
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
            'nomor_registrasi' => ['required', 'string', 'exists:ppid_permohonan,nomor_registrasi'],
        ];
    }

    public function messages(): array
    {
        return [
            'nomor_registrasi.required' => 'Nomor registrasi harus diisi.',
            'nomor_registrasi.exists' => 'Nomor registrasi tidak ditemukan.',
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
