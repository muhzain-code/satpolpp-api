<?php

namespace App\Http\Requests\Humas;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;

class HimbauanRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return false;
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
            'judul' => [
                'required',
                'string',
                'max:255',
                Rule::unique('konten', 'judul')
                    ->where(fn($query) => $query->where('tipe', 'himbauan'))
                    ->ignore($id),
            ],

            'isi' => ['nullable', 'string'],

            'path_gambar' => [
                'nullable',
                'file',
                'image',
                'mimes:jpeg,png,jpg,gif,webp',
                'max:5120',
            ],

            'tampilkan_publik' => ['required', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'judul.required' => 'Judul himbauan wajib diisi.',
            'judul.unique'   => 'Judul himbauan ini sudah digunakan.',
            'path_gambar.image' => 'File harus berupa gambar.',
            'path_gambar.max'   => 'Ukuran gambar maksimal 5 MB.',
            'tampilkan_publik.boolean' => 'Format status publikasi harus benar/salah.',
        ];
    }
}
