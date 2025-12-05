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
        $user = $this->user();
        $isSuperAdmin = $user && $user->hasRole('superadmin');
        $isPost = $this->isMethod('post');

        return [
            'anggota_id' => [
                ($isPost && $isSuperAdmin) ? 'required' : 'nullable',
                'exists:anggota,id'
            ],

            'jenis' => [
                $isPost ? 'required' : 'sometimes',
                'in:aman,insiden'
            ],
            
            'urgent' => [
                $isPost ? 'required' : 'sometimes',
                'boolean'
            ],

            'catatan' => ['nullable', 'string'],
            'lat'     => ['nullable', 'numeric', 'between:-90,90'],
            'lng'     => ['nullable', 'numeric', 'between:-180,180'],

            'kecamatan_id' => ['nullable', 'exists:kecamatan,id'],
            'desa_id'      => ['nullable', 'exists:desa,id'],

            'kategori_pelanggaran_id' => ['nullable', 'exists:kategori_pengaduan,id'],
            'regulasi_indikatif_id'   => ['nullable', 'exists:regulasi,id'],

            'severity' => [
                'nullable',
                'required_if:jenis,insiden',
                'in:rendah,sedang,tinggi'
            ],

            'status_validasi' => ['nullable', 'in:menunggu,disetujui,ditolak'],

            'telah_dieskalasi' => ['nullable', 'boolean'],

            'lampiran'   => ['nullable', 'array'],
            'lampiran.*' => [
                'file',
                'max:10240',
                'mimes:jpg,jpeg,png,mp4'
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'anggota_id.required' => 'Superadmin wajib memilih anggota untuk input laporan.',
            'anggota_id.exists'   => 'Data anggota yang dipilih tidak ditemukan.',

            'jenis.required' => 'Jenis laporan wajib dipilih (Aman/Insiden).',
            'jenis.in'       => 'Jenis laporan hanya boleh Aman atau Insiden.',

            'lat.numeric' => 'Latitude harus berupa angka.',
            'lng.numeric' => 'Longitude harus berupa angka.',
            'lat.between' => 'Latitude tidak valid.',
            'lng.between' => 'Longitude tidak valid.',

            'severity.required_if' => 'Tingkat keparahan (severity) wajib diisi jika jenis laporan adalah Insiden.',
            'severity.in'          => 'Pilihan severity tidak valid.',

            'status_validasi.in' => 'Status validasi harus berupa: menunggu, disetujui, atau ditolak.',

            'lampiran.array'   => 'Format lampiran salah.',
            'lampiran.*.file'  => 'Lampiran harus berupa file.',
            'lampiran.*.mimes' => 'Format file tidak didukung. Gunakan: jpg, png, mp4, mov, pdf, docx.',
            'lampiran.*.max'   => 'Ukuran file lampiran maksimal 10MB per file.',
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
