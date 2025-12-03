<?php

namespace App\Http\Requests\Humas;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;

class AgendaRequest extends FormRequest
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
            'judul' => [
                'required',
                'string',
                'max:255',
            ],

            'deskripsi' => ['nullable', 'string'],

            'lokasi' => ['required', 'string', 'max:255'],

            'tanggal_kegiatan' => ['required', 'date', 'date_format:Y-m-d'],

            'waktu_mulai' => ['required', 'date_format:H:i'],

            'waktu_selesai' => [
                'nullable',
                'date_format:H:i',
                'after:waktu_mulai'
            ],

            'tampilkan_publik' => ['required', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'judul.required' => 'Judul agenda wajib diisi.',
            'judul.unique'   => 'Judul agenda ini sudah digunakan.',
            'judul.string'   => 'Judul harus berupa teks.',
            'judul.max'      => 'Judul maksimal 255 karakter.',

            'deskripsi.string' => 'Deskripsi harus berupa teks.',

            'lokasi.required' => 'Lokasi agenda wajib diisi.',
            'lokasi.max'      => 'Lokasi maksimal 255 karakter.',

            'tanggal_kegiatan.required'    => 'Tanggal kegiatan wajib diisi.',
            'tanggal_kegiatan.date'        => 'Format tanggal tidak valid.',
            'tanggal_kegiatan.date_format' => 'Format tanggal harus YYYY-MM-DD.',

            'waktu_mulai.required'    => 'Jam mulai wajib diisi.',
            'waktu_mulai.date_format' => 'Format jam mulai harus HH:mm (contoh: 08:00).',

            'waktu_selesai.date_format' => 'Format jam selesai harus HH:mm (contoh: 15:00).',
            'waktu_selesai.after'       => 'Jam selesai harus setelah jam mulai.',

            'tampilkan_publik.required' => 'Status publikasi wajib diisi.',
            'tampilkan_publik.boolean'  => 'Format status publikasi harus benar/salah (true/false).',
        ];
    }
    protected function failedValidation(Validator $validator)
    {
        $errors = $validator->errors();

        $response = response()->json([
            'message' => 'Validasi gagal. Mohon periksa kembali input Anda.',
            'errors'  => $errors,
        ], 422);

        throw new HttpResponseException($response);
    }
}
