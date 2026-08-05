<?php

// dummy edit to use read_file
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
        'orang-yang-sama' => ['name' => 'Keterangan 1 Orang Yang Sama', 'template' => 'letters/TEMPLATE KETERANGAN 1 ORANG YANG SAMA.docx'],
        'belum-bekerja' => ['name' => 'Keterangan Belum Bekerja', 'template' => 'letters/TEMPLATE KETERANGAN BELUM BEKERJA.docx'],
        'belum-nikah' => ['name' => 'Keterangan Belum Nikah', 'template' => 'letters/TEMPLATE KETERANGAN BELUM NIKAH.docx'],
        'belum-punya-rumah' => ['name' => 'Keterangan Belum Punya Rumah', 'template' => 'letters/TEMPLATE KETERANGAN BELUM PUNYA RUMAH.docx'],
        'kuasa-ambil-bpkb' => ['name' => 'Keterangan Biasa Kuasa Ambil BPKB', 'template' => 'letters/TEMPLATE KETERANGAN BIASA KUASA AMBIL BPKB.docx'],
        'pengantar-kua' => ['name' => 'Keterangan Biasa Pengantar ke KUA', 'template' => 'letters/TEMPLATE KETERANGAN BIASA PENGANTAR KE KUA.docx'],
        'tidak-diketahui-keberadaannya' => ['name' => 'Keterangan Biasa Tidak Diketahui Keberadaannya', 'template' => 'letters/TEMPLATE KETERANGAN BIASA TIDAK DI KETAHUI KEBERADAANNYA.docx'],
        'keterangan-biasa' => ['name' => 'Keterangan Biasa', 'template' => 'letters/TEMPLATE KETERANGAN BIASA.docx'],
        'domisili-anak-ikut-orang-tua' => ['name' => 'Keterangan Domisili Anak Ikut Orang Tua', 'template' => 'letters/TEMPLATE KETERANGAN DOMISILI ANAK IKUT ORANG TUA.docx'],
        'domisili-anak' => ['name' => 'Keterangan Domisili Anak', 'template' => 'letters/TEMPLATE KETERANGAN DOMISILI ANAK.docx'],
        'domisili' => ['name' => 'Keterangan Domisili', 'template' => 'letters/TEMPLATE KETERANGAN DOMISILI.docx'],
        'ganti-nama-pdam' => ['name' => 'Keterangan Ganti Nama PDAM', 'template' => 'letters/TEMPLATE KETERANGAN GANTI NAMA PDAM.docx'],
        'kesenian' => ['name' => 'Keterangan Kesenian', 'template' => 'letters/TEMPLATE KETERANGAN KESENIAN.docx'],
        'pengalaman-kerja' => ['name' => 'Keterangan Pengalaman Kerja', 'template' => 'letters/TEMPLATE KETERANGAN PENGALAMAN KERJA.docx'],
        'pengampu-wali' => ['name' => 'Keterangan Pengampu Wali', 'template' => 'letters/TEMPLATE KETERANGAN PENGAMPU WALI.docx'],
        'tidak-mampu' => ['name' => 'Keterangan Tidak Mampu', 'template' => 'letters/TEMPLATE KETERANGAN TIDAK MAMPU.docx'],
        'tidak-memiliki-penghasilan-tambahan' => ['name' => 'Keterangan Tidak Memiliki Penghasilan Tambahan', 'template' => 'letters/TEMPLATE KETERANGAN TIDAK MEMILIKI PENGHASILAN TAMBAHAN.docx'],
        'tidak-memiliki-usaha-sampingan' => ['name' => 'Keterangan Tidak Memiliki Usaha Sampingan', 'template' => 'letters/TEMPLATE KETERANGAN TIDAK MEMILIKI USAHA SAMPINGAN.docx'],
        'tidak-punya-ijazah' => ['name' => 'Keterangan Tidak Punya Ijazah', 'template' => 'letters/TEMPLATE KETERANGAN TIDAK PUNYA IJAZAH.docx'],
        'usaha' => ['name' => 'Keterangan Usaha', 'template' => 'letters/TEMPLATE KETERANGAN USAHA.docx'],
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
        'orang-yang-sama' => [
            ['name' => 'isi_keterangan', 'label' => 'Isi Keterangan', 'type' => 'textarea', 'rules' => ['required', 'string', 'max:1000']],
        ],
        'belum-bekerja' => [
            ['name' => 'pihak_1_nama', 'label' => 'Nama Anak/Pihak Terkait', 'type' => 'text', 'rules' => ['nullable', 'string', 'max:150']],
            ['name' => 'pihak_1_nik', 'label' => 'NIK Anak/Pihak Terkait', 'type' => 'text', 'rules' => ['nullable', 'digits:16']],
            ['name' => 'pihak_1_sekolah_universitas', 'label' => 'Sekolah/Universitas Pihak Terkait', 'type' => 'text', 'rules' => ['nullable', 'string', 'max:200']],
        ],
        'belum-nikah' => [],
        'belum-punya-rumah' => [],
        'kuasa-ambil-bpkb' => [
            ['name' => 'pihak_1_nama', 'label' => 'Nama Pihak Terkait', 'type' => 'text', 'rules' => ['required', 'string', 'max:150']],
            ['name' => 'pihak_1_nik', 'label' => 'NIK Pihak Terkait', 'type' => 'text', 'rules' => ['required', 'digits:16']],
            ['name' => 'pihak_1_alamat', 'label' => 'Alamat Pihak Terkait', 'type' => 'textarea', 'rules' => ['required', 'string', 'max:500']],
            ['name' => 'merek_model', 'label' => 'Merek/Model Kendaraan', 'type' => 'text', 'rules' => ['required', 'string', 'max:150']],
            ['name' => 'nomor_polisi', 'label' => 'Nomor Polisi', 'type' => 'text', 'rules' => ['required', 'string', 'max:50']],
            ['name' => 'nomor_rangka', 'label' => 'Nomor Rangka', 'type' => 'text', 'rules' => ['required', 'string', 'max:100']],
            ['name' => 'nomor_mesin', 'label' => 'Nomor Mesin', 'type' => 'text', 'rules' => ['required', 'string', 'max:100']],
            ['name' => 'nomor_bpkb', 'label' => 'Nomor BPKB', 'type' => 'text', 'rules' => ['nullable', 'string', 'max:100']],
            ['name' => 'warna_kendaraan', 'label' => 'Warna Kendaraan', 'type' => 'text', 'rules' => ['required', 'string', 'max:100']],
        ],
        'pengantar-kua' => [
            ['name' => 'pihak_1_nama', 'label' => 'Nama Pihak Terkait', 'type' => 'text', 'rules' => ['nullable', 'string', 'max:150']],
            ['name' => 'pihak_1_nik', 'label' => 'NIK Pihak Terkait', 'type' => 'text', 'rules' => ['nullable', 'digits:16']],
            ['name' => 'pihak_1_alamat', 'label' => 'Alamat Pihak Terkait', 'type' => 'textarea', 'rules' => ['nullable', 'string', 'max:500']],
        ],
        'tidak-diketahui-keberadaannya' => [
            ['name' => 'pihak_1_nama', 'label' => 'Nama Pihak yang Tidak Diketahui', 'type' => 'text', 'rules' => ['nullable', 'string', 'max:150']],
            ['name' => 'pihak_1_alamat', 'label' => 'Alamat Terakhir Pihak Terkait', 'type' => 'textarea', 'rules' => ['nullable', 'string', 'max:500']],
        ],
        'keterangan-biasa' => [
            ['name' => 'isi_keterangan', 'label' => 'Isi Keterangan', 'type' => 'textarea', 'rules' => ['required', 'string', 'max:1000']],
            ['name' => 'pihak_1_nama', 'label' => 'Nama Pihak Terkait', 'type' => 'text', 'rules' => ['nullable', 'string', 'max:150']],
            ['name' => 'pihak_1_nik', 'label' => 'NIK Pihak Terkait', 'type' => 'text', 'rules' => ['nullable', 'digits:16']],
        ],
        'domisili-anak-ikut-orang-tua' => [
            ['name' => 'pihak_1_nama', 'label' => 'Nama Orang Tua', 'type' => 'text', 'rules' => ['required', 'string', 'max:150']],
            ['name' => 'pihak_1_nik', 'label' => 'NIK Orang Tua', 'type' => 'text', 'rules' => ['required', 'digits:16']],
            ['name' => 'pihak_1_ttl', 'label' => 'TTL Orang Tua', 'type' => 'text', 'rules' => ['nullable', 'string', 'max:150']],
            ['name' => 'pihak_1_jenis_kelamin', 'label' => 'Jenis Kelamin Orang Tua', 'type' => 'select', 'options' => ['Laki-laki', 'Perempuan'], 'rules' => ['nullable', 'in:Laki-laki,Perempuan']],
        ],
        'domisili-anak' => [
            ['name' => 'pihak_1_nama', 'label' => 'Nama Anak', 'type' => 'text', 'rules' => ['required', 'string', 'max:150']],
            ['name' => 'pihak_1_nik', 'label' => 'NIK Anak', 'type' => 'text', 'rules' => ['required', 'digits:16']],
        ],
        'ganti-nama-pdam' => [
            ['name' => 'alamat_objek', 'label' => 'Alamat Objek PDAM', 'type' => 'textarea', 'rules' => ['required', 'string', 'max:500']],
            ['name' => 'nomor_pelanggan', 'label' => 'Nomor Pelanggan', 'type' => 'text', 'rules' => ['required', 'string', 'max:100']],
            ['name' => 'nama_pelanggan_lama', 'label' => 'Nama Pelanggan Lama', 'type' => 'text', 'rules' => ['required', 'string', 'max:150']],
            ['name' => 'nama_pelanggan_baru', 'label' => 'Nama Pelanggan Baru', 'type' => 'text', 'rules' => ['required', 'string', 'max:150']],
        ],
        'kesenian' => [
            ['name' => 'kelompok_nama', 'label' => 'Nama Kelompok Kesenian', 'type' => 'text', 'rules' => ['required', 'string', 'max:150']],
            ['name' => 'kelompok_ketua', 'label' => 'Ketua Kelompok', 'type' => 'text', 'rules' => ['required', 'string', 'max:150']],
            ['name' => 'kelompok_alamat', 'label' => 'Alamat Kelompok', 'type' => 'textarea', 'rules' => ['required', 'string', 'max:500']],
        ],
        'pengalaman-kerja' => [
            ['name' => 'nama_instansi', 'label' => 'Nama Instansi', 'type' => 'text', 'rules' => ['required', 'string', 'max:200']],
            ['name' => 'jabatan', 'label' => 'Jabatan', 'type' => 'text', 'rules' => ['required', 'string', 'max:150']],
            ['name' => 'periode_mulai', 'label' => 'Periode Mulai', 'type' => 'text', 'rules' => ['required', 'string', 'max:100']],
            ['name' => 'periode_selesai', 'label' => 'Periode Selesai', 'type' => 'text', 'rules' => ['required', 'string', 'max:100']],
        ],
        'pengampu-wali' => [
            ['name' => 'yang_diampu_nama', 'label' => 'Nama Yang Diampu', 'type' => 'text', 'rules' => ['required', 'string', 'max:150']],
            ['name' => 'yang_diampu_nik', 'label' => 'NIK Yang Diampu', 'type' => 'text', 'rules' => ['required', 'digits:16']],
            ['name' => 'pengampu_nama', 'label' => 'Nama Pengampu/Wali', 'type' => 'text', 'rules' => ['required', 'string', 'max:150']],
            ['name' => 'pengampu_nik', 'label' => 'NIK Pengampu/Wali', 'type' => 'text', 'rules' => ['required', 'digits:16']],
            ['name' => 'tanggal_mulai_pengampuan', 'label' => 'Tanggal Mulai Pengampuan', 'type' => 'date', 'rules' => ['required', 'date']],
            ['name' => 'alasan_pengampuan', 'label' => 'Alasan Pengampuan', 'type' => 'textarea', 'rules' => ['required', 'string', 'max:1000']],
        ],
        'tidak-memiliki-penghasilan-tambahan' => [
            ['name' => 'pihak_1_nama', 'label' => 'Nama Pihak Terkait', 'type' => 'text', 'rules' => ['nullable', 'string', 'max:150']],
            ['name' => 'pihak_1_nik', 'label' => 'NIK Pihak Terkait', 'type' => 'text', 'rules' => ['nullable', 'digits:16']],
            ['name' => 'pihak_1_alamat', 'label' => 'Alamat Pihak Terkait', 'type' => 'textarea', 'rules' => ['nullable', 'string', 'max:500']],
        ],
        'tidak-memiliki-usaha-sampingan' => [
            ['name' => 'pihak_1_nama', 'label' => 'Nama Anak', 'type' => 'text', 'rules' => ['nullable', 'string', 'max:150']],
            ['name' => 'pihak_1_nik', 'label' => 'NIK Anak', 'type' => 'text', 'rules' => ['nullable', 'digits:16']],
            ['name' => 'pihak_1_sekolah_universitas', 'label' => 'Sekolah/Universitas Pihak Terkait', 'type' => 'text', 'rules' => ['nullable', 'string', 'max:200']],
        ],
        'tidak-punya-ijazah' => [
            ['name' => 'jenjang_pendidikan', 'label' => 'Jenjang Pendidikan', 'type' => 'text', 'rules' => ['nullable', 'string', 'max:100']],
            ['name' => 'alasan', 'label' => 'Alasan/Keterangan', 'type' => 'textarea', 'rules' => ['nullable', 'string', 'max:500']],
        ],
        'usaha' => [
            ['name' => 'jenis_usaha', 'label' => 'Jenis Usaha', 'type' => 'text', 'rules' => ['required', 'string', 'max:150']],
            ['name' => 'nama_usaha', 'label' => 'Nama Usaha', 'type' => 'text', 'rules' => ['required', 'string', 'max:150']],
            ['name' => 'lama_usaha', 'label' => 'Lama Usaha', 'type' => 'text', 'rules' => ['required', 'string', 'max:100']],
            ['name' => 'alamat_usaha', 'label' => 'Alamat Usaha', 'type' => 'textarea', 'rules' => ['required', 'string', 'max:500']],
        ],
        'tidak-mampu' => [
            ['name' => 'pihak_1_nama', 'label' => 'Nama Anak', 'type' => 'text', 'rules' => ['required', 'string', 'max:150']],
            ['name' => 'pihak_1_nik', 'label' => 'NIK Anak', 'type' => 'text', 'rules' => ['required', 'digits:16']],
            ['name' => 'pihak_1_sekolah_universitas', 'label' => 'Sekolah/Universitas Anak', 'type' => 'text', 'rules' => ['required', 'string', 'max:150']],
        ],
        'keterangan' => [
            ['name' => 'nama_anak', 'label' => 'Nama Anak', 'type' => 'text', 'rules' => ['required', 'string', 'max:150']],
            ['name' => 'nik_anak', 'label' => 'NIK Anak', 'type' => 'text', 'rules' => ['required', 'digits:16']],
        ],
    ],
];
