<?php

namespace App\Services;

use App\Models\School;
use App\Models\SchoolImportLog;
use App\Models\User;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Throwable;

class SchoolImportService
{
    private const LIMIT = 10000;

    private const SCHOOL_FIELDS = [
        'sekolah_id',
        'npsn',
        'nama',
        'bentuk_pendidikan',
        'status_sekolah',
        'alamat_jalan',
        'nama_dusun',
        'kecamatan',
        'kabupaten',
        'provinsi',
        'nomor_telepon',
        'akreditasi',
    ];

    public function __construct(private readonly PalangkaRayaBoundaryService $boundary)
    {
    }

    public function preview(array $payload): array
    {
        $sourceRows = $payload['data'] ?? null;

        if (! is_array($sourceRows) || ! array_is_list($sourceRows) || $sourceRows === []) {
            return [
                'rows' => [],
                'errors' => ['Format JSON harus berupa objek dengan properti data yang berisi array sekolah.'],
                'warnings' => [],
                'summary' => ['total' => 0, 'inside' => 0, 'outside' => 0, 'missing' => 0, 'review' => 0],
                'boundary_available' => false,
            ];
        }

        if (count($sourceRows) > self::LIMIT) {
            return [
                'rows' => [],
                'errors' => ['Jumlah data melebihi batas aman '.number_format(self::LIMIT, 0, ',', '.').' sekolah per impor.'],
                'warnings' => [],
                'summary' => ['total' => count($sourceRows), 'inside' => 0, 'outside' => 0, 'missing' => 0, 'review' => 0],
                'boundary_available' => false,
            ];
        }

        $boundaryGeometry = null;

        try {
            $boundaryGeometry = $this->boundary->geometry();
        } catch (Throwable) {
            // Coordinate data remains unverified if the boundary service is unavailable.
        }

        $rows = [];
        $errors = [];
        $warnings = [];
        $seenNpsn = [];
        $counts = ['inside' => 0, 'outside' => 0, 'missing' => 0, 'review' => 0];

        foreach ($sourceRows as $index => $source) {
            $rowNumber = $index + 1;

            if (! is_array($source)) {
                $errors[] = "Baris {$rowNumber}: data sekolah tidak berbentuk objek.";
                continue;
            }

            $npsn = trim((string) ($source['npsn'] ?? ''));
            $name = trim((string) ($source['nama'] ?? ''));
            $education = mb_strtoupper(trim((string) ($source['bentuk_pendidikan'] ?? '')));
            $schoolStatus = mb_strtolower(trim((string) ($source['status_sekolah'] ?? '')));

            if (! preg_match('/^[0-9]{8}$/', $npsn)) {
                $errors[] = "Baris {$rowNumber}: NPSN wajib terdiri dari 8 digit.";
                continue;
            }

            if ($name === '') {
                $errors[] = "Baris {$rowNumber}: nama sekolah wajib diisi.";
                continue;
            }

            if (! in_array($education, ['TK', 'SD', 'SMP'], true)) {
                $errors[] = "Baris {$rowNumber}: bentuk_pendidikan harus TK, SD, atau SMP.";
                continue;
            }

            if (! in_array($schoolStatus, ['negeri', 'swasta'], true)) {
                $errors[] = "Baris {$rowNumber}: status_sekolah harus Negeri atau Swasta.";
                continue;
            }

            if (isset($seenNpsn[$npsn])) {
                $errors[] = "Baris {$rowNumber}: NPSN {$npsn} duplikat dengan baris {$seenNpsn[$npsn]} dalam file yang sama.";
                continue;
            }

            $seenNpsn[$npsn] = $rowNumber;
            [$latitude, $longitude, $coordinateStatus, $coordinateWarning] = $this->normaliseCoordinates(
                $source['lintang'] ?? null,
                $source['bujur'] ?? null,
                $boundaryGeometry,
            );

            $counts[match ($coordinateStatus) {
                School::COORDINATE_PENDING => 'inside',
                School::COORDINATE_OUTSIDE_BOUNDARY => 'outside',
                School::COORDINATE_MISSING => 'missing',
                default => 'review',
            }]++;

            if ($coordinateWarning !== null && $coordinateStatus !== School::COORDINATE_PENDING) {
                $warnings[] = "Baris {$rowNumber} ({$npsn}): {$coordinateWarning}";
            }

            $rows[] = [
                'sekolah_id' => $this->nullableText($source['sekolah_id'] ?? null),
                'npsn' => $npsn,
                'nama' => mb_substr($name, 0, 255),
                'bentuk_pendidikan' => $education,
                'status_sekolah' => ucfirst($schoolStatus),
                'alamat_jalan' => $this->nullableText($source['alamat_jalan'] ?? null),
                'nama_dusun' => $this->nullableText($source['nama_dusun'] ?? null),
                'kecamatan' => $this->nullableText($source['kecamatan'] ?? null),
                'kabupaten' => $this->nullableText($source['kabupaten'] ?? null),
                'provinsi' => $this->nullableText($source['provinsi'] ?? null),
                'nomor_telepon' => $this->nullableText($source['nomor_telepon'] ?? null),
                'akreditasi' => $this->nullableText($source['akreditasi'] ?? null),
                'lintang' => $latitude,
                'bujur' => $longitude,
                'coordinate_status' => $coordinateStatus,
                'coordinate_warning' => $coordinateWarning,
            ];
        }

        return [
            'rows' => $rows,
            'errors' => $errors,
            'warnings' => $warnings,
            'summary' => [
                'total' => count($sourceRows),
                'valid_rows' => count($rows),
                'inside' => $counts['inside'],
                'outside' => $counts['outside'],
                'missing' => $counts['missing'],
                'review' => $counts['review'],
            ],
            'boundary_available' => $boundaryGeometry !== null,
        ];
    }

    public function import(array $rows, User $user, string $fileName): array
    {
        if ($rows === []) {
            throw new RuntimeException('Tidak ada data sekolah yang dapat diimpor.');
        }

        return DB::transaction(function () use ($rows, $user, $fileName): array {
            $created = 0;
            $updated = 0;
            $coordinateWarnings = 0;

            $existingSchools = collect();

            foreach (array_chunk(array_column($rows, 'npsn'), 500) as $npsnChunk) {
                $existingSchools = $existingSchools->merge(
                    School::query()
                        ->whereIn('npsn', $npsnChunk)
                        ->lockForUpdate()
                        ->get()
                );
            }

            $existingSchools = $existingSchools->keyBy('npsn');

            foreach ($rows as $row) {
                $school = $existingSchools->get($row['npsn']);

                $isNew = $school === null;
                $isCoordinateVerified = $school?->coordinate_status === School::COORDINATE_VERIFIED;

                if ($school === null) {
                    $school = new School();
                }

                $school->fill(Arr::only($row, self::SCHOOL_FIELDS));

                if (! $isCoordinateVerified) {
                    $school->lintang = $row['lintang'];
                    $school->bujur = $row['bujur'];
                    $school->coordinate_status = $row['coordinate_status'];
                    $school->coordinate_verified_at = null;
                    $school->coordinate_verified_by = null;
                }

                $school->save();

                $isNew ? $created++ : $updated++;

                if (! $isCoordinateVerified && in_array($row['coordinate_status'], [
                    School::COORDINATE_MISSING,
                    School::COORDINATE_OUTSIDE_BOUNDARY,
                    School::COORDINATE_NEEDS_REVIEW,
                ], true)) {
                    $coordinateWarnings++;
                }
            }

            SchoolImportLog::query()->create([
                'uploaded_by' => $user->id,
                'source_file_name' => mb_substr(basename($fileName), 0, 255),
                'total_rows' => count($rows),
                'created_count' => $created,
                'updated_count' => $updated,
                'coordinate_warnings' => $coordinateWarnings,
                'status' => 'success',
                'summary' => [
                    'verified_coordinates_preserved' => true,
                    'coordinates_not_verified_by_import' => true,
                ],
            ]);

            return [
                'total' => count($rows),
                'created' => $created,
                'updated' => $updated,
                'coordinate_warnings' => $coordinateWarnings,
            ];
        });
    }

    private function normaliseCoordinates(mixed $latitude, mixed $longitude, ?array $geometry): array
    {
        $lat = $this->parseCoordinate($latitude);
        $lng = $this->parseCoordinate($longitude);

        if ($lat === null && $lng === null) {
            return [null, null, School::COORDINATE_MISSING, 'Koordinat belum tersedia.'];
        }

        if ($lat === null || $lng === null) {
            return [null, null, School::COORDINATE_NEEDS_REVIEW, 'Pasangan lintang dan bujur tidak lengkap atau formatnya tidak valid.'];
        }

        if ($lat == 0.0 && $lng == 0.0) {
            return [null, null, School::COORDINATE_MISSING, 'Koordinat 0,0 tidak dianggap sebagai lokasi sekolah.'];
        }

        if ($lat < -90 || $lat > 90 || $lng < -180 || $lng > 180) {
            return [null, null, School::COORDINATE_NEEDS_REVIEW, 'Nilai koordinat berada di luar rentang geografis yang valid.'];
        }

        if ($geometry === null) {
            return [$lat, $lng, School::COORDINATE_NEEDS_REVIEW, 'Batas wilayah belum dapat diperiksa; koordinat disimpan tanpa status aman dan tidak langsung dipetakan.'];
        }

        if (! $this->boundary->containsGeometry($geometry, $lat, $lng)) {
            return [$lat, $lng, School::COORDINATE_OUTSIDE_BOUNDARY, 'Koordinat berada di luar poligon Kota Palangka Raya dan perlu dikoreksi admin.'];
        }

        return [$lat, $lng, School::COORDINATE_PENDING, 'Koordinat berada di dalam wilayah kota, tetapi masih menunggu verifikasi admin.'];
    }

    private function parseCoordinate(mixed $value): ?float
    {
        if (! is_string($value) && ! is_numeric($value)) {
            return null;
        }

        $value = trim((string) $value);

        if ($value === '') {
            return null;
        }

        $value = str_replace(',', '.', $value);

        return is_numeric($value) ? (float) $value : null;
    }

    private function nullableText(mixed $value): ?string
    {
        if (! is_string($value) && ! is_numeric($value)) {
            return null;
        }

        $value = trim((string) $value);

        return $value === '' ? null : mb_substr($value, 0, 255);
    }
}
