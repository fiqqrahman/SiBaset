<?php

namespace App\Console\Commands;

use App\Services\PalangkaRayaBoundaryService;
use Illuminate\Console\Command;
use Throwable;

class CachePalangkaRayaBoundary extends Command
{
    protected $signature = 'sibaset:cache-city-boundary';

    protected $description = 'Validasi dan cache poligon batas administratif Kota Palangka Raya dari layanan BIG.';

    public function handle(PalangkaRayaBoundaryService $boundary): int
    {
        try {
            $geometry = $boundary->geometry();
        } catch (Throwable $exception) {
            $this->error('Poligon batas kota belum dapat disiapkan: '.$exception->getMessage());

            return self::FAILURE;
        }

        $this->info('Poligon Kota Palangka Raya berhasil diverifikasi dan disimpan pada cache aplikasi.');
        $this->line('Tipe geometri: '.($geometry['type'] ?? 'unknown'));
        $this->line('Sumber: BIG RBI · Layer Wilayah Administrasi Kabupaten/Kota');

        return self::SUCCESS;
    }
}
