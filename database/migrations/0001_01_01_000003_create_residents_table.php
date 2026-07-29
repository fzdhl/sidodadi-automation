<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('residents', function (Blueprint $table) {
            $table->id();
            $table->string('nik', 16)->unique();
            $table->string('nama', 150);
            $table->string('tempat_lahir', 100)->nullable();
            $table->date('tanggal_lahir')->nullable();
            $table->string('jenis_kelamin', 20)->nullable();
            $table->string('agama', 50)->nullable();
            $table->string('status_perkawinan', 50)->nullable();
            $table->string('kewarganegaraan', 50)->nullable();
            $table->string('pekerjaan', 100)->nullable();
            $table->text('alamat')->nullable();
            $table->string('jenis_usaha', 150)->nullable();
            $table->string('nama_usaha', 150)->nullable();
            $table->string('lama_usaha', 100)->nullable();
            $table->text('alamat_usaha')->nullable();
            $table->string('nama_anak', 150)->nullable();
            $table->string('nik_anak', 16)->nullable();
            $table->string('nama_sekolah', 150)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('residents');
    }
};
