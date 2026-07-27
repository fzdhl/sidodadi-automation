<?php

namespace App\Http\Controllers;

use App\Documents\DocumentGenerator;
use App\Documents\DocumentTemplateRegistry;
use App\Http\Requests\DocumentInputRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class DocumentController extends Controller
{
    public function __construct(
        private readonly DocumentTemplateRegistry $templates,
        private readonly DocumentGenerator $generator,
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

    public function store(DocumentInputRequest $request): BinaryFileResponse
    {
        $validated = $request->validated();
        $type = $validated['document_type'];
        unset($validated['document_type']);

        // Support templates that use the combined TTL placeholder.
        $validated['ttl'] = $validated['tempat_lahir'].', '.$validated['tanggal_lahir'];

        $disk = config('documents.output_disk', 'local');
        $directory = config('documents.output_directory', 'generated-documents');
        $filename = $type.'-'.Str::lower((string) Str::uuid()).'.docx';
        $relativePath = $directory.'/'.$filename;
        $outputPath = Storage::disk($disk)->path($relativePath);

        $this->generator->generate($type, $validated, $outputPath);

        return response()->download(
            $outputPath,
            $filename,
            ['Content-Type' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'],
        )->deleteFileAfterSend(true);
    }

    private function fieldsFor(string $type): array
    {
        return array_merge(
            config('documents.fields.common', []),
            config("documents.fields.{$type}", []),
        );
    }
}