<?php

namespace App\Http\Controllers;

use App\Documents\DocumentGenerator;
use App\Documents\DocumentTemplateRegistry;
use App\Http\Requests\DocumentInputRequest;
use App\Residents\ResidentLookupService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class DocumentController extends Controller
{
    public function __construct(
        private readonly DocumentTemplateRegistry $templates,
        private readonly DocumentGenerator $generator,
        private readonly ResidentLookupService $residents,
    ) {
    }

    public function create(Request $request): View
    {
        $selectedType = $request->string('type')->toString();
        $selectedType = array_key_exists($selectedType, config('documents.types', []))
            ? $selectedType
            : array_key_first(config('documents.types', []));

        return view('documents.create', [
            'templates' => $this->templates->all(),
            'selectedType' => $selectedType,
            'fields' => $this->fieldsFor($selectedType),
        ]);
    }

    public function store(DocumentInputRequest $request): BinaryFileResponse|RedirectResponse|JsonResponse
    {
        $validated = $request->validated();
        $type = $validated['document_type'];
        $saveAsResident = (bool) ($validated['save_as_resident'] ?? false);

        unset($validated['document_type'], $validated['save_as_resident']);

        if ($saveAsResident) {
            $this->residents->saveResident($validated);
        }

        $disk = config('documents.output_disk', 'local');
        $directory = config('documents.output_directory', 'generated-documents');
        $filename = $type.'-'.Str::lower((string) Str::uuid()).'.docx';
        $relativePath = $directory.'/'.$filename;
        $outputPath = Storage::disk($disk)->path($relativePath);

        try {
            $this->generator->generate($type, $validated, $outputPath);
        } catch (\Throwable $exception) {
            $referenceId = 'DOCGEN-'.Str::upper(Str::substr((string) Str::uuid(), 0, 8));
            $friendlyMessage = 'Terjadi kesalahan saat membuat dokumen. Silakan coba lagi.';

            Log::error('Document generation failed', [
                'reference_id' => $referenceId,
                'document_type' => $type,
                'output_path' => $outputPath,
                'exception' => $exception->getMessage(),
                'exception_class' => class_basename($exception),
                'trace' => $exception->getTraceAsString(),
            ]);

            report($exception);

            if ($request->expectsJson()) {
                $response = [
                    'message' => $friendlyMessage,
                    'reference_id' => $referenceId,
                ];

                if (config('app.debug')) {
                    $response['developer_message'] = $exception->getMessage();
                }

                return response()->json($response, 500);
            }

            return redirect()
                ->back()
                ->withInput()
                ->with('error', sprintf('%s Kode: %s', $friendlyMessage, $referenceId));
        }

        if ($request->expectsJson()) {
            return response()->json([
                'message' => $saveAsResident
                    ? 'Dokumen berhasil dibuat dan data resident disimpan. Klik link unduhan di bawah.'
                    : 'Dokumen berhasil dibuat. Klik link unduhan di bawah.',
                'download_url' => route('documents.download', ['filename' => $filename]),
            ]);
        }

        return response()->download(
            $outputPath,
            $filename,
            ['Content-Type' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'],
        )->deleteFileAfterSend(true);
    }

    public function download(string $filename): BinaryFileResponse|RedirectResponse
    {
        $directory = config('documents.output_directory', 'generated-documents');
        $relativePath = $directory.'/'.$filename;

        if (! Storage::disk('local')->exists($relativePath)) {
            return redirect()->route('documents.create')
                ->with('error', 'File dokumen tidak ditemukan. Silakan buat ulang dokumen.');
        }

        $path = Storage::disk('local')->path($relativePath);

        return response()->download(
            $path,
            $filename,
            ['Content-Type' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'],
        );
    }

    private function fieldsFor(string $type): array
    {
        return array_merge(
            config('documents.fields.common', []),
            config("documents.fields.{$type}", []),
        );
    }
}