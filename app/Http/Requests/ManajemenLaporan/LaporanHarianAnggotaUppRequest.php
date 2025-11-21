<?php

namespace App\Http\Requests\ManajemenLaporan;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class LaporanHarianAnggotaUppRequest extends FormRequest
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
            'jenis'      => 'sometimes|in:aman,insiden',
            'catatan'    => 'nullable|string',
            'lat'        => 'sometimes|numeric',
            'lng'        => 'sometimes|numeric',

            'lampiran'   => 'nullable|array',
            'lampiran.*' => 'file|mimes:jpg,jpeg,png,mp4,mov|max:10240',
            'kategori_pelanggaran_id' => 'required_if:jenis,insiden|nullable|exists:kategori_pengaduan,id',
            'severity'                => 'required_if:jenis,insiden|nullable|in:rendah,sedang,tinggi',

            'regulasi_indikatif_id'   => 'nullable|exists:regulasi,id',
        ];
    }

    public function messages(): array
    {
        return [
            'jenis.in'       => 'Jenis laporan harus berupa "aman" atau "insiden".',
            'catatan.string' => 'Catatan harus berupa teks.',
            'lat.numeric'    => 'Latitude harus berupa angka.',
            'lng.numeric'    => 'Longitude harus berupa angka.',
            'lampiran.array'       => 'Lampiran harus dalam format array.',
            'lampiran.*.file'      => 'Lampiran harus berupa file yang valid.',
            'lampiran.*.mimes'     => 'Format lampiran hanya boleh: jpg, jpeg, png, mp4, mov.',
            'lampiran.*.max'       => 'Ukuran lampiran maksimal 10MB per file.',
            'kategori_pelanggaran_id.required_if' => 'Kategori pelanggaran wajib dipilih jika Anda mengubah status menjadi Insiden.',
            'kategori_pelanggaran_id.exists'      => 'Kategori pelanggaran yang dipilih tidak valid.',
            'severity.required_if' => 'Tingkat keparahan (severity) wajib dipilih jika Anda mengubah status menjadi Insiden.',
            'severity.in'          => 'Tingkat keparahan harus salah satu dari: rendah, sedang, atau tinggi.',
            'regulasi_indikatif_id.exists' => 'Regulasi yang dipilih tidak ditemukan di database.',
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
