<?php

namespace App\Http\Requests\Penindakan;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class PenindakanRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            // Salah satu wajib diisi (operasi / pengaduan / laporan_harian)
            'operasi_id'         => ['nullable', 'integer', 'exists:operasi,id'],
            'pengaduan_id'       => ['nullable', 'integer', 'exists:pengaduan,id'],
            'laporan_harian_id'  => ['nullable', 'integer', 'exists:laporan_harian,id'],

            // Jenis penindakan
            'jenis_penindakan'   => ['required', 'in:teguran,pembinaan,penyitaan,proses_hukum'],

            // Lokasi
            'kecamatan_id'       => ['nullable', 'integer', 'exists:kecamatan,id'],
            'desa_id'            => ['nullable', 'integer', 'exists:desa,id'],
            'lokasi'             => ['nullable', 'string'],
            'lat'                => ['nullable', 'numeric'],
            'lng'                => ['nullable', 'numeric'],

            'uraian'             => ['nullable', 'string'],

            // PPNS (aktif hanya saat jenis_penindakan = proses_hukum)
            'butuh_validasi_ppns'   => ['integer', 'in:0,1'],
            'status_validasi_ppns'  => ['nullable', 'in:menunggu,ditolak,revisi,disetujui'],
            'catatan_validasi_ppns' => ['nullable', 'string'],
            'ppns_validator_id'     => ['nullable', 'integer', 'exists:users,id'],

            // REGULASI — jika diperlukan
            'regulasi'                   => ['nullable', 'array'],
            'regulasi.*.regulasi_id'     => ['required_with:regulasi', 'integer', 'exists:regulasi,id'],
            'regulasi.*.pasal_dilanggar' => ['nullable'],

            // LAMPIRAN
            'lampiran'               => ['nullable', 'array'],
            'lampiran.*.path_file'   => ['required', 'string', 'max:1000'],
            'lampiran.*.nama_file'   => ['nullable', 'string', 'max:255'],
            'lampiran.*.jenis'       => ['nullable', 'in:foto,video,dokumen'],
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $filled = array_filter([
                $this->input('operasi_id'),
                $this->input('pengaduan_id'),
                $this->input('laporan_harian_id'),
            ]);

            if (count($filled) === 0) {
                $validator->errors()->add(
                    'sumber_kegiatan',
                    'Salah satu dari operasi, pengaduan, atau laporan harian wajib diisi.'
                );
            }

            if (count($filled) > 1) {
                $validator->errors()->add(
                    'sumber_kegiatan',
                    'Hanya satu dari operasi, pengaduan, atau laporan harian yang boleh diisi.'
                );
            }
        });
    }

    public function messages(): array
    {
        return [
            'operasi_id.exists' => 'Operasi tidak ditemukan.',
            'pengaduan_id.exists' => 'Pengaduan tidak ditemukan.',
            'laporan_harian_id.exists' => 'Laporan harian tidak ditemukan.',

            'jenis_penindakan.in' => 'Jenis penindakan tidak valid.',

            'kecamatan_id.exists' => 'Kecamatan tidak ditemukan.',
            'desa_id.exists' => 'Desa tidak ditemukan.',

            'status_validasi_ppns.in' => 'Status validasi PPNS tidak valid.',
            'ppns_validator_id.exists' => 'PPNS validator tidak ditemukan.',

            'regulasi.array' => 'Format data regulasi tidak valid.',
            'regulasi.*.regulasi_id.required_with' => 'Regulasi wajib dipilih.',
            'regulasi.*.regulasi_id.exists' => 'Regulasi tidak ditemukan.',

            'lampiran.array' => 'Format data lampiran tidak valid.',
            'lampiran.*.path_file.required' => 'Path file lampiran wajib diisi.',
            'lampiran.*.jenis.in' => 'Jenis lampiran tidak valid.',
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
