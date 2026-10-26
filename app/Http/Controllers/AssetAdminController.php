<?php

namespace App\Http\Controllers;

use App\Models\Asset;
use App\Models\AssetAcquisition;
use App\Models\AssetAcquisitionReview;
use App\Models\AssetCategory;
use App\Models\AssetChangeRequest;
use App\Models\AssetDocument;
use App\Models\AssetEvent;
use App\Models\AssetType;
use App\Models\School;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class AssetAdminController extends Controller
{

    public function schoolAcquisitions(School $school): JsonResponse
    {
        $acquisitions = AssetAcquisition::query()
            ->where('school_id', $school->id)
            ->whereIn('ownership_status', [AssetAcquisition::STATUS_PENDING, AssetAcquisition::STATUS_NEEDS_REVISION])
            ->with(['assets.type.category', 'documents'])
            ->latest()
            ->get();

        return response()->json([
            'data' => $acquisitions->map(fn(AssetAcquisition $acquisition): array => [
                'id' => $acquisition->id,
                'school_id' => $acquisition->school_id,
                'status' => $acquisition->ownership_status,
                'funding_source' => $acquisition->funding_source,
                'acquisition_method' => $acquisition->acquisition_method,
                'owner_entity' => $acquisition->owner_entity,
                'provider_name' => $acquisition->provider_name,
                'document_reference' => $acquisition->document_reference,
                'acquired_at' => $acquisition->acquired_at?->toDateString(),
                'created_at' => $acquisition->created_at?->toIso8601String(),
                'total_value' => $acquisition->total_value,
                'notes' => $acquisition->notes,
                'review_note' => $acquisition->review_note,
                'review_url' => route('admin.assets.acquisitions.review', $acquisition),
                'assets' => $acquisition->assets->map(fn(Asset $asset): array => [
                    'id' => $asset->id,
                    'code' => $asset->asset_code,
                    'name' => $asset->name,
                    'category' => $asset->type->category->scheme === 'KIBAR' ? 'KIBAR' : 'KIB ' . $asset->type->category->code,
                    'category_name' => $asset->type->category->name,
                    'type' => $asset->type->name,
                    'serial_number' => $asset->serial_number,
                    'condition' => $asset->condition,
                    'location' => $asset->location,
                    'year' => $asset->year_acquired,
                    'status' => $asset->status,
                ])->values(),
                'documents' => $acquisition->documents->map(fn(AssetDocument $document): array => [
                    'id' => $document->id,
                    'kind' => $document->document_kind,
                    'name' => $document->original_name,
                    'download_url' => route('admin.assets.documents.download', $document),
                ])->values(),
            ])->values(),
            'meta' => [
                'total' => $acquisitions->count(),
                'pending' => $acquisitions->where('ownership_status', AssetAcquisition::STATUS_PENDING)->count(),
                'needs_revision' => $acquisitions->where('ownership_status', AssetAcquisition::STATUS_NEEDS_REVISION)->count(),
            ],
        ]);
    }

    public function schoolAssets(Request $request, School $school): JsonResponse
    {
        $data = $request->validate([
            'category' => ['nullable', Rule::in(['A', 'B', 'C', 'D', 'E', 'F'])],
            'condition' => ['nullable', Rule::in(['baik', 'rusak_ringan', 'rusak_berat', 'tidak_diketahui'])],
            'q' => ['nullable', 'string', 'max:120'],
            'page' => ['nullable', 'integer', 'min:1'],
        ]);

        $query = Asset::query()
            ->where('school_id', $school->id)
            ->with(['type.category', 'acquisition']);

        if (! empty($data['category'])) {
            $query->whereHas('type.category', fn($builder) => $builder->where('code', $data['category']));
        }

        if (! empty($data['condition'])) {
            $query->where('condition', $data['condition']);
        }

        if (! empty($data['q'])) {
            $term = trim($data['q']);
            $query->where(fn($builder) => $builder
                ->where('asset_code', 'like', "%{$term}%")
                ->orWhere('name', 'like', "%{$term}%")
                ->orWhere('location', 'like', "%{$term}%")
                ->orWhere('serial_number', 'like', "%{$term}%"));
        }

        $assets = $query->orderBy('asset_code')->paginate(8)->withQueryString();

        return response()->json([
            'data' => $assets->getCollection()->map(fn(Asset $asset): array => [
                'id' => $asset->id,
                'code' => $asset->asset_code,
                'name' => $asset->name,
                'category' => $asset->type->category->code,
                'category_name' => $asset->type->category->name,
                'type' => $asset->type->name,
                'location' => $asset->location,
                'condition' => $asset->condition,
                'year' => $asset->year_acquired,
                'status' => $asset->status,
                'ownership_status' => $asset->acquisition->ownership_status,
            ]),
            'meta' => [
                'total' => $assets->total(),
                'current_page' => $assets->currentPage(),
                'last_page' => $assets->lastPage(),
                'per_page' => $assets->perPage(),
            ],
        ]);
    }

    public function schoolChanges(Request $request, School $school): JsonResponse
    {
        $data = $request->validate([
            'page' => ['nullable', 'integer', 'min:1'],
        ]);

        $changes = AssetChangeRequest::query()
            ->where('status', 'pending')
            ->whereHas('asset', fn($query) => $query->where('school_id', $school->id))
            ->with(['asset.school', 'asset.type.category', 'submittedBy'])
            ->latest()
            ->paginate(10, ['*'], 'page', (int) ($data['page'] ?? 1));

        return response()->json([
            'data' => $changes->getCollection()->map(fn(AssetChangeRequest $change): array => [
                'id' => $change->id,
                'asset_id' => $change->asset_id,
                'asset_name' => $change->asset->name,
                'asset_code' => $change->asset->asset_code,
                'school_id' => $school->id,
                'school_name' => $school->nama,
                'npsn' => $school->npsn,
                'request_note' => $change->request_note,
                'proposed_changes' => $change->proposed_changes ?? [],
                'submitted_by' => $change->submittedBy?->name,
                'created_at' => $change->created_at?->toIso8601String(),
                'review_url' => route('admin.assets.changes.review', $change),
            ])->values(),
            'meta' => [
                'total' => $changes->total(),
                'current_page' => $changes->currentPage(),
                'last_page' => $changes->lastPage(),
                'per_page' => $changes->perPage(),
            ],
        ]);
    }

    public function schoolHistory(Request $request, School $school): JsonResponse
    {
        $data = $request->validate([
            'page' => ['nullable', 'integer', 'min:1'],
        ]);

        $history = AssetAcquisition::query()
            ->where('school_id', $school->id)
            ->whereIn('ownership_status', [
                AssetAcquisition::STATUS_VERIFIED_BMD,
                AssetAcquisition::STATUS_VERIFIED_NON_BMD,
                AssetAcquisition::STATUS_REJECTED,
            ])
            ->with(['assets.type.category', 'documents', 'events.actor'])
            ->latest('reviewed_at')
            ->paginate(10, ['*'], 'page', (int) ($data['page'] ?? 1));

        return response()->json([
            'data' => $history->getCollection()->map(fn(AssetAcquisition $acquisition): array => [
                'id' => $acquisition->id,
                'school_id' => $acquisition->school_id,
                'status' => $acquisition->ownership_status,
                'owner_entity' => $acquisition->owner_entity,
                'document_reference' => $acquisition->document_reference,
                'review_note' => $acquisition->review_note,
                'reviewed_at' => $acquisition->reviewed_at?->toIso8601String(),
                'assets' => $acquisition->assets->map(fn(Asset $asset): array => [
                    'code' => $asset->asset_code,
                    'name' => $asset->name,
                    'category' => $asset->type->category->scheme === 'KIBAR' ? 'KIBAR' : 'KIB ' . $asset->type->category->code,
                    'type' => $asset->type->name,
                ])->values(),
                'documents' => $acquisition->documents->map(fn(AssetDocument $document): array => [
                    'name' => $document->original_name,
                    'kind' => $document->document_kind,
                    'download_url' => route('admin.assets.documents.download', $document),
                ])->values(),
                'events' => $acquisition->events
                    ->sortByDesc('created_at')
                    ->take(8)
                    ->map(fn(AssetEvent $event): array => [
                        'event_type' => $event->event_type,
                        'actor_name' => $event->actor?->name ?? 'Akun tidak tersedia',
                        'created_at' => $event->created_at?->toIso8601String(),
                        'decision' => data_get($event->details, 'decision'),
                        'review_note' => data_get($event->details, 'review_note'),
                    ])->values(),
            ]),
            'meta' => [
                'total' => $history->total(),
                'current_page' => $history->currentPage(),
                'last_page' => $history->lastPage(),
                'per_page' => $history->perPage(),
            ],
        ]);
    }

    public function typeCatalogue(): JsonResponse
    {
        return response()->json([
            'categories' => AssetCategory::query()
                ->withCount('types')
                ->orderBy('code')
                ->get()
                ->map(fn(AssetCategory $category): array => [
                    'id' => $category->id,
                    'code' => $category->code,
                    'name' => $category->name,
                    'scheme' => $category->scheme,
                    'types_count' => $category->types_count,
                ])->values(),
            'types' => AssetType::query()
                ->with('category')
                ->orderBy('asset_category_id')
                ->orderBy('name')
                ->get()
                ->map(fn(AssetType $type): array => [
                    'id' => $type->id,
                    'asset_category_id' => $type->asset_category_id,
                    'name' => $type->name,
                    'classification_code' => $type->classification_code,
                    'description' => $type->description,
                    'is_active' => (bool) $type->is_active,
                    'category_code' => $type->category->code,
                    'category_name' => $type->category->name,
                    'category_scheme' => $type->category->scheme,
                    'toggle_url' => route('admin.assets.types.toggle', $type),
                ])->values(),
        ]);
    }

    public function reviewAcquisition(Request $request, AssetAcquisition $acquisition): RedirectResponse|JsonResponse
    {
        $data = $request->validate([
            'decision' => ['required', Rule::in([
                AssetAcquisition::STATUS_VERIFIED_BMD,
                AssetAcquisition::STATUS_VERIFIED_NON_BMD,
                AssetAcquisition::STATUS_NEEDS_REVISION,
                AssetAcquisition::STATUS_REJECTED,
            ])],
            'verified_owner_entity' => ['nullable', Rule::in([
                'belum_ditentukan',
                'pemkot',
                'pemprov',
                'pemerintah_pusat',
                'yayasan',
                'pihak_lain',
            ])],
            'review_note' => ['nullable', 'string', 'max:3000'],
        ]);

        if (
            in_array($data['decision'], [AssetAcquisition::STATUS_NEEDS_REVISION, AssetAcquisition::STATUS_REJECTED], true)
            && trim((string) ($data['review_note'] ?? '')) === ''
        ) {
            throw ValidationException::withMessages([
                'review_note' => 'Catatan wajib diisi ketika perolehan perlu diperbaiki atau ditolak.',
            ]);
        }

        $verifiedDecisions = [
            AssetAcquisition::STATUS_VERIFIED_BMD,
            AssetAcquisition::STATUS_VERIFIED_NON_BMD,
        ];

        if (in_array($data['decision'], $verifiedDecisions, true)) {
            $verifiedOwner = $data['verified_owner_entity'] ?? 'belum_ditentukan';
            $ownerDecisionIsInconsistent = $data['decision'] === AssetAcquisition::STATUS_VERIFIED_BMD
                ? $verifiedOwner !== 'pemkot'
                : in_array($verifiedOwner, ['pemkot', 'belum_ditentukan'], true);

            if ($ownerDecisionIsInconsistent) {
                throw ValidationException::withMessages([
                    'verified_owner_entity' => $data['decision'] === AssetAcquisition::STATUS_VERIFIED_BMD
                        ? 'Untuk menetapkan status BMD Kota, entitas pemilik terverifikasi harus Pemerintah Kota Palangka Raya.'
                        : 'Untuk menetapkan non-BMD Kota, pilih entitas pemilik di luar Pemerintah Kota atau lengkapi bukti terlebih dahulu.',
                ]);
            }
        }

        DB::transaction(function () use ($request, $acquisition, $data, $verifiedDecisions): void {
            $locked = AssetAcquisition::query()->with('assets')->lockForUpdate()->findOrFail($acquisition->id);

            abort_unless(in_array($locked->ownership_status, [
                AssetAcquisition::STATUS_PENDING,
                AssetAcquisition::STATUS_NEEDS_REVISION,
            ], true), 409, 'Perolehan ini tidak sedang menunggu pemeriksaan.');

            $previousOwnerEntity = $locked->owner_entity;
            $previousOwnershipStatus = $locked->ownership_status;
            $acquisitionUpdates = [
                'ownership_status' => $data['decision'],
                'reviewed_by' => $request->user()->id,
                'reviewed_at' => now(),
                'review_note' => $data['review_note'] ?? null,
            ];

            if (in_array($data['decision'], $verifiedDecisions, true)) {
                $acquisitionUpdates['owner_entity'] = $data['verified_owner_entity'];
            }

            $locked->update($acquisitionUpdates);

            AssetAcquisitionReview::query()->create([
                'asset_acquisition_id' => $locked->id,
                'reviewed_by' => $request->user()->id,
                'decision' => $data['decision'],
                'owner_entity_before' => $previousOwnerEntity,
                'owner_entity_after' => $locked->owner_entity,
                'review_note' => $data['review_note'] ?? null,
            ]);

            AssetEvent::query()->create([
                'asset_acquisition_id' => $locked->id,
                'actor_id' => $request->user()->id,
                'event_type' => 'acquisition_reviewed',
                'details' => [
                    'decision' => $data['decision'],
                    'previous' => [
                        'ownership_status' => $previousOwnershipStatus,
                        'owner_entity' => $previousOwnerEntity,
                    ],
                    'current' => [
                        'ownership_status' => $locked->ownership_status,
                        'owner_entity' => $locked->owner_entity,
                    ],
                    'owner_entity_before' => $previousOwnerEntity,
                    'owner_entity_after' => $locked->owner_entity,
                    'review_note' => $data['review_note'] ?? null,
                ],
                'created_at' => now(),
            ]);

            $assetStatus = match ($data['decision']) {
                AssetAcquisition::STATUS_VERIFIED_BMD,
                AssetAcquisition::STATUS_VERIFIED_NON_BMD => Asset::STATUS_APPROVED,
                AssetAcquisition::STATUS_NEEDS_REVISION => Asset::STATUS_NEEDS_REVISION,
                default => Asset::STATUS_REJECTED,
            };

            $locked->assets()->get()->each(function (Asset $asset) use ($request, $assetStatus, $data, $locked): void {
                $previousAssetStatus = $asset->status;
                $asset->update([
                    'status' => $assetStatus,
                    'reviewed_by' => $request->user()->id,
                    'reviewed_at' => now(),
                    'review_note' => $data['review_note'] ?? null,
                ]);

                AssetEvent::query()->create([
                    'asset_id' => $asset->id,
                    'asset_acquisition_id' => $locked->id,
                    'actor_id' => $request->user()->id,
                    'event_type' => 'asset_reviewed',
                    'details' => [
                        'decision' => $data['decision'],
                        'previous' => ['status' => $previousAssetStatus],
                        'current' => ['status' => $assetStatus],
                        'asset_status' => $assetStatus,
                        'review_note' => $data['review_note'] ?? null,
                    ],
                    'created_at' => now(),
                ]);
            });
        });

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Hasil pemeriksaan perolehan berhasil dicatat.',
                'decision' => $data['decision'],
                'acquisition_id' => (int) $acquisition->id,
            ]);
        }

        return redirect()->route('admin.dashboard')
            ->with('status', 'Hasil pemeriksaan perolehan berhasil dicatat.');
    }

    public function reviewChangeRequest(Request $request, AssetChangeRequest $changeRequest): RedirectResponse|JsonResponse
    {
        $data = $request->validate([
            'decision' => ['required', Rule::in(['approved', 'needs_revision', 'rejected'])],
            'review_note' => ['nullable', 'string', 'max:3000'],
        ]);

        if (
            in_array($data['decision'], ['needs_revision', 'rejected'], true)
            && trim((string) ($data['review_note'] ?? '')) === ''
        ) {
            throw ValidationException::withMessages([
                'review_note' => 'Catatan wajib diisi ketika pengajuan perlu diperbaiki atau ditolak.',
            ]);
        }

        DB::transaction(function () use ($request, $changeRequest, $data): void {
            $locked = AssetChangeRequest::query()->with('asset')->lockForUpdate()->findOrFail($changeRequest->id);

            abort_unless($locked->status === 'pending', 409, 'Pengajuan perubahan sudah diperiksa.');

            $previousAsset = $locked->asset->only(['condition', 'location', 'notes']);
            $proposedChanges = collect($locked->proposed_changes)->only(['condition', 'location', 'notes'])->all();

            if ($data['decision'] === 'approved') {
                abort_unless($locked->asset->status === Asset::STATUS_APPROVED, 409, 'Status aset tidak lagi memungkinkan perubahan ini.');

                $locked->asset->update($proposedChanges);
            }

            $locked->update([
                'status' => $data['decision'],
                'reviewed_by' => $request->user()->id,
                'reviewed_at' => now(),
                'review_note' => $data['review_note'] ?? null,
            ]);

            AssetEvent::query()->create([
                'asset_id' => $locked->asset_id,
                'asset_acquisition_id' => $locked->asset->asset_acquisition_id,
                'actor_id' => $request->user()->id,
                'event_type' => 'change_' . ($data['decision'] === 'approved' ? 'approved' : $data['decision']),
                'details' => [
                    'change_request_id' => $locked->id,
                    'decision' => $data['decision'],
                    'previous' => $previousAsset,
                    'proposed' => $proposedChanges,
                    'current' => $locked->asset->only(['condition', 'location', 'notes']),
                    'review_note' => $data['review_note'] ?? null,
                ],
                'created_at' => now(),
            ]);
        });

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Keputusan pengajuan perubahan berhasil dicatat.',
                'change_request_id' => (int) $changeRequest->id,
                'decision' => $data['decision'],
            ]);
        }

        return redirect()->route('admin.dashboard')
            ->with('status', 'Keputusan pengajuan perubahan berhasil dicatat.');
    }

    public function storeType(Request $request): RedirectResponse|JsonResponse
    {
        $data = $request->validate([
            'asset_category_id' => ['required', 'exists:asset_categories,id'],
            'name' => ['required', 'string', 'max:255'],
            'classification_code' => ['nullable', 'string', 'max:100', Rule::unique('asset_types', 'classification_code')->where(fn($query) => $query->where('asset_category_id', $request->input('asset_category_id')))],
            'description' => ['nullable', 'string', 'max:2000'],
        ]);

        $duplicate = AssetType::query()
            ->where('asset_category_id', $data['asset_category_id'])
            ->whereRaw('LOWER(name) = ?', [mb_strtolower(trim($data['name']))])
            ->exists();

        if ($duplicate) {
            $message = 'Jenis barang dengan nama tersebut sudah ada pada kategori ini.';
            if ($request->expectsJson()) {
                return response()->json(['message' => $message, 'errors' => ['name' => [$message]]], 422);
            }

            return back()->withErrors(['name' => $message])->withInput();
        }

        $data['name'] = trim($data['name']);
        $data['is_active'] = true;
        AssetType::query()->create($data);

        if ($request->expectsJson()) {
            return response()->json(['message' => 'Master jenis barang berhasil ditambahkan.'], 201);
        }

        return redirect()->route('admin.dashboard')
            ->with('status', 'Master jenis barang berhasil ditambahkan.');
    }

    public function toggleType(Request $request, AssetType $type): RedirectResponse|JsonResponse
    {
        $type->update(['is_active' => ! $type->is_active]);
        $message = $type->is_active ? 'Jenis barang diaktifkan.' : 'Jenis barang dinonaktifkan.';

        if ($request->expectsJson()) {
            return response()->json(['message' => $message, 'is_active' => (bool) $type->is_active]);
        }

        return redirect()->route('admin.dashboard')->with('status', $message);
    }
}
