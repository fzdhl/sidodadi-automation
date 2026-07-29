<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ config('app.name') }}</title>
    <style>
        body { background: #fafbf7; color: #263322; font-family: Arial, sans-serif; margin: 0; }
        .sidebar { background: rgb(0, 63, 29); color: #f8f5e8; height: 100vh; left: 0; padding: 24px 16px; position: fixed; top: 0; width: 220px; z-index: 10; box-sizing: border-box; }
        .sidebar h2 { color: #fff; font-size: 1.05rem; margin: 0 0 28px; }
        .sidebar a { border-radius: 6px; color: #f8f5e8; display: block; margin: 6px 0; padding: 11px 12px; text-decoration: none; }
        .sidebar a:hover, .sidebar a.active { background: #405829; color: #fff; }
        .page-content { margin-left: 220px; min-height: 100vh; }
        .container { max-width: 980px; margin: 0 auto; padding: 32px 20px; }
        .card { background: #fff; border: 1px solid #dce5d2; border-radius: 8px; padding: 24px; margin-top: 20px; }
        .grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 16px; }
        .full { grid-column: 1 / -1; }
        label { display: block; font-weight: 600; margin-bottom: 6px; }
        input, select, textarea { border: 1px solid #c5d2b8; border-radius: 5px; box-sizing: border-box; padding: 10px; width: 100%; }
        textarea { min-height: 90px; resize: vertical; }
        .button { display: inline-block;background: #FEC51E; border: 0; border-radius: 5px; color: #3d5226; cursor: pointer; font-weight: 600; padding: 11px 18px; }
        .button.secondary { background: rgb(0, 63, 29); color: #fff; }
        .alert { border-radius: 5px; margin: 16px 0; padding: 12px 16px; }
        .success { background: #dcfce7; color: #166534; }
        .errors { background: #fee2e2; color: #991b1b; }
        .field-error { color: #991b1b; display: none; font-size: 0.95rem; margin-top: 8px; }
        .muted { color: #64745d; }
        .highlighted { animation: highlight-pulse 3s ease-out; border-color: #f59e0b; }
        @keyframes highlight-pulse {
        0% { 
            box-shadow: 0 0 0 4px rgba(251, 191, 36, 0.35); 
            background: rgba(251, 191, 36, 0.35);
        }
        33% { 
            box-shadow: 0 0 0 0px rgba(251, 191, 36, 0.35); 
            background: rgba(251, 191, 36, 0.35);
        }
        67% { 
            box-shadow: 0 0 0 0px rgba(251, 191, 36, 0); 
            background: rgba(251, 191, 36, 0);
        }
        100% { 
            box-shadow: 0 0 0 0px rgba(251, 191, 36, 0); 
            background: none;
        }
        }
        @media (max-width: 680px) { .sidebar { height: auto; position: static; width: 100%; } .sidebar h2 { margin-bottom: 12px; } .sidebar a { display: inline-block; } .page-content { margin-left: 0; } .grid { grid-template-columns: 1fr; } .full { grid-column: auto; } }
    </style>
</head>
<body>
<nav class="sidebar" aria-label="Navigasi utama">
    <h2>Sidodadi</h2>
    <a class="active" href="{{ route('documents.create') }}">Buat Dokumen</a>
    <a href="{{ route('residents.index') }}">Data Penduduk</a>
</nav>
<main class="page-content">
<div class="container">
    <h1>Sidodadi Document Generator</h1>
    <p class="muted">Pilih jenis surat, lalu isi data pemohon.</p>
    <p><a class="button secondary" href="{{ route('residents.index') }}">Lihat data penduduk internal</a></p>

    @if (session('success'))
        <div class="alert success">{{ session('success') }}</div>
    @endif

    @if (session('error'))
        <div class="alert errors">
            <strong>Terjadi kesalahan:</strong>
            <p>{{ session('error') }}</p>
        </div>
    @endif

    @if ($errors->any())
        <div class="alert errors">
            <strong>Periksa kembali data berikut:</strong>
            <ul>
                @foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach
            </ul>
        </div>
    @endif

    <form method="GET" action="{{ route('documents.create') }}" class="card">
        <label for="type">Jenis dokumen</label>
        <select id="type" name="type" onchange="this.form.submit()">
            @foreach ($templates as $template)
                <option value="{{ $template->type }}" @selected($template->type === $selectedType)>{{ $template->name }}</option>
            @endforeach
        </select>
    </form>

    <form method="POST" action="{{ route('documents.store') }}" class="card">
        @csrf
        <input type="hidden" name="document_type" value="{{ $selectedType }}">
        <div class="grid">
            @foreach ($fields as $field)
                <div class="{{ $field['type'] === 'textarea' ? 'full' : '' }}">
                    <label for="{{ $field['name'] }}">{{ $field['label'] }}</label>
                    @if ($field['name'] === 'nik')
                        <div style="display:flex; gap:10px; align-items:flex-end;">
                            <input id="{{ $field['name'] }}" type="{{ $field['type'] }}" name="{{ $field['name'] }}" value="{{ old($field['name']) }}" placeholder="{{ $field['placeholder'] ?? '' }}" style="flex:1;">
                            <button id="lookup_nik_button" type="button" class="button secondary">Lookup NIK</button>
                        </div>
                        <div id="lookupMessage" class="muted" style="margin-top:8px;"></div>
                    @elseif ($field['type'] === 'textarea')
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
                    @error($field['name']) <small class="errors field-error" style="display: block;">{{ $message }}</small> @else <small class="errors field-error" id="{{ $field['name'] }}_error"></small> @enderror
                </div>
            @endforeach
        </div>

        <div class="card" style="margin-top: 16px;">
            <label>
                <input type="checkbox" name="save_as_resident" value="1" @checked(old('save_as_resident'))>
                Simpan data penduduk ini ke database resident saat dokumen dibuat
            </label>
        </div>

        <div id="documentStatusCard" class="card" style="margin-top: 16px; display: none;">
            <div id="documentStatusMessage" class="muted"></div>
            <div id="documentDownloadLink"></div>
        </div>

        <script>
            document.addEventListener('DOMContentLoaded', function () {
                const lookupButton = document.getElementById('lookup_nik_button');
                const nikInput = document.getElementById('nik');
                const lookupMessage = document.getElementById('lookupMessage');
                const documentStatusCard = document.getElementById('documentStatusCard');
                const documentStatusMessage = document.getElementById('documentStatusMessage');
                const documentDownloadLink = document.getElementById('documentDownloadLink');
                const documentForm = document.querySelector('form[action="{{ route('documents.store') }}"]');
                const fieldNames = @json(array_column($fields, 'name'));
                const highlightDelay = 2000;

                const showStatusCard = () => {
                    if (documentStatusCard) {
                        documentStatusCard.style.display = 'block';
                    }
                };

                const hideStatusCard = () => {
                    if (documentStatusCard) {
                        documentStatusCard.style.display = 'none';
                    }
                };

                const setStatusMessage = (message) => {
                    if (documentStatusMessage) {
                        documentStatusMessage.textContent = message;
                    }
                    if (message) {
                        showStatusCard();
                    }
                };

                const setDownloadLink = (html) => {
                    if (documentDownloadLink) {
                        documentDownloadLink.innerHTML = html;
                    }
                    if (html) {
                        showStatusCard();
                    }
                };

                const applyAutoFillHighlight = (element) => {
                    if (! element) {
                        return;
                    }
                    element.classList.add('highlighted');
                    window.setTimeout(() => element.classList.remove('highlighted'), highlightDelay);
                };

                lookupButton?.addEventListener('click', async function () {
                    const nik = nikInput?.value.trim();

                    lookupMessage.textContent = '';
                    if (! nik || nik.length !== 16) {
                        lookupMessage.textContent = 'Masukkan NIK 16 digit sebelum melakukan lookup.';
                        return;
                    }

                    lookupButton.disabled = true;
                    lookupButton.textContent = 'Mencari...';

                    try {
                        const response = await fetch('{{ route('residents.lookup') }}', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                            },
                            body: JSON.stringify({ nik }),
                        });

                        if (! response.ok) {
                            const payload = await response.json();
                            lookupMessage.textContent = payload.message || 'Data tidak ditemukan.';
                            return;
                        }

                        const payload = await response.json();
                        const data = payload.data || {};

                        fieldNames.forEach(name => {
                            const element = document.getElementById(name);
                            if (! element || typeof data[name] === 'undefined') {
                                return;
                            }

                            let value = data[name] ?? '';
                            if (element.type === 'date' && value) {
                                const match = value.match(/^(\d{4}-\d{2}-\d{2})/);
                                if (match) {
                                    value = match[1];
                                }
                            }

                            if (element.tagName === 'INPUT' || element.tagName === 'TEXTAREA' || element.tagName === 'SELECT') {
                                element.value = value;
                                if (value) {
                                    applyAutoFillHighlight(element);
                                }
                            }
                        });

                        lookupMessage.textContent = 'Data penduduk ditemukan dan diisi otomatis. Pastikan data sudah benar sebelum menyimpan.';
                    } catch (error) {
                        lookupMessage.textContent = 'Terjadi kesalahan saat melakukan lookup. Coba lagi.';
                    } finally {
                        lookupButton.disabled = false;
                        lookupButton.textContent = 'Lookup NIK';
                    }
                });

                documentForm?.addEventListener('submit', async function (event) {
                    event.preventDefault();
                    setStatusMessage('Membuat dokumen...');
                    setDownloadLink('');
                    clearFieldErrors();

                    const formData = new FormData(documentForm);
                    const response = await fetch(documentForm.action, {
                        method: 'POST',
                        headers: {
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        },
                        body: formData,
                    });

                    const payload = await response.json();
                    if (! response.ok) {
                        const reference = payload.reference_id ? ` Kode referensi error: ${payload.reference_id}` : '';
                        setStatusMessage(`${payload.message || 'Gagal membuat dokumen. Coba lagi.'}${reference}`);
                        if (payload.errors) {
                            displayFieldErrors(payload.errors);
                        }
                        return;
                    }

                    setStatusMessage(payload.message);
                    if (payload.download_url) {
                        setDownloadLink(`<a class="button" href="${payload.download_url}" target="_blank">Unduh dokumen</a>`);
                    }
                });

                const clearFieldErrors = () => {
                    fieldNames.forEach(name => {
                        const errorElement = document.getElementById(`${name}_error`);
                        if (errorElement) {
                            errorElement.textContent = '';
                            errorElement.style.display = 'none';
                        }
                    });
                };

                const displayFieldErrors = (errors) => {
                    Object.entries(errors).forEach(([name, messages]) => {
                        const errorElement = document.getElementById(`${name}_error`);
                        if (errorElement && Array.isArray(messages) && messages.length) {
                            errorElement.textContent = messages[0];
                            errorElement.style.display = 'block';
                        }
                    });
                };
            });
        </script>
        <p><button class="button" type="submit">Simpan data pemohon</button></p>
    </form>
</div>
</main>
</body>
</html>
