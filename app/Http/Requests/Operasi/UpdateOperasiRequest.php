<?php

namespace App\Http\Requests\Operasi;

use Illuminate\Foundation\Http\FormRequest;

class UpdateOperasiRequest extends FormRequest
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
        // Mendukung route model binding: /operasi/{operasi}
        $id = $this->route('id');

        return [
            'nomor_surat_tugas'  => "nullable|string|max:255|unique:operasi,nomor_surat_tugas,$id",
            'pengaduan_id'       => 'nullable|integer|exists:pengaduan,id',
            'judul'              => 'required|string|max:255',
            'uraian'             => 'nullable|string',
            'mulai'              => 'nullable|date',
            'selesai'            => 'nullable|date|after_or_equal:mulai',
        ];
    }
}
