Exit code: 0
Wall time: 0.8 seconds
Output:
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ config('app.name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>
<nav class="sidebar position-fixed top-0 start-0 vh-100 p-3" aria-label="Navigasi utama">
    <h2 class="mb-4">Sidodadi</h2>
    <a class="active d-block my-1 px-3 py-2 rounded text-decoration-none" href="{{ route('documents.create') }}">Buat Dokumen</a>
    <a class="d-block my-1 px-3 py-2 rounded text-decoration-none" href="{{ route('residents.index') }}">Data Penduduk</a>
</nav>
<main class="page-content">
<div class="container-fluid">
    <h1>Sidodadi Document Generator</h1>
    <p class="muted">Pilih jenis surat, lalu isi data pemohon.</p>
    <p><a class="button secondary btn btn-success" href="{{ route('residents.index') }}">Lihat data penduduk internal</a></p>

    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    @if (session('error'))
        <div class="alert alert-danger">
            <strong>Terjadi kesalahan:</strong>
            <p>{{ session('error') }}</p>
        </div>
    @endif

    @if ($errors->any())
        <div class="alert alert-danger">
            <strong>Periksa kembali data berikut:</strong>
            <ul>
                @foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach
            </ul>
        </div>
    @endif

    <form method="GET" action="{{ route('documents.create') }}" class="card mt-4 p-4">
        <label class="form-label" for="type">Jenis dokumen</label>
        <select class="form-select" id="type" name="type" onchange="this.form.submit()">
            @foreach ($templates as $template)
                <option value="{{ $template->type }}" @selected($template->type === $selectedType)>{{ $template->name }}</option>
            @endforeach
        </select>
    </form>

    <form method="POST" action="{{ route('documents.store') }}" class="card mt-4 p-4">
        @csrf
        <input type="hidden" name="document_type" value="{{ $selectedType }}">
        <div class="row g-3">
            @foreach ($fields as $field)
                <div class="{{ $field['type'] === 'textarea' ? 'col-12' : 'col-md-6' }}">
                    <label class="form-label" for="{{ $field['name'] }}">{{ $field['label'] }}</label>
                    @if ($field['name'] === 'nik')
                        <div class="d-flex align-items-end gap-2">
                            <input class="form-control flex-grow-1" id="{{ $field['name'] }}" type="{{ $field['type'] }}" name="{{ $field['name'] }}" value="{{ old($field['name']) }}" placeholder="{{ $field['placeholder'] ?? '' }}">
                            <button id="lookup_nik_button" type="button" class="button secondary btn btn-success flex-shrink-0">Lookup NIK</button>
                        </div>
                        <div id="lookupMessage" class="muted form-text mt-2"></div>
                    @elseif ($field['type'] === 'textarea')
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
                    @error($field['name']) <small class="invalid-feedback d-block">{{ $message }}</small> @else <small class="invalid-feedback field-error" id="{{ $field['name'] }}_error"></small> @enderror
                </div>
            @endforeach
        </div>

        <div class="card mt-3 p-3">
            <div class="form-check">
                <input class="form-check-input" type="checkbox" name="save_as_resident" value="1" @checked(old('save_as_resident'))>
                <label class="form-check-label">
                Simpan data penduduk ini ke database resident saat dokumen dibuat
                </label>
            </div>
        </div>

        <div id="documentStatusCard" class="card mt-3 p-3" style="display: none;">
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
        <p><button class="button btn btn-warning" type="submit">Simpan data pemohon</button></p>
    </form>
</div>
</main>
</body>
</html>
