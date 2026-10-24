<?php

namespace App\Console\Commands;

use App\Models\School;
use App\Services\SchoolOperatorProvisioningService;
use Illuminate\Console\Command;

class MakeSibasetOperators extends Command
{
    protected $signature = 'sibaset:make-operators {--dry-run : Tampilkan rencana tanpa menyimpan perubahan}';

    protected $description = 'Buat akun operator untuk sekolah yang belum memiliki operator.';

    public function handle(SchoolOperatorProvisioningService $provisioner): int
    {
        if (School::query()->doesntExist()) {
            $this->error('Belum ada data sekolah. Impor data sekolah terlebih dahulu. Tidak ada perubahan.');

            return self::FAILURE;
        }

        $preview = $provisioner->preview();
        $this->displayRows($preview['rows']);

        if ($this->option('dry-run')) {
            $this->info("Dry-run selesai: {$preview['create']} akun akan dibuat; tidak ada data yang diubah.");
            $this->displaySummary($preview);

            return self::SUCCESS;
        }

        if ($preview['create'] === 0) {
            $this->warn('Tidak ada akun operator baru yang perlu dibuat.');
            $this->displaySummary($preview);

            return $preview['conflicts'] > 0 ? self::FAILURE : self::SUCCESS;
        }

        $this->warn('Gunakan hanya untuk pengembangan. Semua akun baru akan menerima kata sandi awal yang sama dan harus segera diganti.');
        $password = (string) $this->secret('Kata sandi awal operator (minimal 12 karakter)');
        $confirmation = (string) $this->secret('Ulangi kata sandi awal operator');

        if (strlen($password) < 12) {
            $this->error('Kata sandi harus minimal 12 karakter. Tidak ada perubahan.');

            return self::FAILURE;
        }

        if (! hash_equals($password, $confirmation)) {
            $this->error('Konfirmasi kata sandi tidak sama. Tidak ada perubahan.');

            return self::FAILURE;
        }

        $result = $provisioner->provision($password);
        $this->displaySummary($result);

        foreach ($result['rows'] as $row) {
            if ($row['status'] === 'conflict') {
                $this->warn("Konflik NPSN {$row['school']->npsn}: {$row['reason']} Email: {$row['email']}");
            }
        }

        $this->info('Provisioning selesai. Kata sandi tidak ditampilkan atau dicatat oleh perintah ini.');

        return $result['conflicts'] > 0 ? self::FAILURE : self::SUCCESS;
    }

    private function displayRows(array $rows): void
    {
        $this->table(
            ['NPSN', 'Sekolah', 'Email internal', 'Status'],
            array_map(static fn (array $row): array => [
                $row['school']->npsn,
                $row['school']->nama,
                $row['email'],
                match ($row['status']) {
                    'create' => 'Akan dibuat',
                    'created' => 'Dibuat',
                    'skip' => 'Dilewati',
                    'conflict' => 'Konflik',
                    default => $row['status'],
                },
            ], $rows),
        );
    }

    private function displaySummary(array $summary): void
    {
        $this->line("Sekolah diperiksa: {$summary['total']}");
        $this->line("Akun baru (direncanakan/dibuat): {$summary['create']}");
        $this->line("Dilewati: {$summary['skipped']}");
        $this->line("Konflik: {$summary['conflicts']}");
    }
}
