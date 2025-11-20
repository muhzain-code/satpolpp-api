<?php

namespace App\Http\Requests\Operasi;

use Illuminate\Foundation\Http\FormRequest;

class UpdateOperasiPenugasanRequest extends FormRequest
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
    public function rules()
    {
        $id = $this->route('id');

        return [
            'operasi_id' => 'required|exists:operasi,id',
            'anggota_id' => 'required|exists:anggota,id|unique:operasi_penugasan,anggota_id,' . $id . ',id,operasi_id,' . $this->operasi_id,
            'peran'      => 'nullable|string|max:100',
        ];
    }

    public function messages()
    {
        return [
            'anggota_id.unique' => 'Anggota ini sudah ditugaskan pada operasi tersebut.',
        ];
    }
}
