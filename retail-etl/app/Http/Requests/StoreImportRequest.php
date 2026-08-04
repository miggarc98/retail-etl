<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreImportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'file' => 'required|file|mimes:csv,txt'
        ];
    }

    public function messages(): array
    {
        return [
            'file.required' => 'El archivo CSV es obligatorio',
            'file.file' => 'El archivo debe ser un archivo válido',
            'file.mimes' => 'El archivo debe ser de tipo CSV o TXT',
        ];
    }
}