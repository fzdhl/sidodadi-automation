# Windows Desktop Packaging Guide - Sidodadi Document Generator

Dokumen ini menjelaskan dua cara untuk memaketkan aplikasi Laravel ini menjadi aplikasi desktop Windows *offline-first*.

---

## 🚀 Metode 1: Ultra-Lightweight Edge App Mode (Rekomendasi Utama)

Metode ini memanfaatkan **Microsoft Edge App Mode** (bawaan Windows 10/11) dan **PHP Portable**, sehingga **TIDAK memerlukan browser Chromium tambahan**.

- **Ukuran Total Paket**: **~25 MB - 35 MB** (Hemat hingga 90% dibanding PHP Desktop Chromium).
- **Keunggulan**: Jendela aplikasi tampil penuh seperti aplikasi native Windows tanpa address bar/tab browser, hemat RAM, dan cepat.

### Langkah Pengemasan:

1. Jalankan skrip packaging:
   ```bat
   desktop\package-edge-app.bat
   ```
2. Skrip akan membuat folder `desktop\release` berisi aplikasi terkompilasi (`www/`) dan file *launcher* (`Start-App.vbs`).
3. Unduh **PHP 8.2 / 8.3 NTS (Non-Thread Safe) Zip (~20 MB)** dari [https://windows.php.net/download/](https://windows.php.net/download/).
4. Ekstrak isi file zip PHP tersebut ke dalam folder `desktop\release\php\`.
5. Pengguna cukup melakukan klik-ganda pada file `Start-App.vbs` untuk membuka aplikasi secara *offline*.

---

## 📦 Metode 2: PHP Desktop Chrome Runtime (Legacy)

Metode ini menyertakan browser Chromium Embedded Framework (CEF) di dalam rilis.

- **Ukuran Total Paket**: **~250 MB - 350 MB**
- **Langkah**: Jalankan `desktop\package.bat` dan ekstrak runtime PHP Desktop ke `desktop\release`.

---

## 🧹 Git Best Practices

Folder `desktop/release` telah ditambahkan ke `.gitignore` sehingga file kompilasi akhir tidak akan mengotori git commit history repository Anda.
