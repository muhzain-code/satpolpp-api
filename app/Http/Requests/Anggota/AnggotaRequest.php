<?php

namespace App\Http\Requests\Anggota;

use Illuminate\Validation\Rule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class AnggotaRequest extends FormRequest
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
        $anggotaId = $this->route('id');

        return [
            'kode_anggota' => [
                'required',
                'string',
                'max:50',
                Rule::unique('anggota', 'kode_anggota')->ignore($anggotaId),
            ],

            'nik' => [
                'nullable',
                'string',
                'max:16',
                Rule::unique('anggota', 'nik')->ignore($anggotaId),
            ],

            'nip' => [
                'nullable',
                'string',
                'max:18',
                Rule::unique('anggota', 'nip')->ignore($anggotaId),
            ],

            'nama' => 'required|string|max:255',

            'jenis_kelamin' => 'nullable|in:L,P',

            'tempat_lahir' => 'nullable|string|max:255',
            'tanggal_lahir' => 'nullable|date',

            'provinsi_id'  => 'nullable|exists:provinsi,id',
            'kabupaten_id' => 'nullable|exists:kabupaten,id',
            'kecamatan_id' => 'nullable|exists:kecamatan,id',
            'desa_id'      => 'nullable|exists:desa,id',

            'no_hp' => 'nullable|string|max:20',

            'foto' => 'nullable|image|mimes:jpg,jpeg,png|max:2048',

            'jabatan_id' => 'nullable|exists:jabatan,id',
            'unit_id'    => 'nullable|exists:unit,id',

            'status' => 'required|in:aktif,nonaktif,cuti,mutasi,pensiun,meninggal',

            'jenis_kepegawaian' => 'nullable|in:asn,p3k,nonasn',
        ];
    }

    public function messages(): array
    {
        return [
            'kode_anggota.required' => 'Kode anggota wajib diisi.',
            'kode_anggota.unique'   => 'Kode anggota sudah digunakan.',
            'kode_anggota.max'      => 'Kode anggota maksimal 50 karakter.',

            'nik.unique' => 'NIK sudah terdaftar.',
            'nik.max'    => 'NIK maksimal 16 karakter.',

            'nip.unique' => 'NIP sudah terdaftar.',
            'nip.max'    => 'NIP maksimal 18 karakter.',

            'nama.required' => 'Nama wajib diisi.',

            'jenis_kelamin.in' => 'Jenis kelamin harus L atau P.',

            'tempat_lahir.max' => 'Tempat lahir maksimal 255 karakter.',
            'tanggal_lahir.date' => 'Tanggal lahir harus berupa tanggal valid.',

            'provinsi_id.exists'  => 'Provinsi tidak valid.',
            'kabupaten_id.exists' => 'Kabupaten tidak valid.',
            'kecamatan_id.exists' => 'Kecamatan tidak valid.',
            'desa_id.exists'      => 'Desa tidak valid.',

            'no_hp.max' => 'Nomor HP maksimal 20 karakter.',

            'foto.image' => 'Foto harus berupa file gambar.',
            'foto.mimes' => 'Foto harus berekstensi jpg, jpeg, atau png.',
            'foto.max'   => 'Foto maksimal 2MB.',

            'jabatan_id.exists' => 'Jabatan tidak valid.',
            'unit_id.exists'    => 'Unit tidak valid.',

            'status.required' => 'Status wajib diisi.',
            'status.in'       => 'Status tidak valid.',

            'jenis_kepegawaian.in' => 'Jenis kepegawaian tidak valid.',
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
