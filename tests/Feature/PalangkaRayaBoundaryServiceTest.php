<?php

use App\Services\PalangkaRayaBoundaryService;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    Cache::flush();
});

test('loads the Palangkaraya boundary regardless of spacing in the returned name', function (string $returnedName) {
    $geometry = [
        'type' => 'Polygon',
        'coordinates' => [[
            [113.0, -3.0],
            [114.0, -3.0],
            [114.0, -2.0],
            [113.0, -2.0],
            [113.0, -3.0],
        ]],
    ];

    Http::fake([
        'https://geoservices.big.go.id/rbi/rest/services/Hosted/Wilayah_Administrasi_Kabupaten__Kota/FeatureServer/0/query*' => Http::response([
            'type' => 'FeatureCollection',
            'features' => [[
                'type' => 'Feature',
                'properties' => [
                    'namobj' => $returnedName,
                    'wadmkk' => $returnedName,
                    'wadmpr' => 'Kalimantan Tengah',
                ],
                'geometry' => $geometry,
            ]],
        ]),
    ]);

    expect(app(PalangkaRayaBoundaryService::class)->geometry())->toEqual($geometry);

    Http::assertSent(fn (Request $request) =>
        str_contains($request->url(), '/FeatureServer/0/query')
        && $request['where'] === "namobj LIKE '%Palangkaraya%'"
    );
})->with([
    'official BIG spelling' => 'Kota Palangkaraya',
    'spaced spelling' => 'Kota Palangka Raya',
]);
