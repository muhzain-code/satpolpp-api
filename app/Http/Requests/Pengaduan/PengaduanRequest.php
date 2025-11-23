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
            'nama_pelapor'   => 'nullable|string|max:255',
            'kontak_pelapor' => 'nullable|string|max:50',

            'kategori_id'    => 'required|exists:kategori_pengaduan,id',
            'deskripsi'      => 'required|string',

            'lat'            => 'nullable|numeric|between:-90,90',
            'lng'            => 'nullable|numeric|between:-180,180',

            'kecamatan_id'   => 'nullable|exists:kecamatan,id',
            'desa_id'        => 'nullable|exists:desa,id',

            'status'         => 'nullable|in:diterima,diproses,selesai,ditolak',

            'lampiran'       => 'nullable|array|max:3',
            'lampiran.*'     => 'file|mimes:jpg,jpeg,png,pdf|max:2048', 
        ];
    }

    public function messages(): array
    {
        return [
            'nama_pelapor.string'   => 'Nama pelapor harus berupa teks.',
            'nama_pelapor.max'      => 'Nama pelapor maksimal 255 karakter.',

            'kontak_pelapor.string' => 'Kontak pelapor harus berupa teks.',
            'kontak_pelapor.max'    => 'Kontak pelapor maksimal 50 karakter.',

            'kategori_id.required'  => 'Kategori pengaduan wajib dipilih.',
            'kategori_id.exists'    => 'Kategori pengaduan tidak ditemukan.',

            'deskripsi.required'    => 'Deskripsi pengaduan wajib diisi.',
            'deskripsi.string'      => 'Deskripsi harus berupa teks.',

            'lat.numeric'           => 'Latitude harus berupa angka.',
            'lat.between'           => 'Latitude harus valid (-90 s/d 90).',
            'lng.numeric'           => 'Longitude harus berupa angka.',
            'lng.between'           => 'Longitude harus valid (-180 s/d 180).',

            'provinsi_id.exists'    => 'Data Provinsi tidak valid.',
            'kabupaten_id.exists'   => 'Data Kabupaten tidak valid.',
            'kecamatan_id.exists'   => 'Data Kecamatan tidak valid.',
            'desa_id.exists'        => 'Data Desa tidak valid.',

            'status.in'             => 'Status tidak valid (diterima, diproses, selesai, ditolak).',

            'lampiran.array'        => 'Lampiran harus berupa list file.',
            'lampiran.max'          => 'Maksimal upload 3 file lampiran.',
            'lampiran.*.file'       => 'Lampiran harus berupa file.',
            'lampiran.*.mimes'      => 'Format lampiran harus jpg, jpeg, png, atau pdf.',
            'lampiran.*.max'        => 'Ukuran file maksimal 2MB per file.',
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
