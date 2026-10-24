<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use RuntimeException;
use Throwable;

class PalangkaRayaBoundaryService
{
    private const CACHE_KEY = 'sibaset.boundary.palangka-raya.big.2023';
    private const SERVICE_URL = 'https://geoservices.big.go.id/rbi/rest/services/Hosted/Wilayah_Administrasi_Kabupaten__Kota/FeatureServer/0/query';

    public function geometry(): array
    {
        return Cache::rememberForever(self::CACHE_KEY, function (): array {
            try {
                $response = Http::acceptJson()
                    ->timeout(15)
                    ->retry(2, 300)
                    ->get(self::SERVICE_URL, [
                        'where' => "namobj LIKE '%Palangkaraya%'",
                        'resultRecordCount' => 25,
                        'outFields' => '*',
                        'returnGeometry' => 'true',
                        'outSR' => '4326',
                        'f' => 'geojson',
                    ]);
            } catch (Throwable $exception) {
                throw new RuntimeException('Batas wilayah Palangka Raya belum dapat diambil dari layanan BIG.', 0, $exception);
            }

            if (! $response->successful()) {
                throw new RuntimeException('Layanan batas wilayah BIG mengembalikan HTTP '.$response->status().'.');
            }

            $features = $response->json('features');

            if (! is_array($features) || $features === []) {
                throw new RuntimeException('Layanan BIG tidak mengembalikan geometri Kota Palangka Raya.');
            }

            foreach ($features as $feature) {
                $properties = $feature['properties'] ?? [];
                $label = mb_strtolower(implode(' ', array_map(
                    static fn ($value) => is_scalar($value) ? (string) $value : '',
                    array_intersect_key($properties, array_flip(['namobj', 'NAMOBJ', 'nama', 'name', 'WADMKK', 'kabupaten']))
                )));
                $normalizedLabel = preg_replace('/[^\p{L}\p{N}]+/u', '', $label) ?? $label;

                if (str_contains($normalizedLabel, 'palangkaraya') && $this->validGeometry($feature['geometry'] ?? null)) {
                    return $feature['geometry'];
                }
            }

            if (count($features) === 1 && $this->validGeometry($features[0]['geometry'] ?? null)) {
                return $features[0]['geometry'];
            }

            throw new RuntimeException('Geometri yang dikembalikan BIG tidak cocok dengan Kota Palangka Raya.');
        });
    }

    public function contains(float $latitude, float $longitude): bool
    {
        return $this->containsGeometry($this->geometry(), $latitude, $longitude);
    }

    public function containsGeometry(array $geometry, float $latitude, float $longitude): bool
    {
        if (($geometry['type'] ?? null) === 'Feature') {
            $geometry = $geometry['geometry'] ?? [];
        }

        $type = $geometry['type'] ?? null;
        $coordinates = $geometry['coordinates'] ?? null;

        if (! is_array($coordinates)) {
            throw new RuntimeException('Geometri batas wilayah tidak memiliki koordinat yang valid.');
        }

        $polygons = match ($type) {
            'Polygon' => [$coordinates],
            'MultiPolygon' => $coordinates,
            default => throw new RuntimeException('Tipe geometri batas wilayah tidak didukung.'),
        };

        foreach ($polygons as $rings) {
            if (! isset($rings[0]) || ! $this->pointInRing($rings[0], $longitude, $latitude)) {
                continue;
            }

            $insideHole = false;

            foreach (array_slice($rings, 1) as $hole) {
                if ($this->pointInRing($hole, $longitude, $latitude)) {
                    $insideHole = true;
                    break;
                }
            }

            if (! $insideHole) {
                return true;
            }
        }

        return false;
    }

    private function validGeometry(mixed $geometry): bool
    {
        return is_array($geometry)
            && in_array($geometry['type'] ?? null, ['Polygon', 'MultiPolygon'], true)
            && is_array($geometry['coordinates'] ?? null)
            && $geometry['coordinates'] !== [];
    }

    private function pointInRing(array $ring, float $longitude, float $latitude): bool
    {
        $inside = false;
        $count = count($ring);

        if ($count < 3) {
            return false;
        }

        for ($i = 0, $j = $count - 1; $i < $count; $j = $i++) {
            $a = $ring[$j] ?? null;
            $b = $ring[$i] ?? null;

            if (! is_array($a) || ! is_array($b) || count($a) < 2 || count($b) < 2) {
                continue;
            }

            $x1 = (float) $a[0];
            $y1 = (float) $a[1];
            $x2 = (float) $b[0];
            $y2 = (float) $b[1];

            if ($this->pointOnSegment($longitude, $latitude, $x1, $y1, $x2, $y2)) {
                return true;
            }

            if (($y1 > $latitude) !== ($y2 > $latitude)) {
                $intersection = (($x2 - $x1) * ($latitude - $y1) / ($y2 - $y1)) + $x1;

                if ($longitude < $intersection) {
                    $inside = ! $inside;
                }
            }
        }

        return $inside;
    }

    private function pointOnSegment(
        float $x,
        float $y,
        float $x1,
        float $y1,
        float $x2,
        float $y2,
    ): bool {
        $cross = (($y - $y1) * ($x2 - $x1)) - (($x - $x1) * ($y2 - $y1));

        if (abs($cross) > 1.0e-8) {
            return false;
        }

        return $x >= min($x1, $x2) - 1.0e-8
            && $x <= max($x1, $x2) + 1.0e-8
            && $y >= min($y1, $y2) - 1.0e-8
            && $y <= max($y1, $y2) + 1.0e-8;
    }
}
