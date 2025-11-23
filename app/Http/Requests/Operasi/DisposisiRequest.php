<?php

namespace App\Http\Requests\Operasi;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class DisposisiRequest extends FormRequest
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
            'pengaduan_id' => 'required|exists:pengaduan,id',
            'ke_unit_id' => 'nullable|exists:unit,id',
            'catatan' => 'nullable|string|max:500',
            'batas_waktu' => 'nullable|date',
            'status' => 'nullable|in:pending,completed',
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
