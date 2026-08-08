# Windows Desktop Packaging Guide - Sidodadi Document Generator

Dokumen ini menjelaskan cara memaketkan aplikasi Laravel ini menjadi **Single-File Portable EXE (`Sidodadi-Generator.exe`)** yang sangat cocok untuk perangkat desa.

---

## 🌟 Metode Utama: Single-File Portable EXE (`Sidodadi-Generator.exe`)

Metode ini membungkus seluruh aplikasi (PHP Portable + Aplikasi Laravel + SQLite + Launcher) ke dalam **1 file `.exe` tunggal**.

- **Ukuran File**: **~30 - 35 MB**
- **Keunggulan**: Perangkat desa tidak perlu melakukan instalasi dan tidak bingung dengan banyak file/folder. Cukup simpan file `Sidodadi-Generator.exe` di Flashdisk atau Desktop dan klik dua kali!

### Langkah Pembuatan File `.exe`:

1. Pastikan folder `desktop\release\php` sudah berisi PHP Portable.
2. Jalankan skrip pembentuk `.exe`:
   ```cmd
   desktop\make-portable-exe.bat
   ```
3. Skrip akan menghasilkan file eksekusi tunggal:
   **`desktop\Sidodadi-Generator.exe`**

---

## 🚀 Metode Folder Rilis: Edge App Mode

Jika Anda ingin mendistribusikan folder rilis biasa:

1. Jalankan skrip packaging:
   ```cmd
   desktop\package-edge-app.bat
   ```
2. Pengguna membuka aplikasi dengan klik-ganda pada `Start-App.vbs`.

---

## 🧹 Git Best Practices

Folder `desktop/release` dan file `.exe` rilis telah ditambahkan ke `.gitignore` sehingga tidak mengotori riwayat commit Git Anda.
