<?php

namespace App\Residents;

use App\Models\Resident;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

final class ResidentLookupService
{
    public function findByNik(string $nik): ?Resident
    {
        return Resident::where('nik', $nik)->first();
    }

    public function search(string $query): Collection
    {
        if ($query === '') {
            return $this->all();
        }

        return Resident::query()
            ->where('nik', 'like', "%{$query}%")
            ->orWhere('nama', 'like', "%{$query}%")
            ->orderBy('nama')
            ->get();
    }

    public function all(): Collection
    {
        return Resident::orderBy('nama')->get();
    }

    public function paginate(string $query, int $perPage = 25): LengthAwarePaginator
    {
        return Resident::query()
            ->when($query !== '', function ($builder) use ($query): void {
                $builder->where(function ($search) use ($query): void {
                    $search->where('nik', 'like', "%{$query}%")
                        ->orWhere('nama', 'like', "%{$query}%");
                });
            })
            ->orderBy('nama')
            ->paginate($perPage);
    }

    public function saveResident(array $data): Resident
    {
        $normalized = array_merge(
            ['nik' => $data['nik'] ?? ''],
            array_filter($data, static fn ($value): bool => $value !== null && $value !== ''),
        );

        if (isset($normalized['tanggal_lahir'])) {
            try {
                $normalized['tanggal_lahir'] = Carbon::parse($normalized['tanggal_lahir'])->format('Y-m-d');
            } catch (\Throwable) {
                unset($normalized['tanggal_lahir']);
            }
        }

        return Resident::updateOrCreate(
            ['nik' => $normalized['nik']],
            $normalized,
        );
    }
}
