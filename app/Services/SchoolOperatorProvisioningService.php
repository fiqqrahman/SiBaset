<?php

namespace App\Services;

use App\Models\School;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class SchoolOperatorProvisioningService
{
    /**
     * @return array{total: int, create: int, created: int, skipped: int, conflicts: int, rows: array<int, array{school: School, email: string, status: string, reason: string|null}>}
     */
    public function preview(): array
    {
        return $this->summarize($this->buildPlan(School::query()->orderBy('id')->get()));
    }

    /**
     * @return array{total: int, create: int, created: int, skipped: int, conflicts: int, rows: array<int, array{school: School, email: string, status: string, reason: string|null}>}
     */
    public function provision(string $password): array
    {
        return DB::transaction(function () use ($password): array {
            $schools = School::query()->orderBy('id')->lockForUpdate()->get();
            $rows = $this->buildPlan($schools);

            foreach ($rows as $index => $row) {
                if ($row['status'] !== 'create') {
                    continue;
                }

                User::query()->create([
                    'name' => Str::limit('Operator '.$row['school']->nama, 255, ''),
                    'email' => $row['email'],
                    'password' => Hash::make($password),
                    'role' => 'operator_sekolah',
                    'school_id' => $row['school']->id,
                    'password_change_recommended' => true,
                ]);

                $rows[$index]['status'] = 'created';
            }

            return $this->summarize($rows);
        });
    }

    private function buildPlan(EloquentCollection $schools): array
    {
        $operatorSchoolIds = User::query()
            ->where('role', 'operator_sekolah')
            ->whereNotNull('school_id')
            ->pluck('school_id')
            ->mapWithKeys(static fn ($id): array => [(int) $id => true]);

        $existingEmails = User::query()
            ->pluck('email')
            ->mapWithKeys(static fn ($email): array => [strtolower((string) $email) => true]);

        $rows = [];

        foreach ($schools as $school) {
            $email = $this->emailFor($school);
            $schoolId = (int) $school->id;

            if ($operatorSchoolIds->has($schoolId)) {
                $status = 'skip';
                $reason = 'Sekolah sudah memiliki akun operator.';
            } elseif ($existingEmails->has(strtolower($email))) {
                $status = 'conflict';
                $reason = 'Email akun internal sudah dipakai akun lain.';
            } else {
                $status = 'create';
                $reason = null;
                $existingEmails->put(strtolower($email), true);
            }

            $rows[] = [
                'school' => $school,
                'email' => $email,
                'status' => $status,
                'reason' => $reason,
            ];
        }

        return $rows;
    }

    private function emailFor(School $school): string
    {
        $slug = Str::slug($school->nama) ?: 'sekolah';

        return 'operator.'.substr($slug, 0, 100).'.'.$school->npsn.'@sibaset.invalid';
    }

    /**
     * @param array<int, array{school: School, email: string, status: string, reason: string|null}> $rows
     * @return array{total: int, create: int, created: int, skipped: int, conflicts: int, rows: array<int, array{school: School, email: string, status: string, reason: string|null}>}
     */
    private function summarize(array $rows): array
    {
        $statuses = array_column($rows, 'status');

        return [
            'total' => count($rows),
            'create' => count(array_filter($statuses, static fn (string $status): bool => in_array($status, ['create', 'created'], true))),
            'created' => count(array_filter($statuses, static fn (string $status): bool => $status === 'created')),
            'skipped' => count(array_filter($statuses, static fn (string $status): bool => $status === 'skip')),
            'conflicts' => count(array_filter($statuses, static fn (string $status): bool => $status === 'conflict')),
            'rows' => $rows,
        ];
    }
}
