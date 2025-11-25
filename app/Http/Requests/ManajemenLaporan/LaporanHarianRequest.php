<?php

namespace App\Http\Requests\ManajemenLaporan;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class LaporanHarianRequest extends FormRequest
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
            'anggota_id' => ['required', 'exists:anggota,id'],

            'jenis' => ['required', 'in:aman,insiden'],
            'catatan' => ['nullable', 'string'],

            'kecamatan_id' => ['nullable', 'exists:kecamatan,id'],
            'desa_id' => ['nullable', 'exists:desa,id'],

            'lokasi' => ['nullable', 'string'],

            'lat' => ['nullable', 'numeric'],
            'lng' => ['nullable', 'numeric'],

            'kategori_pelanggaran_id' => ['nullable', 'exists:kategori_pengaduan,id'],
            'regulasi_indikatif_id'   => ['nullable', 'exists:regulasi,id'],

            'severity' => ['required', 'in:rendah,sedang,tinggi'],

            'status_validasi' => ['required', 'in:menunggu,disetujui,ditolak'],
            'divalidasi_oleh' => ['nullable', 'exists:users,id'],

            'telah_dieskalasi' => ['nullable', 'boolean'],

            // Lampiran kini masuk ke tabel terpisah → tetap validasi array
            'lampiran.*' => ['nullable', 'file', 'max:10240', 'mimes:jpg,jpeg,png,mp4'],
        ];
    }

    public function messages(): array
    {
        return [
            'anggota_id.required' => 'ID anggota wajib diisi.',
            'anggota_id.exists' => 'ID anggota tidak ditemukan.',

            'jenis.required' => 'Jenis laporan wajib diisi.',
            'jenis.in' => 'Jenis laporan hanya boleh bernilai aman atau insiden.',

            'catatan.string' => 'Catatan harus berupa teks.',

            'kecamatan_id.exists' => 'Kecamatan tidak ditemukan.',
            'desa_id.exists' => 'Desa tidak ditemukan.',

            'lokasi.string' => 'Lokasi harus berupa teks.',

            'lat.numeric' => 'Latitude harus berupa angka.',
            'lng.numeric' => 'Longitude harus berupa angka.',

            'kategori_pelanggaran_id.exists' => 'Kategori pelanggaran tidak ditemukan.',
            'regulasi_indikatif_id.exists' => 'Regulasi indikatif tidak ditemukan.',

            'severity.in' => 'Severity hanya boleh rendah, sedang, atau tinggi.',

            'status_validasi.in' => 'Status validasi hanya boleh menunggu, disetujui, atau ditolak.',
            'divalidasi_oleh.exists' => 'Validator tidak ditemukan di daftar pengguna.',

            'telah_dieskalasi.boolean' => 'Field telah_dieskalasi harus berupa true atau false.',

            'lampiran.*.file' => 'Lampiran harus berupa file.',
            'lampiran.*.mimes' => 'Lampiran hanya boleh berupa: jpg, jpeg, png, mp4, mov, pdf, doc, docx.',
            'lampiran.*.max' => 'Ukuran file lampiran tidak boleh melebihi 10MB.',
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
