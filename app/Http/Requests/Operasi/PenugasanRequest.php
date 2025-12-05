<?php

namespace App\Http\Requests\Operasi;

use Illuminate\Validation\Rule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class PenugasanRequest extends FormRequest
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
        $id = $this->route('id'); 

        return [
            // wajib salah satu
            'pengaduan_id' => [
                'nullable',
                'exists:pengaduan,id',
                function ($attr, $value, $fail) {
                    if (!$value && !$this->operasi_id) {
                        $fail("pengaduan_id atau operasi_id wajib diisi salah satu.");
                    }
                }
            ],

            'operasi_id' => ['nullable', 'exists:operasi,id'],

            // ARRAY
            'anggota_id'   => ['required', 'array', 'min:1'],
            'anggota_id.*' => [
                'required',
                'integer',
                'exists:anggota,id',

                // unique per pengaduan
                Rule::unique('penugasan', 'anggota_id')
                    ->where(fn($q) => $q->where('pengaduan_id', $this->pengaduan_id))
                    ->ignore($id),

                // unique per operasi
                Rule::unique('penugasan', 'anggota_id')
                    ->where(fn($q) => $q->where('operasi_id', $this->operasi_id))
                    ->ignore($id),
            ],

            // PERAN
            'peran'   => ['nullable', 'array'],
            'peran.*' => ['nullable', 'string'],
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
