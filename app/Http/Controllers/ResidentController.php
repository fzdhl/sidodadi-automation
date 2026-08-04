<?php

namespace App\Http\Controllers;

use App\Models\Resident;
use App\Residents\ResidentImportService;
use App\Residents\ResidentLookupService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;

class ResidentController extends Controller
{
    public function __construct(
        private readonly ResidentLookupService $residents,
        private readonly ResidentImportService $importer,
    )
    {
    }

    public function index(Request $request): View
    {
        $query = $request->string('search')->trim()->toString();
        $residents = $this->residents->paginate($query);
        $totalResidents = \App\Models\Resident::count();

        return view('residents.index', [
            'residents' => $residents,
            'search' => $query,
            'totalResidents' => $totalResidents,
        ]);
    }

    public function lookup(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'nik' => ['required', 'digits:16'],
        ]);

        $resident = $this->residents->findByNik($validated['nik']);
        if ($resident === null) {
            return response()->json(['message' => 'Data tidak ditemukan. Silakan isi data secara manual dan simpan jika perlu.'], 404);
        }

        return response()->json(['data' => $resident]);
    }

    public function searchJson(Request $request): JsonResponse
    {
        $query = $request->string('q')->trim()->toString();
        if ($query === '') {
            return response()->json(['data' => []]);
        }

        $residents = Resident::query()
            ->where(function ($builder) use ($query): void {
                $builder->where('nik', 'like', "%{$query}%")
                    ->orWhere('nama', 'like', "%{$query}%");
            })
            ->orderBy('nama')
            ->limit(8)
            ->get(['nik', 'nama', 'tempat_lahir', 'tanggal_lahir']);

        return response()->json(['data' => $residents]);
    }

    public function store(Request $request): RedirectResponse
    {
        $fields = array_merge(
            config('documents.fields.common', []),
            config('documents.fields.usaha', []),
            config('documents.fields.tidak-mampu', []),
            config('documents.fields.keterangan', []),
        );

        $rules = [
            'nik' => ['required', 'digits:16'],
            'nama' => ['required', 'string', 'max:150'],
        ];

        foreach ($fields as $field) {
            if (in_array($field['name'], ['nik', 'nama'], true)) {
                continue;
            }

            $rules[$field['name']] = array_map(
                static fn ($rule): string => $rule === 'required' ? 'nullable' : $rule,
                $field['rules'] ?? [],
            );
        }

        $data = $request->validate($rules);
        $this->residents->saveResident($data);

        return redirect()->route('residents.index')
            ->with('success', 'Data penduduk berhasil disimpan.');
    }

    public function import(Request $request): RedirectResponse
    {
        $request->validate([
            'resident_csv' => ['required', 'file', 'mimes:csv,txt,xlsx'],
        ]);

        try {
            $summary = $this->importer->import($request->file('resident_csv'));
        } catch (\Throwable $exception) {
            report($exception);

            return redirect()->route('residents.index')
                ->with('error', 'Gagal membaca file impor. Pastikan format dan header file sesuai.');
        }

        $message = "Berhasil mengimpor {$summary['imported']} data penduduk.";
        if ($summary['updated'] > 0) {
            $message .= " {$summary['updated']} data diperbarui.";
        }
        if ($summary['skipped'] > 0) {
            $message .= " {$summary['skipped']} baris dilewati karena NIK kosong atau tidak valid.";
        }

        return redirect()->route('residents.index')
            ->with('success', $message);
    }

    public function template()
    {
        $columns = (new Resident())->getFillable();
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="resident-template.csv"',
        ];

        $csv = implode(',', $columns) . "\n";

        return response($csv, 200, $headers);
    }

    public function export()
    {
        $residents = $this->residents->all();
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="residents.csv"',
        ];

        $columns = (new Resident())->getFillable();

        $callback = function () use ($residents, $columns) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, $columns);
            foreach ($residents as $resident) {
                $row = [];
                foreach ($columns as $column) {
                    $row[] = $resident->{$column};
                }
                fputcsv($handle, $row);
            }
            fclose($handle);
        };

        return response()->stream($callback, 200, $headers);
    }

    public function exportXlsx()
    {
        $columns = (new Resident())->getFillable();
        $residents = $this->residents->all();

        return response()->streamDownload(function () use ($columns, $residents): void {
            $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();
            $sheet->setTitle('Data Penduduk');

            foreach ($columns as $columnIndex => $column) {
                $sheet->setCellValueByColumnAndRow($columnIndex + 1, 1, $column);
            }

            foreach ($residents as $rowIndex => $resident) {
                foreach ($columns as $columnIndex => $column) {
                    $sheet->setCellValueExplicitByColumnAndRow(
                        $columnIndex + 1,
                        $rowIndex + 2,
                        (string) ($resident->{$column} ?? ''),
                        \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING,
                    );
                }
            }

            $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
            $writer->save('php://output');
            $spreadsheet->disconnectWorksheets();
        }, 'residents.xlsx', [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }
}
