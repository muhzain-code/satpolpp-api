<?php

namespace App\Http\Requests\Operasi;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;

class PenugasanUpdateRequest extends FormRequest
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
        // Ambil ID dari route (pastikan parameternya sesuai, bisa 'id' atau 'penugasan')
        $id = $this->route('id');

        // 1. Definisikan rule dasar
        $rules = [
            'disposisi_id' => [
                'nullable',
                'exists:disposisi,id',
                function ($attr, $value, $fail) {
                    if (!$value && !$this->operasi_id) {
                        $fail("Wajib memilih salah satu: Disposisi atau Operasi.");
                    }
                }
            ],
            'operasi_id' => ['nullable', 'exists:operasi,id'],

            'anggota_id'   => ['required', 'array', 'min:1'],
            'peran'        => ['nullable', 'array'],
            'peran.*'      => ['nullable', 'string'],
        ];

        // 2. Siapkan rule dasar untuk item anggota_id
        $anggotaItemRules = [
            'required',
            'integer',
            'exists:anggota,id'
        ];

        // 3. Terapkan logic CONDITIONAL untuk Unique
        // Hanya tambahkan rule unique disposisi JIKA disposisi_id diisi
        if ($this->disposisi_id) {
            $anggotaItemRules[] = Rule::unique('penugasan', 'anggota_id')
                ->where('disposisi_id', $this->disposisi_id)
                ->ignore($id);
        }

        // Hanya tambahkan rule unique operasi JIKA operasi_id diisi
        if ($this->operasi_id) {
            $anggotaItemRules[] = Rule::unique('penugasan', 'anggota_id')
                ->where('operasi_id', $this->operasi_id)
                ->ignore($id);
        }

        // 4. Masukkan rule yang sudah racik ke dalam array utama
        $rules['anggota_id.*'] = $anggotaItemRules;

        return $rules;
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
