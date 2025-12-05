<?php

namespace App\Http\Requests\Operasi;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class PenugasanRequest extends FormRequest
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
            'disposisi_id' => 'nullable|exists:disposisi,id',
            'operasi_id' => 'nullable|exists:operasi,id',
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
