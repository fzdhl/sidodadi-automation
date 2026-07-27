<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ config('app.name') }}</title>
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
        .alert { border-radius: 5px; margin: 16px 0; padding: 12px 16px; }
        .success { background: #dcfce7; color: #166534; }
        .errors { background: #fee2e2; color: #991b1b; }
        .muted { color: #64748b; }
        @media (max-width: 680px) { .grid { grid-template-columns: 1fr; } .full { grid-column: auto; } }
    </style>
</head>
<body>
<main class="container">
    <h1>Sidodadi Document Generator</h1>
    <p class="muted">Pilih jenis surat, lalu isi data pemohon.</p>

    @if (session('success'))
        <div class="alert success">{{ session('success') }}</div>
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
                    @if ($field['type'] === 'textarea')
                        <textarea id="{{ $field['name'] }}" name="{{ $field['name'] }}">{{ old($field['name']) }}</textarea>
                    @elseif ($field['type'] === 'select')
                        <select id="{{ $field['name'] }}" name="{{ $field['name'] }}">
                            <option value="">Pilih...</option>
                            @foreach ($field['options'] as $option)
                                <option value="{{ $option }}" @selected(old($field['name']) === $option)>{{ $option }}</option>
                            @endforeach
                        </select>
                    @else
                        <input id="{{ $field['name'] }}" type="{{ $field['type'] }}" name="{{ $field['name'] }}" value="{{ old($field['name']) }}">
                    @endif
                    @error($field['name']) <small class="errors">{{ $message }}</small> @enderror
                </div>
            @endforeach
        </div>
        <p><button class="button" type="submit">Simpan data pemohon</button></p>
    </form>
</main>
</body>
</html>
