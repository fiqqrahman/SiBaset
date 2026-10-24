<?php

namespace App\Http\Controllers;

use App\Models\Asset;
use App\Models\AssetAcquisition;
use App\Models\AssetAcquisitionReview;
use App\Models\AssetCategory;
use App\Models\AssetChangeRequest;
use App\Models\AssetEvent;
use App\Models\AssetType;
use App\Models\School;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AssetAdminController extends Controller
{

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
            $query->whereHas('type.category', fn ($builder) => $builder->where('code', $data['category']));
        }

        if (! empty($data['condition'])) {
            $query->where('condition', $data['condition']);
        }

        if (! empty($data['q'])) {
            $term = trim($data['q']);
            $query->where(fn ($builder) => $builder
                ->where('asset_code', 'like', "%{$term}%")
                ->orWhere('name', 'like', "%{$term}%")
                ->orWhere('location', 'like', "%{$term}%")
                ->orWhere('serial_number', 'like', "%{$term}%"));
        }

        $assets = $query->orderBy('asset_code')->paginate(8)->withQueryString();

        return response()->json([
            'data' => $assets->getCollection()->map(fn (Asset $asset): array => [
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

    public function index(Request $request): View
    {
        $tab = in_array($request->query('tab'), ['perolehan', 'perubahan', 'jenis', 'riwayat'], true)
            ? $request->query('tab') : 'perolehan';

        return view('pages.admin.assets.index', [
            'tab' => $tab,
            'acquisitions' => AssetAcquisition::query()
                ->with(['school', 'createdBy', 'assets.type.category', 'documents'])
                ->whereIn('ownership_status', [
                    AssetAcquisition::STATUS_PENDING,
                    AssetAcquisition::STATUS_NEEDS_REVISION,
                ])
                ->latest()
                ->paginate(15, ['*'], 'perolehan_page')
                ->withQueryString(),
            'changeRequests' => AssetChangeRequest::query()
                ->with(['asset.school', 'asset.type.category', 'submittedBy'])
                ->where('status', 'pending')
                ->latest()
                ->paginate(15, ['*'], 'perubahan_page')
                ->withQueryString(),
            'history' => AssetAcquisition::query()
                ->with(['school', 'createdBy', 'assets.type.category', 'documents', 'events.actor'])
                ->whereIn('ownership_status', [
                    AssetAcquisition::STATUS_VERIFIED_BMD,
                    AssetAcquisition::STATUS_VERIFIED_NON_BMD,
                    AssetAcquisition::STATUS_REJECTED,
                ])
                ->latest('reviewed_at')
                ->paginate(15, ['*'], 'riwayat_page')
                ->withQueryString(),
            'categories' => AssetCategory::query()->withCount('types')->orderBy('code')->get(),
            'types' => AssetType::query()->with('category')->orderBy('asset_category_id')->orderBy('name')->paginate(30, ['*'], 'jenis_page')->withQueryString(),
            'summary' => [
                'pending_acquisitions' => AssetAcquisition::query()->where('ownership_status', AssetAcquisition::STATUS_PENDING)->count(),
                'pending_changes' => AssetChangeRequest::query()->where('status', 'pending')->count(),
                'approved_assets' => Asset::query()->where('status', Asset::STATUS_APPROVED)->count(),
            ],
        ]);
    }

    public function reviewAcquisition(Request $request, AssetAcquisition $acquisition): RedirectResponse
    {
        $data = $request->validate([
            'decision' => ['required', Rule::in([
                AssetAcquisition::STATUS_VERIFIED_BMD,
                AssetAcquisition::STATUS_VERIFIED_NON_BMD,
                AssetAcquisition::STATUS_NEEDS_REVISION,
                AssetAcquisition::STATUS_REJECTED,
            ])],
            'verified_owner_entity' => ['nullable', Rule::in([
                'belum_ditentukan', 'pemkot', 'pemprov', 'pemerintah_pusat', 'yayasan', 'pihak_lain',
            ])],
            'review_note' => ['nullable', 'string', 'max:3000'],
        ]);

        if (in_array($data['decision'], [AssetAcquisition::STATUS_NEEDS_REVISION, AssetAcquisition::STATUS_REJECTED], true)
            && trim((string) ($data['review_note'] ?? '')) === '') {
            throw \\Illuminate\\Validation\\ValidationException::withMessages([
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
                throw \\Illuminate\\Validation\\ValidationException::withMessages([
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

        return redirect()->route('admin.assets.index', ['tab' => 'perolehan'])
            ->with('status', 'Hasil pemeriksaan perolehan berhasil dicatat.');
    }

    public function reviewChangeRequest(Request $request, AssetChangeRequest $changeRequest): RedirectResponse
    {
        $data = $request->validate([
            'decision' => ['required', Rule::in(['approved', 'needs_revision', 'rejected'])],
            'review_note' => ['nullable', 'string', 'max:3000'],
        ]);

        if (in_array($data['decision'], ['needs_revision', 'rejected'], true)
            && trim((string) ($data['review_note'] ?? '')) === '') {
            throw \\Illuminate\\Validation\\ValidationException::withMessages([
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
                'event_type' => 'change_'.($data['decision'] === 'approved' ? 'approved' : $data['decision']),
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

        return redirect()->route('admin.assets.index', ['tab' => 'perubahan'])
            ->with('status', 'Keputusan pengajuan perubahan berhasil dicatat.');
    }

    public function storeType(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'asset_category_id' => ['required', 'exists:asset_categories,id'],
            'name' => ['required', 'string', 'max:255'],
            'classification_code' => ['nullable', 'string', 'max:100', Rule::unique('asset_types', 'classification_code')->where(fn ($query) => $query->where('asset_category_id', $request->input('asset_category_id')))],
            'description' => ['nullable', 'string', 'max:2000'],
        ]);

        $duplicate = AssetType::query()
            ->where('asset_category_id', $data['asset_category_id'])
            ->whereRaw('LOWER(name) = ?', [mb_strtolower(trim($data['name']))])
            ->exists();

        if ($duplicate) {
            return back()->withErrors(['name' => 'Jenis barang dengan nama tersebut sudah ada pada kategori ini.'])->withInput();
        }

        $data['name'] = trim($data['name']);
        $data['is_active'] = true;
        AssetType::query()->create($data);

        return redirect()->route('admin.assets.index', ['tab' => 'jenis'])
            ->with('status', 'Master jenis barang berhasil ditambahkan.');
    }

    public function toggleType(AssetType $type): RedirectResponse
    {
        $type->update(['is_active' => ! $type->is_active]);

        return redirect()->route('admin.assets.index', ['tab' => 'jenis'])
            ->with('status', $type->is_active ? 'Jenis barang diaktifkan.' : 'Jenis barang dinonaktifkan.');
    }
}