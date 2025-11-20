<?php

namespace App\Http\Requests\ManajemenLaporan;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class LaporanHarianAnggotaRequest extends FormRequest
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
            'jenis'        => 'required|in:aman,insiden',
            'catatan'      => 'nullable|string',
            'lat'          => 'nullable|numeric',
            'lng'          => 'nullable|numeric',

            'lampiran'     => 'nullable|array',
            'lampiran.*'   => 'file|mimes:jpg,jpeg,png,mp4,mov|max:10240',
        ];
    }

    public function messages(): array
    {
        return [
            'jenis.required' => 'Jenis laporan wajib diisi',
            'jenis.in'       => 'Jenis laporan harus aman atau insiden',

            'catatan.string' => 'Catatan harus berupa teks yang valid',

            'lat.numeric'    => 'Latitude harus berupa angka',
            'lng.numeric'    => 'Longitude harus berupa angka',

            'lampiran.array' => 'Lampiran harus dalam format array',

            'lampiran.*.file'  => 'Lampiran harus berupa file',
            'lampiran.*.mimes' => 'Lampiran hanya boleh: jpg, jpeg, png, mp4, mov',
            'lampiran.*.max'   => 'Ukuran lampiran maksimal 10MB per file',
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
