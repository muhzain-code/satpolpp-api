<?php

namespace App\Http\Requests\Humas;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class GaleriRequest extends FormRequest
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
        $isUpdate = $this->isMethod('PUT');

        return [
            'judul' => ['required', 'string', 'max:255'],

            'path_file' => [
                $isUpdate ? 'nullable' : 'required',
                'file',
                'mimes:jpg,jpeg,png,gif,webp,mp4,mov,avi,mkv',
                'max:20480',
            ]
        ];
    }

    public function messages(): array
    {
        return [
            'judul.required' => 'Judul wajib diisi.',
            'judul.max'      => 'Judul maksimal 255 karakter.',

            'path_file.file'  => 'File tidak valid.',
            'path_file.mimes' => 'File harus berupa JPG, JPEG, PNG, GIF, WEBP, MP4, MOV, AVI, atau MKV.',
            'path_file.max'   => 'Ukuran file maksimal 20 MB.',
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
