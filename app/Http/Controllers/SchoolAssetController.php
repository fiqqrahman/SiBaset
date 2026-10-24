<?php

namespace App\Http\Controllers;

use App\Models\Asset;
use App\Models\AssetAcquisition;
use App\Models\AssetCategory;
use App\Models\AssetChangeRequest;
use App\Models\AssetDocument;
use App\Models\AssetEvent;
use App\Models\AssetType;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class SchoolAssetController extends Controller
{
    private const FUNDING_SOURCES = [
        'apbd_kota', 'apbd_provinsi', 'apbn', 'bos_bosp', 'hibah',
        'bantuan_pemerintah', 'swadaya', 'lainnya',
    ];

    private const ACQUISITION_METHODS = ['pembelian', 'bantuan', 'hibah', 'transfer', 'donasi', 'lainnya'];

    private const OWNER_ENTITIES = [
        'pemkot', 'pemprov', 'pemerintah_pusat', 'yayasan', 'pihak_lain', 'belum_ditentukan',
    ];

    private const DOCUMENT_KINDS = [
        'bukti_pembelian', 'bast', 'bapp', 'dokumen_hibah', 'dokumen_bantuan',
        'kontrak_pesanan', 'dokumen_pendukung',
    ];

    public function index(Request $request): View
    {
        $school = $this->currentSchoolOrFail();
        $query = Asset::query()
            ->where('school_id', $school->id)
            ->with(['type.category', 'acquisition.documents'])
            ->withCount(['changeRequests as pending_change_requests_count' => fn ($builder) => $builder->where('status', 'pending')]);

        if ($search = trim((string) $request->query('q', ''))) {
            $query->where(function ($builder) use ($search) {
                $builder->where('asset_code', 'like', "%{$search}%")
                    ->orWhere('name', 'like', "%{$search}%")
                    ->orWhere('serial_number', 'like', "%{$search}%")
                    ->orWhere('location', 'like', "%{$search}%");
            });
        }

        if ($category = $request->query('category')) {
            $query->whereHas('type.category', fn ($builder) => $builder->where('code', $category));
        }

        if ($status = $request->query('status')) {
            $query->where('status', $status);
        }

        $assets = $query->latest()->paginate(15)->withQueryString();

        return view('pages.school.assets.index', [
            'school' => $school,
            'assets' => $assets,
            'categories' => AssetCategory::query()->orderBy('code')->get(),
            'assetTypes' => AssetType::query()->where('is_active', true)->with('category')->orderBy('name')->get(),
            'revisionAcquisitions' => AssetAcquisition::query()
                ->where('school_id', $school->id)
                ->where('ownership_status', AssetAcquisition::STATUS_NEEDS_REVISION)
                ->with(['documents', 'assets'])
                ->latest()
                ->get(),
            'summary' => [
                'total' => Asset::query()->where('school_id', $school->id)->count(),
                'pending' => Asset::query()->where('school_id', $school->id)->where('status', Asset::STATUS_PENDING)->count(),
                'approved' => Asset::query()->where('school_id', $school->id)->where('status', Asset::STATUS_APPROVED)->count(),
                'needs_revision' => Asset::query()->where('school_id', $school->id)->where('status', Asset::STATUS_NEEDS_REVISION)->count(),
            ],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $school = $this->currentSchoolOrFail();

        $data = $request->validate([
            'funding_source' => ['required', Rule::in(self::FUNDING_SOURCES)],
            'acquisition_method' => ['required', Rule::in(self::ACQUISITION_METHODS)],
            'owner_entity' => ['required', Rule::in(self::OWNER_ENTITIES)],
            'provider_name' => ['nullable', 'string', 'max:255'],
            'document_reference' => ['nullable', 'string', 'max:255'],
            'acquired_at' => ['nullable', 'date', 'before_or_equal:today'],
            'total_value' => ['nullable', 'numeric', 'min:0', 'max:9999999999999.99'],
            'acquisition_notes' => ['nullable', 'string', 'max:5000'],
            'items' => ['required', 'array', 'min:1', 'max:50'],
            'items.*.asset_code' => ['required', 'string', 'max:80', 'regex:/^[A-Za-z0-9][A-Za-z0-9._\/-]*$/'],
            'items.*.name' => ['required', 'string', 'max:255'],
            'items.*.asset_type_id' => ['required', Rule::exists('asset_types', 'id')->where('is_active', true)],
            'items.*.brand_model' => ['nullable', 'string', 'max:255'],
            'items.*.serial_number' => ['nullable', 'string', 'max:160'],
            'items.*.location' => ['nullable', 'string', 'max:255'],
            'items.*.condition' => ['required', Rule::in(['baik', 'rusak_ringan', 'rusak_berat', 'tidak_diketahui'])],
            'items.*.year_acquired' => ['nullable', 'integer', 'min:1900', 'max:'.(now()->year + 1)],
            'items.*.notes' => ['nullable', 'string', 'max:3000'],
            'documents' => ['required', 'array', 'min:1', 'max:10'],
            'documents.*.kind' => ['required', Rule::in(self::DOCUMENT_KINDS)],
            'documents.*.file' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png,webp', 'max:10240'],
        ], [
            'items.*.asset_code.regex' => 'Kode aset hanya boleh berisi huruf, angka, titik, garis bawah, garis miring, dan tanda hubung.',
            'documents.*.file.mimes' => 'Dokumen harus berformat PDF, JPG, PNG, atau WEBP.',
            'documents.*.file.max' => 'Ukuran setiap dokumen maksimal 10 MB.',
        ]);

        $this->assertUniqueAssetIdentifiers($school->id, $data['items']);

        $storedPaths = [];

        try {
            DB::transaction(function () use ($data, $school, $request, &$storedPaths): void {
                $acquisition = AssetAcquisition::query()->create([
                    'school_id' => $school->id,
                    'created_by' => $request->user()->id,
                    'funding_source' => $data['funding_source'],
                    'acquisition_method' => $data['acquisition_method'],
                    'owner_entity' => $data['owner_entity'],
                    'provider_name' => $data['provider_name'] ?? null,
                    'document_reference' => $data['document_reference'] ?? null,
                    'acquired_at' => $data['acquired_at'] ?? null,
                    'total_value' => $data['total_value'] ?? null,
                    'notes' => $data['acquisition_notes'] ?? null,
                    'ownership_status' => AssetAcquisition::STATUS_PENDING,
                ]);

                foreach ($data['items'] as $item) {
                    $asset = Asset::query()->create([
                        'school_id' => $school->id,
                        'asset_acquisition_id' => $acquisition->id,
                        'asset_type_id' => $item['asset_type_id'],
                        'submitted_by' => $request->user()->id,
                        'asset_code' => strtoupper(trim($item['asset_code'])),
                        'name' => trim($item['name']),
                        'brand_model' => $item['brand_model'] ?? null,
                        'serial_number' => isset($item['serial_number']) && trim($item['serial_number']) !== ''
                            ? mb_strtoupper(trim($item['serial_number'])) : null,
                        'location' => $item['location'] ?? null,
                        'condition' => $item['condition'],
                        'year_acquired' => $item['year_acquired'] ?? null,
                        'notes' => $item['notes'] ?? null,
                        'status' => Asset::STATUS_PENDING,
                    ]);

                    AssetEvent::query()->create([
                        'asset_id' => $asset->id,
                        'asset_acquisition_id' => $acquisition->id,
                        'actor_id' => $request->user()->id,
                        'event_type' => 'asset_submitted',
                        'details' => [
                            'asset_code' => $asset->asset_code,
                            'current' => $asset->only([
                                'asset_code', 'name', 'asset_type_id', 'brand_model',
                                'serial_number', 'location', 'condition', 'year_acquired', 'notes',
                            ]),
                            'status' => Asset::STATUS_PENDING,
                        ],
                        'created_at' => now(),
                    ]);
                }

                foreach ($data['documents'] as $document) {
                    $file = $document['file'];
                    $path = $file->store("assets/acquisitions/{$acquisition->id}", 'local');

                    if ($path === false) {
                        throw new \RuntimeException('Dokumen gagal disimpan.');
                    }

                    $storedPaths[] = $path;

                    $storedDocument = AssetDocument::query()->create([
                        'asset_acquisition_id' => $acquisition->id,
                        'uploaded_by' => $request->user()->id,
                        'document_kind' => $document['kind'],
                        'original_name' => basename(str_replace('\\', '/', $file->getClientOriginalName())),
                        'storage_path' => $path,
                        'mime_type' => $file->getMimeType(),
                        'size_bytes' => $file->getSize(),
                    ]);

                    AssetEvent::query()->create([
                        'asset_acquisition_id' => $acquisition->id,
                        'actor_id' => $request->user()->id,
                        'event_type' => 'document_uploaded',
                        'details' => [
                            'document_id' => $storedDocument->id,
                            'document_kind' => $storedDocument->document_kind,
                            'original_name' => $storedDocument->original_name,
                        ],
                        'created_at' => now(),
                    ]);
                }

                AssetEvent::query()->create([
                    'asset_acquisition_id' => $acquisition->id,
                    'actor_id' => $request->user()->id,
                    'event_type' => 'acquisition_submitted',
                    'details' => ['asset_count' => count($data['items']), 'document_count' => count($data['documents'])],
                    'created_at' => now(),
                ]);
            });
        } catch (QueryException $exception) {
            foreach ($storedPaths as $path) {
                Storage::disk('local')->delete($path);
            }

            if (str_contains($exception->getMessage(), 'Duplicate entry') || str_contains($exception->getMessage(), 'UNIQUE constraint failed')) {
                throw ValidationException::withMessages([
                    'items' => 'Kode aset atau nomor seri sudah digunakan pada sekolah ini. Periksa kembali data sebelum menyimpan.',
                ]);
            }

            throw $exception;
        } catch (\Throwable $exception) {
            foreach ($storedPaths as $path) {
                Storage::disk('local')->delete($path);
            }

            throw $exception;
        }

        return redirect()->route('school.assets.index')
            ->with('status', 'Kelompok perolehan dan aset berhasil dicatat. Statusnya menunggu pemeriksaan Dinas Pendidikan.');
    }

    public function editAcquisition(AssetAcquisition $acquisition): View
    {
        $school = $this->currentSchoolOrFail();
        abort_unless((int) $acquisition->school_id === (int) $school->id, 404);
        abort_unless($acquisition->ownership_status === AssetAcquisition::STATUS_NEEDS_REVISION, 404);

        $acquisition->load(['assets.type.category', 'documents']);

        return view('pages.school.assets.resubmit', [
            'school' => $school,
            'acquisition' => $acquisition,
            'assetTypes' => AssetType::query()->with('category')->orderBy('name')->get(),
        ]);
    }

    public function resubmitAcquisition(Request $request, AssetAcquisition $acquisition): RedirectResponse
    {
        $school = $this->currentSchoolOrFail();
        abort_unless((int) $acquisition->school_id === (int) $school->id, 404);
        abort_unless($acquisition->ownership_status === AssetAcquisition::STATUS_NEEDS_REVISION, 404);

        $data = $request->validate([
            'funding_source' => ['required', Rule::in(self::FUNDING_SOURCES)],
            'acquisition_method' => ['required', Rule::in(self::ACQUISITION_METHODS)],
            'owner_entity' => ['required', Rule::in(self::OWNER_ENTITIES)],
            'provider_name' => ['nullable', 'string', 'max:255'],
            'document_reference' => ['nullable', 'string', 'max:255'],
            'acquired_at' => ['nullable', 'date', 'before_or_equal:today'],
            'total_value' => ['nullable', 'numeric', 'min:0', 'max:9999999999999.99'],
            'acquisition_notes' => ['nullable', 'string', 'max:5000'],
            'items' => ['required', 'array', 'min:1', 'max:50'],
            'items.*.id' => [
                'required',
                'integer',
                'distinct',
                Rule::exists('assets', 'id')->where(fn ($query) => $query
                    ->where('asset_acquisition_id', $acquisition->id)
                    ->where('school_id', $school->id)),
            ],
            'items.*.asset_code' => ['required', 'string', 'max:80', 'regex:/^[A-Za-z0-9][A-Za-z0-9._\/-]*$/'],
            'items.*.name' => ['required', 'string', 'max:255'],
            'items.*.asset_type_id' => ['required', 'exists:asset_types,id'],
            'items.*.brand_model' => ['nullable', 'string', 'max:255'],
            'items.*.serial_number' => ['nullable', 'string', 'max:160'],
            'items.*.location' => ['nullable', 'string', 'max:255'],
            'items.*.condition' => ['required', Rule::in(['baik', 'rusak_ringan', 'rusak_berat', 'tidak_diketahui'])],
            'items.*.year_acquired' => ['nullable', 'integer', 'min:1900', 'max:'.(now()->year + 1)],
            'items.*.notes' => ['nullable', 'string', 'max:3000'],
            'documents' => ['sometimes', 'array', 'max:10'],
            'documents.*.kind' => ['required_with:documents', Rule::in(self::DOCUMENT_KINDS)],
            'documents.*.file' => ['required_with:documents', 'file', 'mimes:pdf,jpg,jpeg,png,webp', 'max:10240'],
        ]);

        $expectedIds = $acquisition->assets()->pluck('id')->map(fn ($id) => (int) $id)->sort()->values()->all();
        $providedIds = collect($data['items'])->pluck('id')->map(fn ($id) => (int) $id)->sort()->values()->all();

        if ($expectedIds !== $providedIds) {
            throw ValidationException::withMessages([
                'items' => 'Semua unit aset dalam kelompok harus dikirim kembali. Periksa daftar unit dan coba lagi.',
            ]);
        }

        $this->assertUniqueResubmissionIdentifiers($school->id, $data['items']);

        $storedPaths = [];

        try {
            DB::transaction(function () use ($data, $request, $acquisition, &$storedPaths): void {
                $locked = AssetAcquisition::query()->lockForUpdate()->findOrFail($acquisition->id);
                abort_unless($locked->ownership_status === AssetAcquisition::STATUS_NEEDS_REVISION, 409);

                $previousAcquisition = $locked->only([
                    'funding_source', 'acquisition_method', 'owner_entity', 'provider_name',
                    'document_reference', 'acquired_at', 'total_value', 'notes',
                ]);

                $locked->update([
                    'funding_source' => $data['funding_source'],
                    'acquisition_method' => $data['acquisition_method'],
                    'owner_entity' => $data['owner_entity'],
                    'provider_name' => $data['provider_name'] ?? null,
                    'document_reference' => $data['document_reference'] ?? null,
                    'acquired_at' => $data['acquired_at'] ?? null,
                    'total_value' => $data['total_value'] ?? null,
                    'notes' => $data['acquisition_notes'] ?? null,
                    'ownership_status' => AssetAcquisition::STATUS_PENDING,
                    'reviewed_by' => null,
                    'reviewed_at' => null,
                    'review_note' => null,
                ]);

                foreach ($data['documents'] ?? [] as $document) {
                    $file = $document['file'];
                    $path = $file->store("assets/acquisitions/{$locked->id}", 'local');
                    if ($path === false) {
                        throw new \RuntimeException('Dokumen gagal disimpan.');
                    }
                    $storedPaths[] = $path;
                    $storedDocument = AssetDocument::query()->create([
                        'asset_acquisition_id' => $locked->id,
                        'uploaded_by' => $request->user()->id,
                        'document_kind' => $document['kind'],
                        'original_name' => basename(str_replace('\\', '/', $file->getClientOriginalName())),
                        'storage_path' => $path,
                        'mime_type' => $file->getMimeType(),
                        'size_bytes' => $file->getSize(),
                    ]);

                    AssetEvent::query()->create([
                        'asset_acquisition_id' => $locked->id,
                        'actor_id' => $request->user()->id,
                        'event_type' => 'document_uploaded',
                        'details' => [
                            'document_id' => $storedDocument->id,
                            'document_kind' => $storedDocument->document_kind,
                            'original_name' => $storedDocument->original_name,
                        ],
                        'created_at' => now(),
                    ]);
                }

                $lockedAssets = $locked->assets()->get()->keyBy('id');

                foreach ($data['items'] as $item) {
                    $asset = $lockedAssets->get((int) $item['id']);
                    abort_unless($asset, 404);
                    $previousAsset = $asset->only([
                        'asset_code', 'name', 'asset_type_id', 'brand_model',
                        'serial_number', 'location', 'condition', 'year_acquired', 'notes',
                    ]);

                    $asset->update([
                        'asset_code' => strtoupper(trim($item['asset_code'])),
                        'name' => trim($item['name']),
                        'asset_type_id' => $item['asset_type_id'],
                        'brand_model' => $item['brand_model'] ?? null,
                        'serial_number' => isset($item['serial_number']) && trim($item['serial_number']) !== ''
                            ? mb_strtoupper(trim($item['serial_number'])) : null,
                        'location' => $item['location'] ?? null,
                        'condition' => $item['condition'],
                        'year_acquired' => $item['year_acquired'] ?? null,
                        'notes' => $item['notes'] ?? null,
                        'status' => Asset::STATUS_PENDING,
                        'reviewed_by' => null,
                        'reviewed_at' => null,
                        'review_note' => null,
                    ]);

                    AssetEvent::query()->create([
                        'asset_id' => $asset->id,
                        'asset_acquisition_id' => $locked->id,
                        'actor_id' => $request->user()->id,
                        'event_type' => 'asset_resubmitted',
                        'details' => [
                            'previous' => $previousAsset,
                            'current' => $asset->only([
                                'asset_code', 'name', 'asset_type_id', 'brand_model',
                                'serial_number', 'location', 'condition', 'year_acquired', 'notes',
                            ]),
                            'status' => Asset::STATUS_PENDING,
                        ],
                        'created_at' => now(),
                    ]);
                }

                AssetEvent::query()->create([
                    'asset_acquisition_id' => $locked->id,
                    'actor_id' => $request->user()->id,
                    'event_type' => 'acquisition_resubmitted',
                    'details' => [
                        'previous' => $previousAcquisition,
                        'current' => $locked->only([
                            'funding_source', 'acquisition_method', 'owner_entity', 'provider_name',
                            'document_reference', 'acquired_at', 'total_value', 'notes',
                        ]),
                        'document_count' => count($data['documents'] ?? []),
                    ],
                    'created_at' => now(),
                ]);
            });
        } catch (QueryException $exception) {
            foreach ($storedPaths as $path) {
                Storage::disk('local')->delete($path);
            }

            if (str_contains($exception->getMessage(), 'Duplicate entry') || str_contains($exception->getMessage(), 'UNIQUE constraint failed')) {
                throw ValidationException::withMessages([
                    'items' => 'Kode aset atau nomor seri sudah digunakan pada sekolah ini. Periksa kembali data sebelum mengirim ulang.',
                ]);
            }

            throw $exception;
        } catch (\Throwable $exception) {
            foreach ($storedPaths as $path) {
                Storage::disk('local')->delete($path);
            }
            throw $exception;
        }
        return redirect()->route('school.assets.index')
            ->with('status', 'Perbaikan perolehan berhasil dikirim ulang untuk diperiksa.');
    }

    public function storeChangeRequest(Request $request, Asset $asset): RedirectResponse
    {
        $school = $this->currentSchoolOrFail();
        abort_unless((int) $asset->school_id === (int) $school->id, 404);
        abort_unless($asset->status === Asset::STATUS_APPROVED, 422, 'Aset harus disetujui sebelum mengajukan perubahan.');

        $data = $request->validate([
            'condition' => ['required', Rule::in(['baik', 'rusak_ringan', 'rusak_berat', 'tidak_diketahui'])],
            'location' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:3000'],
            'request_note' => ['required', 'string', 'max:2000'],
        ]);

        $proposed = [
            'condition' => $data['condition'],
            'location' => $data['location'] ?? null,
            'notes' => $data['notes'] ?? null,
        ];

        DB::transaction(function () use ($request, $asset, $school, $proposed, $data): void {
            $lockedAsset = Asset::query()->whereKey($asset->id)->lockForUpdate()->firstOrFail();
            abort_unless((int) $lockedAsset->school_id === (int) $school->id, 404);
            abort_unless($lockedAsset->status === Asset::STATUS_APPROVED, 409, 'Status aset berubah; muat ulang sebelum mengajukan perubahan.');

            if ($lockedAsset->changeRequests()->where('status', 'pending')->exists()) {
                throw ValidationException::withMessages([
                    'asset' => 'Masih ada pengajuan perubahan untuk aset ini yang menunggu pemeriksaan.',
                ]);
            }

            $changeRequest = AssetChangeRequest::query()->create([
                'asset_id' => $lockedAsset->id,
                'submitted_by' => $request->user()->id,
                'proposed_changes' => $proposed,
                'request_note' => $data['request_note'],
                'status' => 'pending',
            ]);

            AssetEvent::query()->create([
                'asset_id' => $lockedAsset->id,
                'asset_acquisition_id' => $lockedAsset->asset_acquisition_id,
                'actor_id' => $request->user()->id,
                'event_type' => 'change_requested',
                'details' => [
                    'change_request_id' => $changeRequest->id,
                    'proposed' => $proposed,
                    'request_note' => $data['request_note'],
                ],
                'created_at' => now(),
            ]);
        });

        return redirect()->route('school.assets.index')
            ->with('status', 'Pengajuan perubahan disimpan. Data resmi tidak berubah sebelum diperiksa Dinas.');
    }

    public function downloadDocument(AssetDocument $document)
    {
        $user = request()->user();
        abort_unless($user, 403);

        if ($user->role !== 'admin') {
            $document->loadMissing('acquisition');
            abort_unless(
                $user->role === 'operator_sekolah'
                && $user->school_id !== null
                && (int) $document->acquisition->school_id === (int) $user->school_id,
                404
            );
        }

        $disk = Storage::disk('local');
        abort_unless($disk->exists($document->storage_path), 404);

        return $disk->download($document->storage_path, $document->original_name, [
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }


    private function assertUniqueResubmissionIdentifiers(int $schoolId, array $items): void
    {
        $codes = [];
        $serials = [];

        foreach ($items as $index => $item) {
            $assetId = (int) $item['id'];
            $code = mb_strtoupper(trim($item['asset_code']));

            if (isset($codes[$code])) {
                throw ValidationException::withMessages([
                    "items.{$index}.asset_code" => 'Kode aset duplikat ditemukan dalam kelompok ini.',
                ]);
            }
            $codes[$code] = true;

            if (Asset::query()->where('school_id', $schoolId)->where('asset_code', $code)->where('id', '<>', $assetId)->exists()) {
                throw ValidationException::withMessages([
                    "items.{$index}.asset_code" => 'Kode aset sudah digunakan oleh unit lain di sekolah ini.',
                ]);
            }

            $serial = mb_strtoupper(trim((string) ($item['serial_number'] ?? '')));
            if ($serial !== '') {
                if (isset($serials[$serial])) {
                    throw ValidationException::withMessages([
                        "items.{$index}.serial_number" => 'Nomor seri duplikat ditemukan dalam kelompok ini.',
                    ]);
                }
                $serials[$serial] = true;

                if (Asset::query()->where('school_id', $schoolId)->where('serial_number', $serial)->where('id', '<>', $assetId)->exists()) {
                    throw ValidationException::withMessages([
                        "items.{$index}.serial_number" => 'Nomor seri sudah digunakan oleh unit lain di sekolah ini.',
                    ]);
                }
            }
        }
    }

    private function currentSchoolOrFail()
    {
        $user = request()->user();
        abort_unless($user?->role === 'operator_sekolah' && $user->school_id, 403);

        return $user->school()->firstOrFail();
    }

    private function assertUniqueAssetIdentifiers(int $schoolId, array $items): void
    {
        $codes = [];
        $serials = [];

        foreach ($items as $index => $item) {
            $code = mb_strtoupper(trim($item['asset_code']));
            if (isset($codes[$code])) {
                throw ValidationException::withMessages([
                    "items.{$index}.asset_code" => 'Kode aset duplikat ditemukan dalam formulir ini.',
                ]);
            }
            $codes[$code] = true;

            $serial = trim((string) ($item['serial_number'] ?? ''));
            if ($serial !== '') {
                $normalizedSerial = mb_strtoupper($serial);
                if (isset($serials[$normalizedSerial])) {
                    throw ValidationException::withMessages([
                        "items.{$index}.serial_number" => 'Nomor seri duplikat ditemukan dalam formulir ini.',
                    ]);
                }
                $serials[$normalizedSerial] = true;
            }
        }

        if (Asset::query()->where('school_id', $schoolId)->whereIn('asset_code', array_keys($codes))->exists()) {
            throw ValidationException::withMessages([
                'items' => 'Salah satu kode aset sudah terdaftar pada sekolah ini.',
            ]);
        }

        if ($serials && Asset::query()->where('school_id', $schoolId)->whereIn('serial_number', array_keys($serials))->exists()) {
            throw ValidationException::withMessages([
                'items' => 'Salah satu nomor seri sudah terdaftar pada sekolah ini.',
            ]);
        }
    }
}