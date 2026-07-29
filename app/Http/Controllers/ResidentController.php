<?php

namespace App\Http\Controllers;

use App\Models\Resident;
use App\Residents\ResidentLookupService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;

class ResidentController extends Controller
{
    public function __construct(private readonly ResidentLookupService $residents)
    {
    }

    public function index(Request $request): View
    {
        $query = $request->string('search')->trim()->toString();
        $residents = $this->residents->search($query);

        return view('residents.index', [
            'residents' => $residents,
            'search' => $query,
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
            'resident_csv' => ['required', 'file', 'mimes:csv,txt'],
        ]);

        $file = $request->file('resident_csv');
        $handle = fopen($file->getRealPath(), 'r');
        if ($handle === false) {
            return redirect()->route('residents.index')->with('error', 'Gagal membaca file impor.');
        }

        $columns = [];
        $imported = 0;

        while (($row = fgetcsv($handle)) !== false) {
            if ($columns === []) {
                $columns = array_map('trim', $row);
                continue;
            }

            $row = array_combine($columns, $row);
            if ($row === false || empty($row['nik'])) {
                continue;
            }

            $this->residents->saveResident($row);
            $imported++;
        }

        fclose($handle);

        return redirect()->route('residents.index')
            ->with('success', "Berhasil mengimpor {$imported} data penduduk.");
    }

    public function export(): Response
    {
        $residents = $this->residents->all();
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="residents.csv"',
        ];

        $columns = Resident::getFillable();

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
}
