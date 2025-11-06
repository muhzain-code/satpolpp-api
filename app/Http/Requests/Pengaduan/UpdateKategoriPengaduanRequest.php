<?php

namespace App\Http\Requests\Pengaduan;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class UpdateKategoriPengaduanRequest extends FormRequest
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
        // Ambil ID dari route jika update, null jika create
        $kategoriId = $this->route('id');

        return [
            'nama' => [
                'required',
                'string',
                'max:255',
                Rule::unique('kategori_pengaduan', 'nama')->ignore($kategoriId),
            ],
            'keterangan' => 'nullable|string|max:1000',
        ];
    }

    /**
     * Pesan error custom
     */
    public function messages(): array
    {
        return [
            'nama.required' => 'Nama kategori pengaduan wajib diisi.',
            'nama.string' => 'Nama kategori pengaduan harus berupa teks.',
            'nama.max' => 'Nama kategori pengaduan maksimal 255 karakter.',
            'nama.unique' => 'Nama kategori pengaduan sudah digunakan.',
            'keterangan.string' => 'Keterangan harus berupa teks.',
            'keterangan.max' => 'Keterangan maksimal 1000 karakter.',
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
