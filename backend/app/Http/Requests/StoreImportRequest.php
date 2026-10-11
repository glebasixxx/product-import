<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreImportRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        /** @var list<string> $mimeTypes */
        $mimeTypes = config('import.allowed_mime_types');

        return [
            'file' => [
                'required',
                'file',
                'extensions:xlsx',
                'mimetypes:'.implode(',', $mimeTypes),
                'max:'.config('import.max_file_size_kb'),
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'file.required' => 'Выберите файл для импорта.',
            'file.file' => 'Файл не был загружен.',
            'file.extensions' => 'Допускаются только файлы с расширением .xlsx.',
            'file.mimetypes' => 'Файл не является xlsx документом.',
            'file.max' => 'Размер файла не должен превышать :max КБ.',
        ];
    }
}
