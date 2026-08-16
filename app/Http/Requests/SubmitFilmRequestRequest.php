<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SubmitFilmRequestRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'title' => 'required|string|min:2|max:255',
            'type' => 'required|string|in:movie,tv,series,dracin',
            'year' => 'nullable|integer|min:1900|max:2099',
        ];
    }

    public function messages(): array
    {
        return [
            'title.required' => 'Judul film wajib diisi.',
            'title.min' => 'Judul film minimal 2 karakter.',
            'type.required' => 'Tipe film wajib dipilih.',
            'type.in' => 'Tipe film harus movie, tv, atau dracin.',
            'year.integer' => 'Tahun harus berupa angka.',
        ];
    }
}
