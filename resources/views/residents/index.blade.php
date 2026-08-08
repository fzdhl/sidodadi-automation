@extends('layouts.app')

@section('title', 'Data Penduduk | ' . config('app.name'))

@section('content')
<div class="container-fluid">
    <div class="page-heading sticky-heading">
        <h1 class="fw-bold">Data Penduduk</h1>
        <p class="muted">Kelola data penduduk untuk lookup NIK dan pengisian dokumen otomatis.</p>
    </div>

    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    @if (session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    <div class="resident-storage-summary">
        <p><strong>Internal resident storage:</strong> {{ $totalResidents }} data tersimpan.</p>
        <p class="muted">Halaman ini menampilkan data internal yang digunakan untuk lookup NIK dan pengisian dokumen otomatis.</p>
    </div>

    <div class="card mt-4 p-3">
        <ul class="nav nav-tabs" id="residentTabs" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="list-tab" data-bs-toggle="tab" data-bs-target="#list-panel" type="button" role="tab" aria-controls="list-panel" aria-selected="true">Daftar Penduduk</button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="manage-tab" data-bs-toggle="tab" data-bs-target="#manage-panel" type="button" role="tab" aria-controls="manage-panel" aria-selected="false">Kelola Data</button>
            </li>
        </ul>

        <div class="tab-content" id="residentTabsContent">
        <div class="tab-pane fade p-3" id="manage-panel" role="tabpanel" aria-labelledby="manage-tab" tabindex="0">
        <h2 class="h4 fw-bold">Ekspor Data</h2>
        <div class="d-flex gap-2 flex-wrap mt-3">
            <a class="button secondary btn btn-success" href="{{ route('residents.template') }}">Unduh template CSV</a>
            <a class="button secondary btn btn-success" href="{{ route('residents.export') }}">Ekspor CSV</a>
            <a class="button secondary btn btn-success" href="{{ route('residents.export.xlsx') }}">Ekspor XLSX</a>
        </div>
        <form method="POST" action="{{ route('residents.import') }}" enctype="multipart/form-data" class="border-top mt-4 pt-3">
            @csrf
            <h2 class="h4 fw-bold">Impor Data</h2>
            <label class="form-label mt-3" for="resident_csv">Pilih file data penduduk</label>
            <input class="form-control" type="file" id="resident_csv" name="resident_csv" accept=".csv,.xlsx">
            <p class="muted">Menerima CSV template aplikasi atau XLSX DPT Sidodadi dengan header DPID, NO_KK, NIK, NAMA_LGKP, TMPT_LHR, TGL_LAHIR, JENIS_KELAMIN, dan ALAMAT.</p>
            <button class="button btn btn-warning" type="submit">Impor Data</button>
        </form>
        </div>

        <div class="tab-pane fade show active p-3" id="list-panel" role="tabpanel" aria-labelledby="list-tab" tabindex="0">
        <div class="d-flex align-items-center justify-content-between gap-3 mb-3">
            <h2 class="fw-bold mb-0">Daftar Penduduk</h2>
            <button class="btn btn-warning fw-bold" type="button" data-bs-toggle="modal" data-bs-target="#residentModal">Tambah Penduduk</button>
        </div>
        <form method="GET" action="{{ route('residents.index') }}" class="border-bottom mb-4 pb-4">
            <div class="row g-3">
                <div>
                    <label class="form-label fw-bold" for="search">Cari NIK atau Nama</label>
                    <div class="position-relative">
                        <input class="form-control" id="search" name="search" value="{{ $search }}" placeholder="Masukkan NIK atau nama..." autocomplete="off">
                        <div id="residentSuggestions" class="list-group position-absolute w-100 shadow-sm" style="display:none; z-index: 1040;"></div>
                    </div>
                </div>
            </div>
        </form>
        @php
            $residentTableColumns = [
                'nik' => 'NIK',
                'nama' => 'Nama',
                'tempat_lahir' => 'Tempat Lahir',
                'tanggal_lahir' => 'Tanggal Lahir',
                'jenis_kelamin' => 'Jenis Kelamin',
                'agama' => 'Agama',
                'status_perkawinan' => 'Status Perkawinan',
                'kewarganegaraan' => 'Kewarganegaraan',
                'pekerjaan' => 'Pekerjaan',
                'alamat' => 'Alamat',
                'jenis_usaha' => 'Jenis Usaha',
                'nama_usaha' => 'Nama Usaha',
                'lama_usaha' => 'Lama Usaha',
                'alamat_usaha' => 'Alamat Usaha',
                'nama_anak' => 'Nama Anak',
                'nik_anak' => 'NIK Anak',
                'nama_sekolah' => 'Nama Sekolah',
            ];
        @endphp
        <div class="table-responsive">
        <table class="table table-bordered align-middle text-nowrap">
            <thead>
                <tr>
                    @foreach ($residentTableColumns as $label)
                        <th>{{ $label }}</th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                @forelse ($residents as $resident)
                    <tr>
                        @foreach ($residentTableColumns as $column => $label)
                            <td>{{ $column === 'tanggal_lahir' ? optional($resident->{$column})->format('Y-m-d') : $resident->{$column} }}</td>
                        @endforeach
                    </tr>
                @empty
                    <tr>
                        <td colspan="{{ count($residentTableColumns) }}">Tidak ada data penduduk yang cocok.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
        </div>
        <div class="mt-3">
            {{ $residents->withQueryString()->links() }}
        </div>
        </div>

        <div class="modal fade" id="residentModal" tabindex="-1" aria-labelledby="residentModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-xl modal-dialog-scrollable">
                <div class="modal-content">
                    <div class="modal-header">
                        <h2 class="modal-title h4 fw-bold" id="residentModalLabel">Tambah / Perbarui Penduduk</h2>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                    </div>
                    <div class="modal-body">
    <form method="POST" action="{{ route('residents.store') }}">
        @csrf
        <div class="row g-3">
            @foreach (array_merge(config('documents.fields.common', []), config('documents.fields.usaha', []), config('documents.fields.tidak-mampu', []), config('documents.fields.keterangan', [])) as $field)
                <div class="{{ $field['type'] === 'textarea' ? 'col-12' : 'col-md-6' }}">
                    <label class="form-label" for="{{ $field['name'] }}">{{ $field['label'] }}</label>
                    @if ($field['type'] === 'textarea')
                        <textarea class="form-control" id="{{ $field['name'] }}" name="{{ $field['name'] }}" placeholder="{{ $field['placeholder'] ?? '' }}">{{ old($field['name']) }}</textarea>
                    @elseif ($field['type'] === 'select')
                        <select class="form-select" id="{{ $field['name'] }}" name="{{ $field['name'] }}">
                            <option value="">Pilih...</option>
                            @foreach ($field['options'] as $option)
                                <option value="{{ $option }}" @selected(old($field['name']) === $option)>{{ $option }}</option>
                            @endforeach
                        </select>
                    @else
                        <input class="form-control" id="{{ $field['name'] }}" type="{{ $field['type'] }}" name="{{ $field['name'] }}" value="{{ old($field['name']) }}" placeholder="{{ $field['placeholder'] ?? '' }}">
                    @endif
                    @error($field['name']) <small class="invalid-feedback d-block">{{ $message }}</small> @enderror
                </div>
            @endforeach
        </div>
        <p><button class="button btn btn-warning" type="submit">Simpan Penduduk</button></p>
    </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
</main>
<script>
    const initResidentSearch = function () {
        const input = document.getElementById('search');
        const suggestions = document.getElementById('residentSuggestions');
        const searchForm = input?.closest('form');
        let timer;

        const hideSuggestions = () => {
            if (suggestions) {
                suggestions.style.display = 'none';
                suggestions.innerHTML = '';
            }
        };

        const renderSuggestions = (items) => {
            if (!suggestions || !items.length) {
                hideSuggestions();
                return;
            }
            suggestions.innerHTML = items.map(item => `
                <button type="button" class="list-group-item list-group-item-action" data-nik="${item.nik}">
                    <strong>${item.nama}</strong><br><small>${item.nik} · ${item.tempat_lahir || ''}</small>
                </button>
            `).join('');
            suggestions.style.display = 'block';
            suggestions.querySelectorAll('[data-nik]').forEach(button => {
                button.addEventListener('click', () => {
                    input.value = button.dataset.nik;
                    searchForm?.submit();
                });
            });
        };

        input?.addEventListener('input', function () {
            window.clearTimeout(timer);
            const query = input.value.trim();
            if (query.length < 2) {
                hideSuggestions();
                return;
            }
            timer = window.setTimeout(async () => {
                const response = await fetch(`{{ route('residents.search') }}?q=${encodeURIComponent(query)}`, {
                    headers: { 'Accept': 'application/json' },
                });
                if (response.ok) {
                    renderSuggestions((await response.json()).data || []);
                }
            }, 250);
        });

        input?.addEventListener('keydown', function (event) {
            if (event.key === 'Enter') {
                event.preventDefault();
                hideSuggestions();
                searchForm?.submit();
            }
        });

        document.addEventListener('click', function (event) {
            if (!suggestions?.contains(event.target) && event.target !== input) {
                hideSuggestions();
            }
        });
    };
    document.addEventListener('DOMContentLoaded', initResidentSearch);
    document.addEventListener('spa:loaded', initResidentSearch);
</script>
@endsection
