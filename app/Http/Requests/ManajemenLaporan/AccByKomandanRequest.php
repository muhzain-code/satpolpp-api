<?php

namespace App\Http\Requests\ManajemenLaporan;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;

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
            'status_validasi' => [
                'required',
                'string',
                Rule::in(['disetujui', 'ditolak']),
            ],

            'catatan_validasi' => [
                'nullable',
                'string',
                'max:500',
                // Pastikan wajib jika ditolak
                Rule::requiredIf($this->input('status_validasi') === 'ditolak'),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'status_validasi.required' => 'Status validasi wajib diisi.',
            'status_validasi.in'       => 'Status validasi hanya boleh "disetujui" atau "ditolak".',

            'catatan_validasi.required_if' => 'Catatan atau alasan wajib diisi jika status validasi adalah "ditolak".',
            'catatan_validasi.string'      => 'Catatan validasi harus berupa teks.',
            'catatan_validasi.max'         => 'Catatan validasi tidak boleh lebih dari :max karakter.',
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
