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
            'anggota_id'      => 'required|exists:anggota,id',
            'jenis'           => 'required|in:aman,insiden',
            'catatan'         => 'nullable|string',
            'lat'             => 'nullable|numeric',
            'lng'             => 'nullable|numeric',
            'lampiran.*'      => 'nullable|file|mimes:jpg,jpeg,png,mp4,mov|max:10240',
            'status_validasi' => 'nullable|in:menunggu,disetujui,ditolak',
            'divalidasi_oleh' => 'nullable|exists:anggota,id',
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
            'lat.numeric' => 'Latitude harus berupa angka.',
            'lng.numeric' => 'Longitude harus berupa angka.',
            'lampiran.*.file' => 'Lampiran harus berupa file.',
            'lampiran.*.mimes' => 'Lampiran hanya boleh berupa file jpg, jpeg, png, mp4, atau mov.',
            'lampiran.*.max' => 'Ukuran file lampiran tidak boleh lebih dari 10MB.',
            'status_validasi.in' => 'Status validasi hanya boleh menunggu, disetujui, atau ditolak.',
            'divalidasi_oleh.exists' => 'Validator tidak ditemukan di daftar anggota.',
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
