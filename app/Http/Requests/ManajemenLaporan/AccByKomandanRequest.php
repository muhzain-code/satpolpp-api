<?php

namespace App\Http\Requests\ManajemenLaporan;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class AccByKomandanRequest extends FormRequest
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
            'status_validasi' => 'required|string|in:disetujui,ditolak',
        ];
    }

    public function messages(): array
    {
        return [
            'status_validasi.required' => 'Status validasi wajib diisi',
            'status_validasi.string'   => 'Status validasi harus berupa teks',
            'status_validasi.in'       => 'Status validasi hanya boleh: diterima atau ditolak',
        ];
    }

    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(
            response()->json([
                'message' => 'Validasi gagal',
                'errors' => $validator->errors(),
            ], 422)
        );
    }
}
