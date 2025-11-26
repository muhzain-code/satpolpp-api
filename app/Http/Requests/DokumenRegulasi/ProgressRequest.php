<?php

namespace App\Http\Requests\DokumenRegulasi;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class ProgressRequest extends FormRequest
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
            'regulasi_id'  => 'required|integer|exists:regulasi,id',
            'durasi_detik' => 'required|integer|min:0',
        ];
    }

    /**
     * Custom message for validation errors.
     */
    public function messages(): array
    {
        return [
            'regulasi_id.required'  => 'ID Regulasi wajib dikirim.',
            'regulasi_id.exists'    => 'Regulasi tidak ditemukan dalam database.',
            'durasi_detik.required' => 'Durasi baca (detik) wajib dikirim.',
            'durasi_detik.integer'  => 'Durasi baca harus berupa angka.',
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
