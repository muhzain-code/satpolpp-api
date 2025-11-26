<?php

namespace App\Http\Requests\DokumenRegulasi;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class PenandaHalamanRequest extends FormRequest
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
        $isCreate = $this->isMethod('post');
        $ruleType = $isCreate ? 'required' : 'sometimes';

        return [
            'regulasi_id' => [$ruleType, 'integer', 'exists:regulasi,id'],
            'halaman' => [$ruleType, 'integer', 'min:1'],
            'catatan' => ['nullable', 'string', 'max:255'],
        ];
    }
    public function messages(): array
    {
        return [
            'regulasi_id.exists' => 'Regulasi yang dipilih tidak ditemukan.',
            'halaman.required' => 'Halaman wajib diisi.',
            'halaman.min' => 'Halaman minimal bernilai 1.',
            'catatan.max' => 'Catatan maksimal terdiri dari 255 karakter.',
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
