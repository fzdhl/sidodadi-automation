<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Data Penduduk | {{ config('app.name') }}</title>
    <style>
        body { background: #f4f6f8; color: #1f2937; font-family: Arial, sans-serif; margin: 0; }
        .container { max-width: 980px; margin: 0 auto; padding: 32px 20px; }
        .card { background: #fff; border: 1px solid #dfe3e8; border-radius: 8px; padding: 24px; margin-top: 20px; }
        .grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 16px; }
        .full { grid-column: 1 / -1; }
        label { display: block; font-weight: 600; margin-bottom: 6px; }
        input, select, textarea { border: 1px solid #cbd5e1; border-radius: 5px; box-sizing: border-box; padding: 10px; width: 100%; }
        textarea { min-height: 90px; resize: vertical; }
        .button { background: #2563eb; border: 0; border-radius: 5px; color: #fff; cursor: pointer; font-weight: 600; padding: 11px 18px; }
        .button.secondary { background: #64748b; }
        .alert { border-radius: 5px; margin: 16px 0; padding: 12px 16px; }
        .success { background: #dcfce7; color: #166534; }
        .errors { background: #fee2e2; color: #991b1b; }
        table { border-collapse: collapse; width: 100%; margin-top: 20px; }
        th, td { border: 1px solid #e2e8f0; padding: 10px; text-align: left; }
        th { background: #f8fafc; }
        .muted { color: #64748b; }
        @media (max-width: 680px) { .grid { grid-template-columns: 1fr; } .full { grid-column: auto; } }
    </style>
</head>
<body>
<main class="container">
    <h1>Data Penduduk</h1>
    <p class="muted">Kelola data penduduk untuk lookup NIK dan pengisian dokumen otomatis.</p>

    @if (session('success'))
        <div class="alert success">{{ session('success') }}</div>
    @endif

    @if (session('error'))
        <div class="alert errors">{{ session('error') }}</div>
    @endif

    <div class="card">
        <p><strong>Internal resident storage:</strong> {{ $totalResidents }} data tersimpan.</p>
        <p class="muted">Halaman ini menampilkan data internal yang digunakan untuk lookup NIK dan pengisian dokumen otomatis.</p>
        <div style="display:flex; gap:10px; flex-wrap:wrap; margin-top:16px;">
            <a class="button secondary" href="{{ route('residents.template') }}">Unduh template CSV</a>
            <a class="button secondary" href="{{ route('residents.export') }}">Ekspor CSV</a>
        </div>
    </div>

    <form method="GET" action="{{ route('residents.index') }}" class="card">
        <div class="grid">
            <div>
                <label for="search">Cari NIK atau Nama</label>
                <input id="search" name="search" value="{{ $search }}" placeholder="Masukkan NIK atau nama...">
            </div>
            <div class="full" style="align-self:end;">
                <button class="button" type="submit">Cari</button>
                <a class="button secondary" href="{{ route('residents.index') }}">Reset</a>
                <a class="button secondary" href="{{ route('residents.export') }}">Ekspor CSV</a>
            </div>
        </div>
    </form>

    <div class="card">
        <h2>Daftar Penduduk</h2>
        <table>
            <thead>
                <tr>
                    <th>NIK</th>
                    <th>Nama</th>
                    <th>TTL</th>
                    <th>Jenis Kelamin</th>
                    <th>Alamat</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($residents as $resident)
                    <tr>
                        <td>{{ $resident->nik }}</td>
                        <td>{{ $resident->nama }}</td>
                        <td>{{ $resident->tempat_lahir }}, {{ optional($resident->tanggal_lahir)->format('Y-m-d') }}</td>
                        <td>{{ $resident->jenis_kelamin }}</td>
                        <td>{{ \Illuminate\Support\Str::limit($resident->alamat, 80) }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5">Tidak ada data penduduk yang cocok.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <form method="POST" action="{{ route('residents.import') }}" enctype="multipart/form-data" class="card">
        @csrf
        <label for="resident_csv">Impor data penduduk (CSV)</label>
        <input type="file" id="resident_csv" name="resident_csv" accept=".csv">
        <p class="muted">File CSV harus memiliki header seperti nik,nama,tempat_lahir,tanggal_lahir,jenis_kelamin,agama,status_perkawinan,kewarganegaraan,pekerjaan,alamat,jenis_usaha,nama_usaha,lama_usaha,alamat_usaha,nama_anak,nik_anak,nama_sekolah</p>
        <p><button class="button" type="submit">Impor CSV</button></p>
    </form>

    <form method="POST" action="{{ route('residents.store') }}" class="card">
        @csrf
        <h2>Tambah / Perbarui Penduduk</h2>
        <div class="grid">
            @foreach (array_merge(config('documents.fields.common', []), config('documents.fields.usaha', []), config('documents.fields.tidak-mampu', []), config('documents.fields.keterangan', [])) as $field)
                <div class="{{ $field['type'] === 'textarea' ? 'full' : '' }}">
                    <label for="{{ $field['name'] }}">{{ $field['label'] }}</label>
                    @if ($field['type'] === 'textarea')
                        <textarea id="{{ $field['name'] }}" name="{{ $field['name'] }}" placeholder="{{ $field['placeholder'] ?? '' }}">{{ old($field['name']) }}</textarea>
                    @elseif ($field['type'] === 'select')
                        <select id="{{ $field['name'] }}" name="{{ $field['name'] }}">
                            <option value="">Pilih...</option>
                            @foreach ($field['options'] as $option)
                                <option value="{{ $option }}" @selected(old($field['name']) === $option)>{{ $option }}</option>
                            @endforeach
                        </select>
                    @else
                        <input id="{{ $field['name'] }}" type="{{ $field['type'] }}" name="{{ $field['name'] }}" value="{{ old($field['name']) }}" placeholder="{{ $field['placeholder'] ?? '' }}">
                    @endif
                    @error($field['name']) <small class="errors">{{ $message }}</small> @enderror
                </div>
            @endforeach
        </div>
        <p><button class="button" type="submit">Simpan Penduduk</button></p>
    </form>
</main>
</body>
</html>
