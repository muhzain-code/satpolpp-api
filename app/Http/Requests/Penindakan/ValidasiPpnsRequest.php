<?php

namespace App\Http\Requests\Penindakan;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class ValidasiPpnsRequest extends FormRequest
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
            'status_validasi_ppns' => ['required', 'string', 'in:menunggu,ditolak,disetujui'],
            'catatan_validasi_ppns' => ['nullable', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'status_validasi_ppns.required' => 'Status validasi wajib diisi.',
            'status_validasi_ppns.in' => 'Status validasi hanya boleh berupa disetujui, ditolak dan disetujui.',
            'catatan_validasi_ppns.string' => 'Catatan validasi harus berupa teks.',
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
