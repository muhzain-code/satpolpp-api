<?php

namespace App\Http\Requests\Humas;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class KontenRequest extends FormRequest
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
        $slug = $this->route('slug');

        return [
            'tipe' => 'required|in:berita,agenda,himbauan',

            'judul' => [
                'required',
                'string',
                'max:255',
                'unique:konten,judul,' . $slug . ',slug',
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
        ];
    }

    public function messages(): array
    {
        return [
            'judul.unique' => 'Judul sudah digunakan.',
            'path_gambar.image' => 'File harus berupa gambar.',
            'tampilkan_publik.boolean' => 'Format tampilkan_publik harus true atau false.',
            'published_at.date' => 'Format tanggal tidak valid.',
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
