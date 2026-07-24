<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Document templates
    |--------------------------------------------------------------------------
    |
    | Templates are kept in resources/templates so they can be versioned with
    | the application. Add a new entry here when a new letter is introduced.
    |
    */
    'templates_path' => resource_path('templates'),

    'output_disk' => 'local',

    'output_directory' => 'generated-documents',

    'types' => [
        'domisili' => [
            'name' => 'Surat Keterangan Domisili',
            'template' => 'surat-keterangan-domisili.docx',
        ],
        'usaha' => [
            'name' => 'Surat Keterangan Usaha',
            'template' => 'surat-keterangan-usaha.docx',
        ],
        'tidak-mampu' => [
            'name' => 'Surat Keterangan Tidak Mampu',
            'template' => 'surat-keterangan-tidak-mampu.docx',
        ],
        'keterangan' => [
            'name' => 'Surat Keterangan',
            'template' => 'surat-keterangan.docx',
        ],
    ],
];
