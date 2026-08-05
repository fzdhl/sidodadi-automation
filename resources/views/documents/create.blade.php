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
    <h2 class="mb-4 fw-bolder">SIDODADI</h2>
    <a class="active d-block my-1 px-3 py-2 rounded text-decoration-none" href="{{ route('documents.create') }}">Buat Dokumen</a>
    <a class="d-block my-1 px-3 py-2 rounded text-decoration-none" href="{{ route('residents.index') }}">Data Penduduk</a>
</nav>
<main class="page-content">
<div class="container-fluid">
    <div class="page-heading sticky-heading">
        <h1 class="fw-bold">Buat Dokumen</h1>
        <p class="muted">Pilih jenis surat, lalu isi data pemohon.</p>
    </div>

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

        <div class="document-editor-layout">
            <div class="document-editor-main">
                <div class="row g-3">
                    @foreach ($fields as $field)
                        @php
                            $fieldValue = old($field['name']);
                            if ($field['name'] === 'nomor_surat' && !$fieldValue) {
                                $fieldValue = '138/ ___ /35.07.25.2003/2026';
                            }
                        @endphp
                        <div class="col-12">
                            <label class="form-label" for="{{ $field['name'] }}">{{ $field['label'] }}</label>
                            @if ($field['name'] === 'nik')
                                <div class="d-flex align-items-end gap-2">
                                    <input class="form-control flex-grow-1" id="{{ $field['name'] }}" type="{{ $field['type'] }}" name="{{ $field['name'] }}" value="{{ $fieldValue }}" placeholder="{{ $field['placeholder'] ?? '' }}">
                                    <button id="lookup_nik_button" type="button" class="button secondary btn btn-success flex-shrink-0">Lookup NIK</button>
                                </div>
                                <div id="lookupMessage" class="muted form-text mt-2"></div>
                            @elseif (str_ends_with($field['name'], '_nik'))
                                @php($lookupPrefix = str($field['name'])->beforeLast('_nik')->toString())
                                <div class="d-flex align-items-end gap-2">
                                    <input class="form-control flex-grow-1" id="{{ $field['name'] }}" type="{{ $field['type'] }}" name="{{ $field['name'] }}" value="{{ $fieldValue }}" placeholder="{{ $field['placeholder'] ?? '' }}">
                                    <button type="button" class="button secondary btn btn-success flex-shrink-0 party-lookup-button" data-prefix="{{ $lookupPrefix }}" data-nik-field="{{ $field['name'] }}">Lookup NIK</button>
                                </div>
                                <div id="{{ $field['name'] }}_message" class="muted form-text mt-2"></div>
                            @elseif ($field['type'] === 'textarea')
                                <textarea class="form-control" id="{{ $field['name'] }}" name="{{ $field['name'] }}" placeholder="{{ $field['placeholder'] ?? '' }}">{{ $fieldValue }}</textarea>
                            @elseif ($field['type'] === 'select')
                                <select class="form-select" id="{{ $field['name'] }}" name="{{ $field['name'] }}">
                                    <option value="">Pilih...</option>
                                    @foreach ($field['options'] as $option)
                                        <option value="{{ $option }}" @selected($fieldValue === $option)>{{ $option }}</option>
                                    @endforeach
                                </select>
                            @else
                                <input class="form-control" id="{{ $field['name'] }}" type="{{ $field['type'] }}" name="{{ $field['name'] }}" value="{{ $fieldValue }}" placeholder="{{ $field['placeholder'] ?? '' }}">
                            @endif
                            @error($field['name']) <small class="invalid-feedback d-block">{{ $message }}</small> @else <small class="invalid-feedback field-error" id="{{ $field['name'] }}_error"></small> @enderror
                        </div>
                    @endforeach
                </div>

                <div class="card mt-3 p-3">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="save_as_resident" value="1" @checked(old('save_as_resident'))>
                        <label class="form-check-label">
                        Simpan data penduduk ini ke database saat  residentdokumen dibuat
                        </label>
                    </div>
                </div>

                <div id="documentStatusCard" class="card mt-3 p-3" style="display: none;">
                    <div id="documentStatusMessage" class="muted"></div>
                    <div id="documentDownloadLink"></div>
                </div>

                <p><button class="button btn btn-warning" type="submit">Simpan data pemohon</button></p>
            </div>

            <aside class="document-preview-panel">
                <div id="documentPreviewCard" class="card p-3">
                    <h2 class="fw-bold mb-3">Preview Dokumen</h2>
                    <div id="documentPreviewMessage" class="muted mb-3">Preview dokumen akan diperbarui otomatis saat Anda mengisi formulir.</div>
                    <div id="documentPreviewContent" class="preview-content"></div>
                </div>
            </aside>
        </div>
    </form>

    <script>
            document.addEventListener('DOMContentLoaded', function () {
                const stickyHeading = document.querySelector('.sticky-heading');
                const lookupButton = document.getElementById('lookup_nik_button');
                const nikInput = document.getElementById('nik');
                const lookupMessage = document.getElementById('lookupMessage');
                const documentStatusCard = document.getElementById('documentStatusCard');
                const documentStatusMessage = document.getElementById('documentStatusMessage');
                const documentDownloadLink = document.getElementById('documentDownloadLink');
                const documentForm = document.querySelector(`form[action="{{ route('documents.store') }}"]`);
                const lookupRoute = "{{ route('residents.lookup') }}";
                const previewRoute = "{{ route('documents.preview') }}";
                const fieldNames = @json(array_column($fields, 'name'));
                const highlightDelay = 2000;

                const getStickyTriggerOffset = () => {
                    return stickyHeading.offsetTop + stickyHeading.offsetHeight;
                };

                let stickyTriggerOffset = getStickyTriggerOffset();

                const updateStickyHeader = () => {
                    if (! stickyHeading) {
                        return;
                    }

                    const shouldStick = window.scrollY >= stickyTriggerOffset;
                    stickyHeading.classList.toggle('scrolled', shouldStick);
                };

                const refreshStickyTriggerOffset = () => {
                    if (! stickyHeading) {
                        return;
                    }

                    const wasScrolled = stickyHeading.classList.contains('scrolled');
                    stickyHeading.classList.remove('scrolled');
                    stickyTriggerOffset = getStickyTriggerOffset();
                    if (wasScrolled) {
                        stickyHeading.classList.add('scrolled');
                    }
                    updateStickyHeader();
                };

                updateStickyHeader();
                window.addEventListener('scroll', updateStickyHeader);
                window.addEventListener('resize', refreshStickyTriggerOffset);

                const formatLookupValue = (element, value) => {
                    if (element?.type === 'date' && value) {
                        const match = value.match(/^(\d{4}-\d{2}-\d{2})/);
                        return match ? match[1] : value;
                    }
                    return value;
                };

                const lookupParty = async (button) => {
                    const prefix = button.dataset.prefix;
                    const nikField = document.getElementById(button.dataset.nikField);
                    const message = document.getElementById(`${button.dataset.nikField}_message`);
                    const nik = nikField?.value.trim();

                    if (!nik || nik.length !== 16) {
                        if (message) message.textContent = 'Masukkan NIK 16 digit.';
                        return;
                    }

                    button.disabled = true;
                    button.textContent = 'Mencari...';
                    try {
                        const response = await fetch(lookupRoute, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                            },
                            body: JSON.stringify({ nik }),
                        });
                        const payload = await response.json();
                        if (!response.ok) {
                            if (message) message.textContent = payload.message || 'Data tidak ditemukan. Isi secara manual.';
                            return;
                        }

                        const data = payload.data || {};
                        fieldNames.forEach(name => {
                            if (!name.startsWith(`${prefix}_`)) return;
                            const residentName = name.substring(prefix.length + 1);
                            const element = document.getElementById(name);
                            if (!element || typeof data[residentName] === 'undefined') return;
                            const value = formatLookupValue(element, data[residentName] ?? '');
                            element.value = value;
                            if (value) applyAutoFillHighlight(element);
                        });
                        if (message) message.textContent = 'Data pihak ditemukan dan diisi otomatis.';
                        debouncePreview();
                    } catch (error) {
                        if (message) message.textContent = 'Lookup gagal. Isi data pihak secara manual.';
                    } finally {
                        button.disabled = false;
                        button.textContent = 'Lookup NIK';
                    }
                };

                document.querySelectorAll('.party-lookup-button').forEach(button => {
                    button.addEventListener('click', () => lookupParty(button));
                });

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
                        const response = await fetch(lookupRoute, {
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
                        debouncePreview();
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

                const previewForm = document.querySelector(`form[action="{{ route('documents.store') }}"]`);
                const previewMessage = document.getElementById('documentPreviewMessage');
                const previewContent = document.getElementById('documentPreviewContent');
                let previewTimeout = null;

                const getPreviewData = () => {
                    const formData = new FormData(previewForm);
                    return formData;
                };

                const setPreviewStatus = (message, isError = false) => {
                    if (!previewMessage) return;
                    previewMessage.textContent = message;
                    previewMessage.classList.toggle('text-danger', isError);
                    previewMessage.classList.toggle('text-muted', !isError);
                };

                const parsePreviewHtml = (rawHtml) => {
                    const parser = new DOMParser();
                    const documentPreview = parser.parseFromString(rawHtml, 'text/html');

                    documentPreview.querySelectorAll('style, link[rel="stylesheet"]').forEach(node => node.remove());
                    documentPreview.querySelectorAll('meta, title').forEach(node => node.remove());

                    documentPreview.querySelectorAll('table').forEach(table => {
                        table.removeAttribute('width');
                        table.style.width = '100%';
                        table.style.maxWidth = '100%';
                        table.style.tableLayout = 'fixed';
                        table.style.whiteSpace = 'normal';
                        table.style.wordBreak = 'break-word';
                        table.style.overflowWrap = 'anywhere';
                        table.style.borderCollapse = 'collapse';
                    });

                    documentPreview.querySelectorAll('colgroup col, th, td').forEach(cell => {
                        cell.removeAttribute('width');
                        if (cell.style) {
                            cell.style.width = 'auto';
                            cell.style.minWidth = '0';
                            cell.style.maxWidth = '100%';
                            cell.style.whiteSpace = 'normal';
                            cell.style.wordBreak = 'break-word';
                            cell.style.overflowWrap = 'anywhere';
                            cell.style.marginLeft = '0';
                            cell.style.marginRight = '0';
                            cell.style.paddingLeft = '0';
                            cell.style.paddingRight = '0';
                        }
                    });

                    documentPreview.querySelectorAll('div, p, span').forEach(element => {
                        if (! element.style) {
                            return;
                        }

                        element.style.minWidth = '0';
                        element.style.maxWidth = '100%';
                        element.style.whiteSpace = 'normal';
                        element.style.wordBreak = 'break-word';
                        element.style.overflowWrap = 'anywhere';
                        element.style.margin = '0';
                        element.style.padding = '0';
                        element.style.textIndent = '0';
                        element.style.left = '';
                        element.style.right = '';
                        element.style.position = '';
                    });

                    documentPreview.querySelectorAll('[style]').forEach(element => {
                        const style = element.style;
                        if (! style) {
                            return;
                        }

                        const allowedStyles = new Set([
                            'font-family',
                            'font-size',
                            'font-style',
                            'font-weight',
                            'text-decoration',
                            'text-align',
                            'color',
                            'background-color',
                            'line-height',
                            'vertical-align',
                            'letter-spacing',
                            'word-spacing',
                        ]);

                        const preserved = [];

                        for (let i = 0; i < style.length; i += 1) {
                            const property = style[i];
                            const value = style.getPropertyValue(property);

                            if (!allowedStyles.has(property)) {
                                continue;
                            }

                            if (property !== 'font-size' && /\b(in|cm|mm|pt|pc)\b/i.test(value)) {
                                continue;
                            }

                            preserved.push(`${property}: ${value}`);
                        }

                        if (preserved.length) {
                            element.setAttribute('style', preserved.join('; '));
                        } else {
                            element.removeAttribute('style');
                        }
                    });

                    return documentPreview.body.innerHTML || '<p>Tidak ada preview tersedia.</p>';
                };

                const renderPreview = async () => {
                    if (!previewForm) {
                        return;
                    }

                    setPreviewStatus('Memuat preview...');
                    if (previewContent) {
                        previewContent.innerHTML = '';
                    }

                    try {
                        const response = await fetch(previewRoute, {
                            method: 'POST',
                            headers: {
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                            },
                            body: getPreviewData(),
                        });

                        const payload = await response.json();
                        if (!response.ok) {
                            setPreviewStatus(payload.message || 'Gagal memuat preview.', true);
                            return;
                        }

                        if (previewContent) {
                            previewContent.innerHTML = parsePreviewHtml(payload.html || '');
                        }
                        setPreviewStatus('Preview dokumen diperbarui.');
                    } catch (error) {
                        setPreviewStatus('Preview gagal dimuat. Coba lagi.', true);
                    }
                };

                const debouncePreview = () => {
                    if (previewTimeout) {
                        clearTimeout(previewTimeout);
                    }
                    previewTimeout = window.setTimeout(renderPreview, 350);
                };

                const attachPreviewListeners = () => {
                    if (!previewForm) return;

                    previewForm.querySelectorAll('input, textarea, select').forEach(element => {
                        element.addEventListener('input', debouncePreview);
                        element.addEventListener('change', debouncePreview);
                    });
                };

                attachPreviewListeners();
                renderPreview();
            });
        </script>
        <p><button class="button btn btn-warning" type="submit">Simpan data pemohon</button></p>
    </form>
</div>
</main>
</body>
</html>
