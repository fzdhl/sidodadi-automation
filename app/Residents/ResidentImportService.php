<?php

namespace App\Residents;

use App\Models\Resident;
use Carbon\Carbon;
use DateTimeInterface;
use Illuminate\Http\UploadedFile;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;
use RuntimeException;

final class ResidentImportService
{
    public function __construct(private readonly ResidentLookupService $residents)
    {
    }

    public function import(UploadedFile $file): array
    {
        return strtolower($file->getClientOriginalExtension()) === 'xlsx'
            ? $this->importXlsx($file->getRealPath())
            : $this->importCsv($file->getRealPath());
    }

    private function importXlsx(string $path): array
    {
        $reader = IOFactory::createReader('Xlsx');
        $reader->setReadDataOnly(true);
        $workbook = $reader->load($path);
        $sheet = $workbook->getSheetByName('URUT NAMA');

        if ($sheet === null) {
            throw new RuntimeException('Sheet URUT NAMA tidak ditemukan.');
        }

        $rows = $sheet->toArray(null, true, true, false);
        $headers = $this->normalizeHeaders(array_shift($rows) ?: []);
        $this->assertDptHeaders($headers);

        return $this->importRows($headers, $rows, true);
    }

    private function importCsv(string $path): array
    {
        $handle = fopen($path, 'r');
        if ($handle === false) {
            throw new RuntimeException('File CSV tidak dapat dibaca.');
        }

        try {
            $row = fgetcsv($handle);
            if ($row === false) {
                throw new RuntimeException('File CSV tidak memiliki header.');
            }

            $headers = array_map('strtolower', $this->normalizeHeaders($row));
            if (! in_array('nik', $headers, true)) {
                throw new RuntimeException('Header CSV harus memuat kolom nik.');
            }

            $rows = [];
            while (($row = fgetcsv($handle)) !== false) {
                $rows[] = $row;
            }

            return $this->importRows($headers, $rows, false);
        } finally {
            fclose($handle);
        }
    }

    private function importRows(array $headers, array $rows, bool $dpt): array
    {
        $summary = ['imported' => 0, 'updated' => 0, 'skipped' => 0];
        $records = [];

        foreach ($rows as $row) {
            $row = array_slice(array_pad($row, count($headers), null), 0, count($headers));
            $data = array_combine($headers, $row);
            if ($data === false) {
                $summary['skipped']++;
                continue;
            }

            $normalized = $dpt ? $this->mapDptRow($data) : $this->mapCsvRow($data);
            $nik = trim((string) ($normalized['nik'] ?? ''));
            if (! preg_match('/^\d{16}$/', $nik)) {
                $summary['skipped']++;
                continue;
            }

            $normalized['nik'] = $nik;
            $records[$nik] = $normalized;
        }

        foreach (array_chunk(array_values($records), 500) as $batch) {
            $niks = array_column($batch, 'nik');
            $existingNiks = array_fill_keys(
                Resident::whereIn('nik', $niks)->pluck('nik')->all(),
                true,
            );
            $columns = array_values(array_unique(array_merge(...array_map('array_keys', $batch))));
            $columns = array_values(array_diff($columns, ['nik']));
            $timestamp = now();

            foreach ($batch as &$record) {
                $record['created_at'] = $timestamp;
                $record['updated_at'] = $timestamp;
                foreach ($columns as $column) {
                    $record[$column] ??= null;
                }
            }
            unset($record);

            Resident::upsert(
                $batch,
                ['nik'],
                array_values(array_unique(array_merge($columns, ['updated_at']))),
            );

            foreach ($niks as $nik) {
                $existingNiks[$nik] ?? false
                    ? $summary['updated']++
                    : $summary['imported']++;
            }
        }

        return $summary;
    }

    private function mapDptRow(array $row): array
    {
        $address = array_filter([
            $this->value($row, 'ALAMAT'),
            $this->value($row, 'NO_RT') ? 'RT '.$this->value($row, 'NO_RT') : null,
            $this->value($row, 'NO_RW') ? 'RW '.$this->value($row, 'NO_RW') : null,
            $this->value($row, 'DESA/KELURAHAN'),
        ]);
        $gender = strtoupper((string) $this->value($row, 'JENIS_KELAMIN'));

        return array_filter([
            'nik' => $this->value($row, 'NIK'),
            'nama' => $this->value($row, 'NAMA_LGKP'),
            'tempat_lahir' => $this->value($row, 'TMPT_LHR'),
            'tanggal_lahir' => $this->normalizeDate($row['TGL_LAHIR'] ?? null),
            'jenis_kelamin' => ['L' => 'Laki-laki', 'P' => 'Perempuan'][$gender] ?? null,
            'alamat' => implode(', ', $address),
        ], static fn ($value): bool => $value !== null && $value !== '');
    }

    private function mapCsvRow(array $row): array
    {
        $fillables = array_map('strtolower', (new Resident())->getFillable());
        return array_filter(
            array_intersect_key($row, array_flip($fillables)),
            static fn ($value): bool => $value !== null && trim((string) $value) !== '',
        );
    }

    private function normalizeHeaders(array $headers): array
    {
        return array_map(static fn ($header): string => strtoupper(trim((string) $header)), $headers);
    }

    private function assertDptHeaders(array $headers): void
    {
        foreach (['NIK', 'NAMA_LGKP', 'TMPT_LHR', 'TGL_LAHIR', 'JENIS_KELAMIN', 'ALAMAT'] as $required) {
            if (! in_array($required, $headers, true)) {
                throw new RuntimeException("Header XLSX wajib memuat kolom {$required}.");
            }
        }
    }

    private function value(array $row, string $key): ?string
    {
        $value = $row[$key] ?? null;
        return $value === null ? null : trim((string) $value);
    }

    private function normalizeDate(mixed $value): ?string
    {
        if ($value instanceof DateTimeInterface) {
            return $value->format('Y-m-d');
        }
        if (is_numeric($value)) {
            try {
                return ExcelDate::excelToDateTimeObject((float) $value)->format('Y-m-d');
            } catch (\Throwable) {
                return null;
            }
        }
        $value = trim((string) $value);
        if ($value === '') {
            return null;
        }
        foreach (['d\|m\|Y', 'd/m/Y', 'Y-m-d'] as $format) {
            try {
                return Carbon::createFromFormat($format, $value)->format('Y-m-d');
            } catch (\Throwable) {
            }
        }
        try {
            return Carbon::parse($value)->format('Y-m-d');
        } catch (\Throwable) {
            return null;
        }
    }
}
