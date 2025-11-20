<?php

namespace App\Http\Requests\Humas;

use Illuminate\Foundation\Http\FormRequest;

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
        return [
            'tipe' => 'required|string|in:berita,agenda,himbauan',
            'judul' => 'nullable|string|max:255',
            'path_file'   => 'file|mimes:jpg,jpeg,png,mp4,mov|max:10240',
        ];
    }
}


