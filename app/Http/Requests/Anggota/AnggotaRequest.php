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
        $anggotaId = $this->route('id')?->anggota ?? null;

        return [
            'kode_anggota' => [
                'nullable',
                'string',
                'max:50',
                Rule::unique('anggota')->ignore($anggotaId),
            ],
            'nik' => [
                'nullable',
                'string',
                'max:32',
                Rule::unique('anggota')->ignore($anggotaId),
            ],
            'nama' => 'required|string|max:255',
            'jenis_kelamin' => 'nullable|in:l,p',
            'tempat_lahir' => 'nullable|string|max:255',
            'tanggal_lahir' => 'nullable|date',
            'alamat' => 'nullable|string|max:255',
            'foto' => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
            'jabatan_id' => 'nullable|exists:jabatan,id',
            'unit_id' => 'nullable|exists:unit,id',
            'status' => 'required|in:aktif,nonaktif,cuti',
        ];
    }

    public function messages(): array
    {
        return [
            'kode_anggota.unique' => 'Kode anggota sudah digunakan.',
            'kode_anggota.max' => 'Kode anggota maksimal 50 karakter.',
            'nik.unique' => 'NIK sudah terdaftar.',
            'nik.max' => 'NIK maksimal 32 karakter.',
            'nama.required' => 'Nama wajib diisi.',
            'nama.max' => 'Nama maksimal 255 karakter.',
            'jenis_kelamin.in' => 'Jenis kelamin harus "l" atau "p".',
            'tempat_lahir.max' => 'Tempat lahir maksimal 255 karakter.',
            'tanggal_lahir.date' => 'Tanggal lahir harus berupa tanggal yang valid.',
            'alamat.max' => 'Alamat maksimal 255 karakter.',
            'foto.image' => 'Foto harus berupa file gambar.',
            'foto.mimes' => 'Foto harus berekstensi jpg, jpeg, atau png.',
            'foto.max' => 'Foto maksimal 2MB.',
            'jabatan_id.exists' => 'Jabatan tidak valid.',
            'unit_id.exists' => 'Unit tidak valid.',
            'status.required' => 'Status wajib diisi',
            'status.in' => 'Status harus "aktif", "nonaktif", atau "cuti".',
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
