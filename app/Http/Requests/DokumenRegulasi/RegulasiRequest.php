<?php

namespace App\Http\Requests\DokumenRegulasi;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class RegulasiRequest extends FormRequest
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
        $rules = [
            'kode' => [
                'required',
                'string',
                'max:80',
                'unique:regulasi,kode,' . $this->route('id'),
            ],
            'judul' => ['required', 'string', 'max:255'],
            'tahun' => ['nullable', 'integer', 'digits:4'],
            'jenis' => ['required', 'in:perda,perkada,buku_saku,sop'],
            'ringkasan' => ['nullable', 'string'],
            'aktif' => ['required', 'boolean'],
        ];

        if ($this->hasFile('path_pdf')) {
            $rules['path_pdf'] = ['file', 'mimes:pdf', 'max:5120'];
        }

        return $rules;
    }

    public function messages(): array
    {
        return [
            'kode.required' => 'Kode regulasi wajib diisi.',
            'kode.unique' => 'Kode regulasi sudah terdaftar, gunakan kode lain.',
            'kode.max' => 'Kode regulasi maksimal 80 karakter.',
            'judul.required' => 'Judul regulasi wajib diisi.',
            'jenis.required' => 'Jenis regulasi wajib dipilih.',
            'jenis.in' => 'Jenis regulasi harus salah satu dari: sop, perda, perkada, buku_saku.',
            'aktif.required' => 'Status aktif harus diisi (true/false).',
            'aktif.boolean' => 'Status aktif harus berupa boolean (true atau false).',
            'path_pdf.mimes' => 'File harus berupa PDF.',
            'path_pdf.max' => 'Ukuran file maksimal 5MB.',
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
