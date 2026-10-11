<?php

return [
    'max_file_size_kb' => (int) env('IMPORT_MAX_FILE_SIZE_KB', 10240),

    'allowed_mime_types' => [
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'application/zip',
        'application/octet-stream',
    ],

    'queue' => env('IMPORT_QUEUE', 'imports'),

    'rate_limit_per_minute' => (int) env('IMPORT_RATE_LIMIT_PER_MINUTE', 5),

    'images' => [
        'disk' => 'public',
        'directory' => 'products',
        'timeout' => (int) env('IMPORT_IMAGE_TIMEOUT', 15),
        'max_size_kb' => (int) env('IMPORT_IMAGE_MAX_SIZE_KB', 10240),
    ],

    'columns' => [
        'external_code' => 'Внешний код',
        'name' => 'Наименование',
        'description' => 'Описание',
        'price' => 'Цена: Цена продажи',
        'purchase_price' => 'Закупочная цена',
    ],

    'attribute_prefix' => 'Доп. поле:',

    'image_columns' => [
        'Доп. поле: Ссылки на фото',
        'Доп. поле: Ссылка на упаковку',
    ],
];
