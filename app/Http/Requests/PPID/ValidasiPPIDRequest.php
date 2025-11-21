<?php

namespace App\Http\Requests\PPID;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;

class ValidasiPPIDRequest extends FormRequest
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
            'status' => ['required', Rule::in(['dijawab', 'ditolak'])],
            'jawaban_ppid' => ['required_if:status,dijawab', 'nullable', 'string'],
            'file_jawaban' => [
                'nullable',
                'file',
                'mimes:pdf', 
                function ($attribute, $value, $fail) {
                    $status = request('status');
                    if ($status === 'ditolak' && !empty($value)) {
                        $fail('File jawaban tidak boleh diisi jika status ditolak.');
                    }
                }
            ]
        ];
    }


    public function messages(): array
    {
        return [
            'status.required' => 'Status wajib diisi.',
            'status.in' => 'Status harus diisi dengan dijawab atau ditolak.',
            'jawaban_ppid.required_if' => 'Jawaban PPID wajib diisi jika status dijawab.',
            'file_jawaban.file' => 'File jawaban harus berupa file.',
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
