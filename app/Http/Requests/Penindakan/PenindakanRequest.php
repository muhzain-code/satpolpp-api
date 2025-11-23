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
            // Salah satu wajib diisi
            'operasi_id'     => ['nullable', 'integer', 'exists:operasi,id'],
            'pengaduan_id'   => ['nullable', 'integer', 'exists:pengaduan,id'],

            'uraian' => ['nullable', 'string'],

            'denda' => ['nullable', 'numeric', 'min:0'],

            // Validasi PPNS — di DB default menunggu, validator adalah users
            'status_validasi_ppns' => ['nullable', 'in:menunggu,ditolak,disetujui'],
            'catatan_validasi_ppns' => ['nullable', 'string'],
            'ppns_validator_id' => ['nullable', 'integer', 'exists:users,id'],

            // REGULASI — pasal_dilanggar adalah JSON
            'regulasi' => ['nullable', 'array'],
            'regulasi.*.regulasi_id' => ['required_with:regulasi', 'integer', 'exists:regulasi,id'],
            'regulasi.*.pasal_dilanggar' => ['nullable'],

            // LAMPIRAN
            'lampiran' => ['nullable', 'array'],
            'lampiran.*.path_file' => ['required', 'string', 'max:1000'],
            'lampiran.*.nama_file' => ['nullable', 'string', 'max:255'],
            'lampiran.*.jenis' => ['nullable', 'in:foto,video,dokumen'],
        ];
    }


    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $filled = array_filter([
                $this->input('operasi_id'),
                $this->input('pengaduan_id'),
            ]);

            if (count($filled) === 0) {
                $validator->errors()->add(
                    'sumber_kegiatan',
                    'Salah satu dari operasi atau pengaduan wajib diisi.'
                );
            }

            if (count($filled) > 1) {
                $validator->errors()->add(
                    'sumber_kegiatan',
                    'Hanya satu dari operasi atau pengaduan yang boleh diisi.'
                );
            }
        });
    }

    public function messages(): array
    {
        return [
            'operasi_id.exists' => 'Operasi tidak ditemukan.',
            'pengaduan_id.exists' => 'Pengaduan tidak ditemukan.',

            'uraian.string' => 'Uraian harus berupa teks.',

            'denda.numeric' => 'Denda harus berupa angka.',
            'denda.min' => 'Denda tidak boleh bernilai negatif.',

            'status_validasi_ppns.in' => 'Status validasi PPNS tidak valid.',
            'catatan_validasi_ppns.string' => 'Catatan validasi PPNS harus berupa teks.',
            'ppns_validator_id.exists' => 'PPNS validator tidak ditemukan.',

            'regulasi.array' => 'Format data regulasi tidak valid.',
            'regulasi.*.regulasi_id.required_with' => 'Regulasi wajib dipilih.',
            'regulasi.*.regulasi_id.exists' => 'Regulasi tidak ditemukan.',

            'lampiran.array' => 'Format data lampiran tidak valid.',
            'lampiran.*.path_file.required' => 'Path file lampiran wajib diisi.',
            'lampiran.*.path_file.max' => 'Path file terlalu panjang.',
            'lampiran.*.nama_file.string' => 'Nama file harus berupa teks.',
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
