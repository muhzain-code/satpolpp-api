<?php

namespace App\Http\Requests\Operasi;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class StoreOperasiRequest extends FormRequest
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
            'pengaduan_id'  => 'nullable|integer|exists:pengaduan,id',
            'jenis_operasi' => 'required|string|in:rutin,pengaduan',
            'judul'         => 'required|string|max:255',
            'uraian'        => 'nullable|string',
            'mulai'         => 'nullable|date',
            'selesai'       => 'nullable|date|after_or_equal:mulai',

            // ---- Tambahan untuk memilih banyak anggota ----
            'anggota'       => 'nullable|array',            
            'anggota.*'     => 'integer|exists:anggota,id', 

            // Jika Anda ingin peran tiap anggota dimasukkan juga:
            'peran'         => 'nullable|array',
            'peran.*'       => 'nullable|string|max:255',
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
