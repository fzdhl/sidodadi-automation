<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Resident extends Model
{
    use HasFactory;

    protected $fillable = [
        'nik',
        'nama',
        'tempat_lahir',
        'tanggal_lahir',
        'jenis_kelamin',
        'agama',
        'status_perkawinan',
        'kewarganegaraan',
        'pekerjaan',
        'alamat',
        'jenis_usaha',
        'nama_usaha',
        'lama_usaha',
        'alamat_usaha',
        'nama_anak',
        'nik_anak',
        'nama_sekolah',
    ];

    protected $casts = [
        'tanggal_lahir' => 'date:Y-m-d',
    ];
}
