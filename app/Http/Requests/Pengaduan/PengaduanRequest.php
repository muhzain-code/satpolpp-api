<?php

namespace App\Http\Requests\Pengaduan;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class PengaduanRequest extends FormRequest
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
            'nama_pelapor'     => 'required|string|max:100',
            'kontak_pelapor'   => 'required|string|max:50',
            'kategori_id'      => 'nullable|exists:kategori_pengaduan,id',
            'deskripsi'        => 'required|string',
            'lat'              => 'nullable|numeric|between:-90,90',
            'lng'              => 'nullable|numeric|between:-180,180',
            'alamat'           => 'required|string',

            'lampiran'         => 'nullable|array|max:3',
            'lampiran.*'       => 'file|mimes:jpg,jpeg,png|max:2048',
        ];
    }

    public function messages(): array
    {
        return [
            'nama_pelapor.required' => 'Nama pelapor wajib diisi.',
            'nama_pelapor.string'   => 'Nama pelapor harus berupa teks.',
            'nama_pelapor.max'      => 'Nama pelapor maksimal 100 karakter.',

            'kontak_pelapor.required' => 'Kontak pelapor wajib diisi.',
            'kontak_pelapor.string'   => 'Kontak pelapor harus berupa teks.',
            'kontak_pelapor.max'      => 'Kontak pelapor maksimal 50 karakter.',

            'kategori_id.exists' => 'Kategori pengaduan yang dipilih tidak valid.',

            'deskripsi.required' => 'Deskripsi pengaduan wajib diisi.',
            'deskripsi.string'   => 'Deskripsi harus berupa teks.',

            'lat.numeric'   => 'Latitude harus berupa angka.',
            'lat.between'   => 'Latitude harus berada di antara -90 dan 90.',
            'lng.numeric'   => 'Longitude harus berupa angka.',
            'lng.between'   => 'Longitude harus berada di antara -180 dan 180.',

            'alamat.required' => 'Alamat wajib diisi.',
            'alamat.string'   => 'Alamat harus berupa teks.',

            'lampiran.array'  => 'Lampiran harus berupa array file.',
            'lampiran.max'    => 'Maksimal hanya boleh mengunggah 3 file lampiran.',
            'lampiran.*.file' => 'Setiap lampiran harus berupa file yang valid.',
            'lampiran.*.mimes' => 'Setiap lampiran harus berupa gambar berformat JPG, JPEG, atau PNG.',
            'lampiran.*.max'  => 'Ukuran setiap file lampiran tidak boleh melebihi 2MB.',
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
