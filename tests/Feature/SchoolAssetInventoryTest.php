<?php

use App\Models\Asset;
use App\Models\AssetAcquisition;
use App\Models\AssetAcquisitionReview;
use App\Models\AssetChangeRequest;
use App\Models\AssetDocument;
use App\Models\AssetEvent;
use App\Models\AssetType;
use App\Models\School;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

function createSchoolForAssetInventoryTest(string $npsn, string $name): School
{
    return School::query()->create([
        'npsn' => $npsn,
        'nama' => $name,
        'bentuk_pendidikan' => 'SMP',
        'status_sekolah' => 'Negeri',
        'coordinate_status' => School::COORDINATE_MISSING,
    ]);
}

function createAssetAcquisitionForInventoryTest(School $school, User $actor, array $attributes = []): AssetAcquisition
{
    return AssetAcquisition::query()->create(array_merge([
        'school_id' => $school->id,
        'created_by' => $actor->id,
        'funding_source' => 'bantuan_pemerintah',
        'acquisition_method' => 'bantuan',
        'owner_entity' => 'belum_ditentukan',
        'ownership_status' => AssetAcquisition::STATUS_PENDING,
    ], $attributes));
}

function createAssetForInventoryTest(School $school, User $actor, AssetAcquisition $acquisition, array $attributes = []): Asset
{
    return Asset::query()->create(array_merge([
        'school_id' => $school->id,
        'asset_acquisition_id' => $acquisition->id,
        'asset_type_id' => AssetType::query()->where('name', 'Laptop')->value('id'),
        'submitted_by' => $actor->id,
        'asset_code' => 'INV-'.str_pad((string) random_int(1, 99999), 5, '0', STR_PAD_LEFT),
        'name' => 'Laptop pembelajaran',
        'condition' => 'baik',
        'status' => Asset::STATUS_PENDING,
    ], $attributes));
}

test('operator creates individual assets linked to the logged-in school and private acquisition documents', function () {
    Storage::fake('local');

    $school = createSchoolForAssetInventoryTest('91000001', 'SMP Sekolah A');
    $operator = User::factory()->create(['role' => 'operator_sekolah', 'school_id' => $school->id]);
    $typeId = AssetType::query()->where('name', 'Laptop')->value('id');

    $this->actingAs($operator)->post(route('school.assets.store'), [
        'funding_source' => 'bantuan_pemerintah',
        'acquisition_method' => 'bantuan',
        'owner_entity' => 'belum_ditentukan',
        'provider_name' => 'Program bantuan pendidikan',
        'document_reference' => 'BAST-001',
        'items' => [[
            'asset_code' => 'inv-laptop-001',
            'name' => 'Laptop pembelajaran',
            'asset_type_id' => $typeId,
            'serial_number' => 'sn-abc-001',
            'location' => 'Lab komputer',
            'condition' => 'baik',
            'year_acquired' => now()->year,
        ]],
        'documents' => [[
            'kind' => 'bast',
            'file' => UploadedFile::fake()->create('BAST.pdf', 80, 'application/pdf'),
        ]],
    ])->assertRedirect(route('school.assets.index'))
      ->assertSessionHas('status');

    $asset = Asset::query()->sole();
    $acquisition = AssetAcquisition::query()->sole();
    $document = AssetDocument::query()->sole();

    expect($asset->school_id)->toBe($school->id)
        ->and($asset->submitted_by)->toBe($operator->id)
        ->and($asset->asset_code)->toBe('INV-LAPTOP-001')
        ->and($asset->serial_number)->toBe('SN-ABC-001')
        ->and($asset->status)->toBe(Asset::STATUS_PENDING)
        ->and($acquisition->ownership_status)->toBe(AssetAcquisition::STATUS_PENDING)
        ->and($document->asset_acquisition_id)->toBe($acquisition->id)
        ->and($document->storage_path)->not->toContain('public')
        ->and(Storage::disk('local')->exists($document->storage_path))->toBeTrue()
        ->and(AssetEvent::query()->where('event_type', 'asset_submitted')->count())->toBe(1)
        ->and(AssetEvent::query()->where('event_type', 'acquisition_submitted')->count())->toBe(1);
});

test('duplicate inventory codes inside one submission are rejected before records are created', function () {
    Storage::fake('local');

    $school = createSchoolForAssetInventoryTest('91000002', 'SMP Sekolah Duplikat');
    $operator = User::factory()->create(['role' => 'operator_sekolah', 'school_id' => $school->id]);
    $typeId = AssetType::query()->where('name', 'Laptop')->value('id');
    $baseItem = [
        'asset_code' => 'INV-DUP-001',
        'name' => 'Laptop pembelajaran',
        'asset_type_id' => $typeId,
        'condition' => 'baik',
    ];

    $this->actingAs($operator)->from(route('school.assets.index'))->post(route('school.assets.store'), [
        'funding_source' => 'apbd_kota',
        'acquisition_method' => 'pembelian',
        'owner_entity' => 'pemkot',
        'items' => [$baseItem, $baseItem],
        'documents' => [[
            'kind' => 'bukti_pembelian',
            'file' => UploadedFile::fake()->create('bukti.pdf', 40, 'application/pdf'),
        ]],
    ])->assertRedirect(route('school.assets.index'))
      ->assertSessionHasErrors('items.1.asset_code');

    expect(Asset::query()->count())->toBe(0)
        ->and(AssetAcquisition::query()->count())->toBe(0)
        ->and(AssetDocument::query()->count())->toBe(0);
});

test('operator can only see assets linked to their own school', function () {
    $schoolA = createSchoolForAssetInventoryTest('91000003', 'SMP Sekolah A');
    $schoolB = createSchoolForAssetInventoryTest('91000004', 'SMP Sekolah B');
    $operator = User::factory()->create(['role' => 'operator_sekolah', 'school_id' => $schoolA->id]);
    $otherOperator = User::factory()->create(['role' => 'operator_sekolah', 'school_id' => $schoolB->id]);

    $acquisitionA = createAssetAcquisitionForInventoryTest($schoolA, $operator);
    $acquisitionB = createAssetAcquisitionForInventoryTest($schoolB, $otherOperator);

    createAssetForInventoryTest($schoolA, $operator, $acquisitionA, ['asset_code' => 'ONLY-SCHOOL-A']);
    createAssetForInventoryTest($schoolB, $otherOperator, $acquisitionB, ['asset_code' => 'ONLY-SCHOOL-B']);

    $this->actingAs($operator)->get(route('school.assets.index'))
        ->assertOk()
        ->assertSee('ONLY-SCHOOL-A')
        ->assertDontSee('ONLY-SCHOOL-B');
});

test('operator cannot download another school acquisition document', function () {
    Storage::fake('local');

    $schoolA = createSchoolForAssetInventoryTest('91000005', 'SMP Sekolah A');
    $schoolB = createSchoolForAssetInventoryTest('91000006', 'SMP Sekolah B');
    $operatorA = User::factory()->create(['role' => 'operator_sekolah', 'school_id' => $schoolA->id]);
    $operatorB = User::factory()->create(['role' => 'operator_sekolah', 'school_id' => $schoolB->id]);
    $acquisitionB = createAssetAcquisitionForInventoryTest($schoolB, $operatorB);
    $path = 'assets/acquisitions/private-evidence.pdf';
    Storage::disk('local')->put($path, 'private-test-content');

    $document = AssetDocument::query()->create([
        'asset_acquisition_id' => $acquisitionB->id,
        'uploaded_by' => $operatorB->id,
        'document_kind' => 'bast',
        'original_name' => 'bast.pdf',
        'storage_path' => $path,
        'mime_type' => 'application/pdf',
        'size_bytes' => 21,
    ]);

    $this->actingAs($operatorA)
        ->get(route('school.assets.documents.download', $document))
        ->assertNotFound();
});

test('admin review records BMD ownership separately from the school and asset status', function () {
    $school = createSchoolForAssetInventoryTest('91000007', 'SMP Sekolah Verifikasi');
    $operator = User::factory()->create(['role' => 'operator_sekolah', 'school_id' => $school->id]);
    $admin = User::factory()->create(['role' => 'admin', 'school_id' => null]);
    $acquisition = createAssetAcquisitionForInventoryTest($school, $operator);
    $asset = createAssetForInventoryTest($school, $operator, $acquisition);

    $this->actingAs($admin)->post(route('admin.assets.acquisitions.review', $acquisition), [
        'decision' => AssetAcquisition::STATUS_VERIFIED_BMD,
        'verified_owner_entity' => 'pemkot',
        'review_note' => 'BAST dan dokumen kepemilikan telah diperiksa.',
    ])->assertRedirect(route('admin.assets.index', ['tab' => 'perolehan']));

    expect($acquisition->refresh()->ownership_status)->toBe(AssetAcquisition::STATUS_VERIFIED_BMD)
        ->and($asset->refresh()->status)->toBe(Asset::STATUS_APPROVED)
        ->and($asset->reviewed_by)->toBe($admin->id)
        ->and($acquisition->owner_entity)->toBe('pemkot')
        ->and(AssetAcquisitionReview::query()->where('asset_acquisition_id', $acquisition->id)->sole()->owner_entity_after)->toBe('pemkot')
        ->and(AssetEvent::query()->where('asset_id', $asset->id)->where('event_type', 'asset_reviewed')->count())->toBe(1);
});

test('approved asset changes remain pending until an admin approves them', function () {
    $school = createSchoolForAssetInventoryTest('91000008', 'SMP Sekolah Perubahan');
    $operator = User::factory()->create(['role' => 'operator_sekolah', 'school_id' => $school->id]);
    $admin = User::factory()->create(['role' => 'admin', 'school_id' => null]);
    $acquisition = createAssetAcquisitionForInventoryTest($school, $operator, [
        'ownership_status' => AssetAcquisition::STATUS_VERIFIED_BMD,
        'reviewed_by' => $admin->id,
        'reviewed_at' => now(),
    ]);
    $asset = createAssetForInventoryTest($school, $operator, $acquisition, [
        'asset_code' => 'CHANGE-001',
        'status' => Asset::STATUS_APPROVED,
        'location' => 'Ruang A',
    ]);

    $this->actingAs($operator)->post(route('school.assets.changes.store', $asset), [
        'condition' => 'rusak_ringan',
        'location' => 'Ruang B',
        'notes' => 'Layar bermasalah.',
        'request_note' => 'Perubahan kondisi setelah pemeriksaan rutin.',
    ])->assertRedirect(route('school.assets.index'));

    expect($asset->refresh()->condition)->toBe('baik')
        ->and(AssetChangeRequest::query()->sole()->status)->toBe('pending');

    $change = AssetChangeRequest::query()->sole();
    $this->actingAs($admin)->post(route('admin.assets.changes.review', $change), [
        'decision' => 'approved',
        'review_note' => 'Perubahan diperiksa.',
    ])->assertRedirect(route('admin.assets.index', ['tab' => 'perubahan']));

    expect($asset->refresh()->condition)->toBe('rusak_ringan')
        ->and($asset->location)->toBe('Ruang B')
        ->and($change->refresh()->status)->toBe('approved')
        ->and(AssetEvent::query()->where('asset_id', $asset->id)->where('event_type', 'change_approved')->count())->toBe(1);
});


test('operator can correct an existing asset in a returned acquisition without creating a duplicate record', function () {
    Storage::fake('local');

    $school = createSchoolForAssetInventoryTest('91000009', 'SMP Sekolah Revisi');
    $operator = User::factory()->create(['role' => 'operator_sekolah', 'school_id' => $school->id]);
    $admin = User::factory()->create(['role' => 'admin', 'school_id' => null]);
    $acquisition = createAssetAcquisitionForInventoryTest($school, $operator, [
        'ownership_status' => AssetAcquisition::STATUS_NEEDS_REVISION,
        'reviewed_by' => $admin->id,
        'reviewed_at' => now(),
        'review_note' => 'Periksa ulang kode inventaris dan nomor seri.',
    ]);
    $asset = createAssetForInventoryTest($school, $operator, $acquisition, [
        'asset_code' => 'OLD-CODE-001',
        'name' => 'Laptop lama',
        'serial_number' => 'OLD-SERIAL',
        'status' => Asset::STATUS_NEEDS_REVISION,
        'review_note' => 'Kode tidak sesuai dokumen.',
    ]);
    $typeId = AssetType::query()->where('name', 'Laptop')->value('id');

    $this->actingAs($operator)->put(route('school.assets.acquisitions.resubmit', $acquisition), [
        'funding_source' => 'bantuan_pemerintah',
        'acquisition_method' => 'bantuan',
        'owner_entity' => 'pemerintah_pusat',
        'provider_name' => 'Program pusat',
        'document_reference' => 'BAST-REV-001',
        'acquired_at' => now()->toDateString(),
        'items' => [[
            'id' => $asset->id,
            'asset_code' => 'FIXED-CODE-001',
            'name' => 'Laptop pembelajaran',
            'asset_type_id' => $typeId,
            'brand_model' => 'Model X',
            'serial_number' => 'SERIAL-FIXED-001',
            'location' => 'Lab komputer',
            'condition' => 'baik',
            'year_acquired' => now()->year,
            'notes' => 'Data diperbaiki sesuai dokumen.',
        ]],
    ])->assertRedirect(route('school.assets.index'));

    $asset->refresh();
    $acquisition->refresh();

    expect(Asset::query()->count())->toBe(1)
        ->and($asset->id)->toBe($asset->getKey())
        ->and($asset->asset_code)->toBe('FIXED-CODE-001')
        ->and($asset->serial_number)->toBe('SERIAL-FIXED-001')
        ->and($asset->name)->toBe('Laptop pembelajaran')
        ->and($asset->status)->toBe(Asset::STATUS_PENDING)
        ->and($acquisition->ownership_status)->toBe(AssetAcquisition::STATUS_PENDING)
        ->and(AssetEvent::query()->where('asset_id', $asset->id)->where('event_type', 'asset_resubmitted')->count())->toBe(1);
});

test('admin map inventory endpoint returns only assets for the selected school', function () {
    $schoolA = createSchoolForAssetInventoryTest('91000010', 'SMP Peta A');
    $schoolB = createSchoolForAssetInventoryTest('91000011', 'SMP Peta B');
    $operatorA = User::factory()->create(['role' => 'operator_sekolah', 'school_id' => $schoolA->id]);
    $operatorB = User::factory()->create(['role' => 'operator_sekolah', 'school_id' => $schoolB->id]);
    $admin = User::factory()->create(['role' => 'admin', 'school_id' => null]);
    $acquisitionA = createAssetAcquisitionForInventoryTest($schoolA, $operatorA);
    $acquisitionB = createAssetAcquisitionForInventoryTest($schoolB, $operatorB);
    createAssetForInventoryTest($schoolA, $operatorA, $acquisitionA, ['asset_code' => 'MAP-A-001']);
    createAssetForInventoryTest($schoolB, $operatorB, $acquisitionB, ['asset_code' => 'MAP-B-001']);

    $this->actingAs($admin)
        ->getJson(route('admin.assets.school-data', ['school' => $schoolA, 'category' => 'B']))
        ->assertOk()
        ->assertJsonPath('meta.total', 1)
        ->assertJsonPath('data.0.code', 'MAP-A-001')
        ->assertJsonPath('data.0.category', 'B')
        ->assertJsonMissing(['code' => 'MAP-B-001']);
});

test('operator cannot access the admin asset verification queue', function () {
    $school = createSchoolForAssetInventoryTest('91000012', 'SMP Sekolah Operator');
    $operator = User::factory()->create(['role' => 'operator_sekolah', 'school_id' => $school->id]);

    $this->actingAs($operator)->get(route('admin.assets.index'))->assertForbidden();
});


test('admin school inventory endpoint only returns open acquisitions for the selected school', function () {
    $schoolA = createSchoolForAssetInventoryTest('91000020', 'SMP Modal A');
    $schoolB = createSchoolForAssetInventoryTest('91000021', 'SMP Modal B');
    $operatorA = User::factory()->create(['role' => 'operator_sekolah', 'school_id' => $schoolA->id]);
    $operatorB = User::factory()->create(['role' => 'operator_sekolah', 'school_id' => $schoolB->id]);
    $admin = User::factory()->create(['role' => 'admin', 'school_id' => null]);
    $acquisitionA = createAssetAcquisitionForInventoryTest($schoolA, $operatorA, ['document_reference' => 'BAST-MODAL-A']);
    $acquisitionB = createAssetAcquisitionForInventoryTest($schoolB, $operatorB, ['document_reference' => 'BAST-MODAL-B']);
    createAssetForInventoryTest($schoolA, $operatorA, $acquisitionA, ['asset_code' => 'MODAL-A-001']);
    createAssetForInventoryTest($schoolB, $operatorB, $acquisitionB, ['asset_code' => 'MODAL-B-001']);

    $this->actingAs($admin)
        ->getJson(route('admin.assets.school-acquisitions', $schoolA))
        ->assertOk()
        ->assertJsonPath('meta.total', 1)
        ->assertJsonPath('data.0.school_id', $schoolA->id)
        ->assertJsonPath('data.0.document_reference', 'BAST-MODAL-A')
        ->assertJsonPath('data.0.assets.0.code', 'MODAL-A-001')
        ->assertJsonMissing(['code' => 'MODAL-B-001']);
});

test('admin can review an acquisition from the school modal using a JSON response', function () {
    $school = createSchoolForAssetInventoryTest('91000022', 'SMP Modal Review');
    $operator = User::factory()->create(['role' => 'operator_sekolah', 'school_id' => $school->id]);
    $admin = User::factory()->create(['role' => 'admin', 'school_id' => null]);
    $acquisition = createAssetAcquisitionForInventoryTest($school, $operator);
    $asset = createAssetForInventoryTest($school, $operator, $acquisition, ['asset_code' => 'MODAL-REVIEW-001']);

    $this->actingAs($admin)
        ->postJson(route('admin.assets.acquisitions.review', $acquisition), [
            'decision' => AssetAcquisition::STATUS_VERIFIED_BMD,
            'verified_owner_entity' => 'pemkot',
            'review_note' => 'Dokumen diperiksa melalui modal sekolah.',
        ])
        ->assertOk()
        ->assertJsonPath('decision', AssetAcquisition::STATUS_VERIFIED_BMD)
        ->assertJsonPath('acquisition_id', $acquisition->id);

    expect($acquisition->refresh()->ownership_status)->toBe(AssetAcquisition::STATUS_VERIFIED_BMD)
        ->and($asset->refresh()->status)->toBe(Asset::STATUS_APPROVED);
});

test('operator cannot access the admin notification feed', function () {
    $school = createSchoolForAssetInventoryTest('91000024', 'SMP Notifikasi Terbatas');
    $operator = User::factory()->create(['role' => 'operator_sekolah', 'school_id' => $school->id]);

    $this->actingAs($operator)
        ->getJson(route('admin.notifications'))
        ->assertForbidden();
});

test('admin notification feed includes asset events and current school context', function () {
    $school = createSchoolForAssetInventoryTest('91000023', 'SMP Notifikasi');
    $operator = User::factory()->create(['role' => 'operator_sekolah', 'school_id' => $school->id]);
    $admin = User::factory()->create(['role' => 'admin', 'school_id' => null]);
    $acquisition = createAssetAcquisitionForInventoryTest($school, $operator, ['document_reference' => 'BAST-NOTIF-001']);
    $asset = createAssetForInventoryTest($school, $operator, $acquisition, ['asset_code' => 'NOTIF-001']);
    AssetEvent::query()->create([
        'asset_id' => $asset->id,
        'asset_acquisition_id' => $acquisition->id,
        'actor_id' => $operator->id,
        'event_type' => 'acquisition_submitted',
        'details' => ['asset_count' => 1],
        'created_at' => now(),
    ]);
    AssetEvent::query()->create([
        'asset_id' => $asset->id,
        'asset_acquisition_id' => $acquisition->id,
        'actor_id' => $admin->id,
        'event_type' => 'acquisition_reviewed',
        'details' => ['decision' => 'verified_bmd'],
        'created_at' => now(),
    ]);

    $this->actingAs($admin)
        ->getJson(route('admin.notifications'))
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.school_name', 'SMP Notifikasi')
        ->assertJsonPath('data.0.actor_name', $operator->name)
        ->assertJsonPath('data.0.target', 'asset')
        ->assertJsonPath('meta.pending_count', 1);
});