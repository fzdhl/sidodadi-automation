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

    /*
    |--------------------------------------------------------------------------
    | Uppercase placeholders
    |--------------------------------------------------------------------------
    |
    | List placeholder field names that should be converted to uppercase when
    | the document is generated. Use lowercase field keys as defined in the
    | templates/fields configuration. Leave empty to keep original input.
    |
    */
    'uppercase_placeholders' => [
        'nama',
        'nama_anak',
        'keperluan',
        'jenis_usaha',

    ],

    'types' => [
        'domisili' => [
            'name' => 'Surat Keterangan Domisili',
            'template' => 'TEMPLATE KETERANGAN DOMISILI.docx',
        ],
        'usaha' => [
            'name' => 'Surat Keterangan Usaha',
            'template' => 'TEMPLATE KETERANGAN USAHA.docx',
        ],
        'tidak-mampu' => [
            'name' => 'Surat Keterangan Tidak Mampu',
            'template' => 'TEMPLATE KETERANGAN TIDAK MAMPU.docx',
        ],
        'keterangan' => [
            'name' => 'Surat Keterangan',
            'template' => 'TEMPLATE KETERANGAN BIASA.docx',
        ],
    ],

    'fields' => [
        'common' => [
            ['name' => 'nomor_surat', 'label' => 'Nomor Surat', 'type' => 'text', 'rules' => ['required', 'string', 'max:100']],
            ['name' => 'tanggal_surat', 'label' => 'Tanggal Pembuatan Surat', 'type' => 'date', 'rules' => ['required', 'date']],
            ['name' => 'nama', 'label' => 'Nama Lengkap', 'type' => 'text', 'rules' => ['required', 'string', 'max:150']],
            ['name' => 'nik', 'label' => 'Nomor Induk Kependudukan (NIK)', 'type' => 'text', 'rules' => ['required', 'digits:16']],
            ['name' => 'tempat_lahir', 'label' => 'Tempat Lahir', 'type' => 'text', 'rules' => ['required', 'string', 'max:100']],
            ['name' => 'tanggal_lahir', 'label' => 'Tanggal Lahir', 'type' => 'date', 'rules' => ['required', 'date']],
            ['name' => 'jenis_kelamin', 'label' => 'Jenis Kelamin', 'type' => 'select', 'options' => ['Laki-laki', 'Perempuan'], 'rules' => ['required', 'in:Laki-laki,Perempuan']],
            ['name' => 'agama', 'label' => 'Agama', 'type' => 'text', 'rules' => ['required', 'string', 'max:50']],
            ['name' => 'status_perkawinan', 'label' => 'Status Perkawinan', 'type' => 'select', 'options' => ['Belum Kawin', 'Kawin', 'Cerai'], 'rules' => ['required', 'in:Belum Kawin,Kawin,Cerai']],
            ['name' => 'kewarganegaraan', 'label' => 'Kewarganegaraan', 'type' => 'text', 'rules' => ['required', 'string', 'max:50']],
            ['name' => 'pekerjaan', 'label' => 'Pekerjaan', 'type' => 'text', 'rules' => ['required', 'string', 'max:100'], 'placeholder' => 'Contoh: Wiraswasta, PNS, Guru, dll.'],
            ['name' => 'alamat', 'label' => 'Alamat', 'type' => 'textarea', 'rules' => ['required', 'string', 'max:500']],
            ['name' => 'keperluan', 'label' => 'Keperluan', 'type' => 'textarea', 'rules' => ['required', 'string', 'max:500'], 'placeholder' => 'Contoh: Pengajuan surat keterangan usaha, pengajuan beasiswa, dll.'],
        ],
        'domisili' => [],
        'usaha' => [
            ['name' => 'jenis_usaha', 'label' => 'Jenis Usaha', 'type' => 'text', 'rules' => ['required', 'string', 'max:150']],
            ['name' => 'nama_usaha', 'label' => 'Nama Usaha', 'type' => 'text', 'rules' => ['required', 'string', 'max:150']],
            ['name' => 'lama_usaha', 'label' => 'Lama Usaha', 'type' => 'text', 'rules' => ['required', 'string', 'max:100']],
            ['name' => 'alamat_usaha', 'label' => 'Alamat Usaha', 'type' => 'textarea', 'rules' => ['required', 'string', 'max:500']],
        ],
        'tidak-mampu' => [
            ['name' => 'nama_anak', 'label' => 'Nama Anak', 'type' => 'text', 'rules' => ['required', 'string', 'max:150']],
            ['name' => 'nik_anak', 'label' => 'NIK Anak', 'type' => 'text', 'rules' => ['required', 'digits:16']],
            ['name' => 'nama_sekolah', 'label' => 'Nama Sekolah Anak', 'type' => 'text', 'rules' => ['required', 'string', 'max:150']],
        ],
        'keterangan' => [
            ['name' => 'nama_anak', 'label' => 'Nama Anak', 'type' => 'text', 'rules' => ['required', 'string', 'max:150']],
            ['name' => 'nik_anak', 'label' => 'NIK Anak', 'type' => 'text', 'rules' => ['required', 'digits:16']],
        ],
    ],
];
