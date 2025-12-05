<?php

namespace App\Http\Requests\Penindakan;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class ValidasiKomandanRequest extends FormRequest
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
    public function rules()
    {
        return [
            'status_validasi_komandan'  => 'required|in:disetujui,ditolak,revisi',
            'catatan_validasi_komandan' => 'nullable|string',

            // "butuh_validasi_ppns" wajib diisi (true/false) JIKA status disetujui.
            // Ini saklar penting untuk menentukan alur selanjutnya.
            'butuh_validasi_ppns'       => 'required_if:status_validasi_komandan,disetujui|boolean',
        ];
    }

    public function messages()
    {
        return [
            'butuh_validasi_ppns.required_if' => 'Anda harus menentukan apakah kasus ini perlu diteruskan ke PPNS atau selesai di tempat.',
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
