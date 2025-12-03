<?php

namespace App\Http\Requests\Humas;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;

class BeritaRequest extends FormRequest
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
        // Pastikan parameter route sesuai dengan definisi di routes/api.php atau web.php
        // Contoh: Route::put('/berita/{id}', ...) maka gunakan 'id'
        $id = $this->route('id');

        return [
            'judul' => [
                'required',
                'string',
                'max:255',
                Rule::unique('berita', 'judul')->ignore($id),
            ],

            'kategori' => [
                'required',
                'string',
                'max:255'
            ],

            'isi' => ['nullable', 'string'],

            'path_gambar' => [
                'nullable',
                'file',
                'image',
                'mimes:jpg,jpeg,png,webp',
                'max:2048'
            ],

            'tampilkan_publik' => ['required', 'boolean'],

            'published_at' => ['nullable', 'date'],
        ];
    }

    public function messages(): array
    {
        return [
            'judul.required' => 'Judul berita wajib diisi.',
            'judul.unique' => 'Judul berita sudah digunakan, silakan pilih judul lain.',
            'kategori.required' => 'Kategori berita wajib diisi.',
            'path_gambar.image' => 'File harus berupa gambar (jpg, jpeg, png, webp).',
            'path_gambar.max' => 'Ukuran gambar maksimal 2MB.',
            'tampilkan_publik.boolean' => 'Format tampilkan publik harus berupa true atau false.',
            'published_at.date' => 'Format tanggal publish tidak valid.',
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
