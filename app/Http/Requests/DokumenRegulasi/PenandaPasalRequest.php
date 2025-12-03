<?php

namespace App\Http\Requests\DokumenRegulasi;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class PenandaPasalRequest extends FormRequest
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
        // Cek apakah ini request POST (Create)
        $isCreate = $this->isMethod('post');

        // Jika Create, field wajib (required). Jika Update, field opsional (sometimes)
        $ruleType = $isCreate ? 'required' : 'sometimes';

        return [
            'regulasi_id' => [$ruleType, 'integer', 'exists:regulasi,id'],
            'halaman'     => [$ruleType, 'integer', 'min:1'],

            // Validasi Array 'data'
            'data'        => [$ruleType, 'text'],

            // // Item di dalam array 'data' hanya divalidasi jika array 'data' dikirim
            // 'data.x'      => ['required_with:data', 'numeric'],
            // 'data.y'      => ['required_with:data', 'numeric'],
            // 'data.w'      => ['nullable', 'numeric'],
            // 'data.h'      => ['nullable', 'numeric'],
            // 'data.color'  => ['nullable', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'regulasi_id.exists'   => 'Regulasi yang dipilih tidak ditemukan.',
            'regulasi_id.required' => 'ID Regulasi wajib diisi.',
            'halaman.required'     => 'Nomor halaman wajib diisi.',

            'data.required'        => 'Koordinat highlight (data) wajib dikirim.',
            'data.array'           => 'Format data highlight harus berupa object/array JSON.',

            'data.x.required_with' => 'Koordinat X wajib ada jika data dikirim.',
            'data.y.required_with' => 'Koordinat Y wajib ada jika data dikirim.',
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
