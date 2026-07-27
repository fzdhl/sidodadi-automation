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
            'template' => 'KETERANGAN DOMISILI.docx',
        ],
        'usaha' => [
            'name' => 'Surat Keterangan Usaha',
            'template' => 'KETERANGAN USAHA.docx',
        ],
        'tidak-mampu' => [
            'name' => 'Surat Keterangan Tidak Mampu',
            'template' => 'KETERANGAN TIDAK MAMPU.docx',
        ],
        'keterangan' => [
            'name' => 'Surat Keterangan',
            'template' => 'KETERANGAN BIASA.docx',
        ],
    ],

    'fields' => [
        'common' => [
            ['name' => 'nomor_surat', 'label' => 'Nomor surat', 'type' => 'text', 'rules' => ['required', 'string', 'max:100']],
            ['name' => 'tanggal_surat', 'label' => 'Tanggal surat', 'type' => 'date', 'rules' => ['required', 'date']],
            ['name' => 'nama', 'label' => 'Nama lengkap', 'type' => 'text', 'rules' => ['required', 'string', 'max:150']],
            ['name' => 'nik', 'label' => 'NIK', 'type' => 'text', 'rules' => ['required', 'digits:16']],
            ['name' => 'tempat_lahir', 'label' => 'Tempat lahir', 'type' => 'text', 'rules' => ['required', 'string', 'max:100']],
            ['name' => 'tanggal_lahir', 'label' => 'Tanggal lahir', 'type' => 'date', 'rules' => ['required', 'date']],
            ['name' => 'jenis_kelamin', 'label' => 'Jenis kelamin', 'type' => 'select', 'options' => ['Laki-laki', 'Perempuan'], 'rules' => ['required', 'in:Laki-laki,Perempuan']],
            ['name' => 'agama', 'label' => 'Agama', 'type' => 'text', 'rules' => ['required', 'string', 'max:50']],
            ['name' => 'status_perkawinan', 'label' => 'Status perkawinan', 'type' => 'text', 'rules' => ['required', 'string', 'max:50']],
            ['name' => 'kewarganegaraan', 'label' => 'Kewarganegaraan', 'type' => 'text', 'rules' => ['required', 'string', 'max:50']],
            ['name' => 'pekerjaan', 'label' => 'Pekerjaan', 'type' => 'text', 'rules' => ['required', 'string', 'max:100']],
            ['name' => 'alamat', 'label' => 'Alamat', 'type' => 'textarea', 'rules' => ['required', 'string', 'max:500']],
            ['name' => 'keperluan', 'label' => 'Keperluan', 'type' => 'textarea', 'rules' => ['required', 'string', 'max:500']],
        ],
        'domisili' => [],
        'usaha' => [
            ['name' => 'jenis_usaha', 'label' => 'Jenis usaha', 'type' => 'text', 'rules' => ['required', 'string', 'max:150']],
            ['name' => 'nama_usaha', 'label' => 'Nama usaha', 'type' => 'text', 'rules' => ['required', 'string', 'max:150']],
            ['name' => 'lama_usaha', 'label' => 'Lama usaha', 'type' => 'text', 'rules' => ['required', 'string', 'max:100']],
            ['name' => 'alamat_usaha', 'label' => 'Alamat usaha', 'type' => 'textarea', 'rules' => ['required', 'string', 'max:500']],
        ],
        'tidak-mampu' => [
            ['name' => 'nama_anak', 'label' => 'Nama anak', 'type' => 'text', 'rules' => ['required', 'string', 'max:150']],
            ['name' => 'nik_anak', 'label' => 'NIK anak', 'type' => 'text', 'rules' => ['required', 'digits:16']],
            ['name' => 'nama_sekolah', 'label' => 'Nama sekolah', 'type' => 'text', 'rules' => ['required', 'string', 'max:150']],
        ],
        'keterangan' => [
            // The common nama and nik fields represent the parent in this template.
            ['name' => 'anak_nama', 'label' => 'Nama anak', 'type' => 'text', 'rules' => ['required', 'string', 'max:150']],
            ['name' => 'anak_nik', 'label' => 'NIK anak', 'type' => 'text', 'rules' => ['required', 'digits:16']],
        ],
    ],
];
