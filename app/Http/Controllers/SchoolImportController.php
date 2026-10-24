<?php

namespace App\Http\Controllers;

use App\Models\School;
use App\Models\SchoolImportLog;
use App\Services\SchoolImportService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use Illuminate\View\View;
use RuntimeException;
use Throwable;

class SchoolImportController extends Controller
{
    public function index(): View
    {
        return view('pages.admin.schools.import', [
            'logs' => SchoolImportLog::query()->with('uploadedBy')->latest()->paginate(8),
            'schoolCount' => School::query()->count(),
        ]);
    }

    public function preview(Request $request, SchoolImportService $importer): RedirectResponse
    {
        Session::forget('school_import_preview');

        $request->validate([
            'file' => ['required', 'file', 'mimes:json', 'max:10240'],
        ]);

        $file = $request->file('file');

        try {
            $payload = json_decode(
                file_get_contents($file->getRealPath()),
                true,
                512,
                JSON_THROW_ON_ERROR,
            );
        } catch (Throwable) {
            return back()->withErrors(['file' => 'File tidak berisi JSON yang valid atau tidak dapat dibaca.']);
        }

        if (! is_array($payload)) {
            return back()->withErrors(['file' => 'Struktur JSON harus berupa objek yang memiliki properti data.']);
        }

        $preview = $importer->preview($payload);
        $preview['file_name'] = basename($file->getClientOriginalName());

        Session::put('school_import_preview', $preview);

        return redirect()
            ->route('admin.schools.import.index')
            ->with('status', 'Pratinjau impor berhasil disiapkan. Periksa peringatan sebelum mengonfirmasi.');
    }

    public function confirm(Request $request, SchoolImportService $importer): RedirectResponse
    {
        $preview = Session::get('school_import_preview');

        if (! is_array($preview) || ! empty($preview['errors']) || empty($preview['rows'])) {
            return redirect()
                ->route('admin.schools.import.index')
                ->withErrors(['file' => 'Pratinjau tidak tersedia atau memiliki kesalahan. Unggah dan periksa file kembali.']);
        }

        try {
            $result = $importer->import(
                $preview['rows'],
                $request->user(),
                $preview['file_name'] ?? 'schools.json',
            );
        } catch (RuntimeException $exception) {
            return redirect()
                ->route('admin.schools.import.index')
                ->withErrors(['file' => $exception->getMessage()]);
        } catch (Throwable $exception) {
            report($exception);

            return redirect()
                ->route('admin.schools.import.index')
                ->withErrors(['file' => 'Impor gagal dan perubahan dibatalkan. Periksa log aplikasi sebelum mencoba lagi.']);
        }

        Session::forget('school_import_preview');

        return redirect()
            ->route('admin.schools.import.index')
            ->with('import_result', $result);
    }

    public function cancel(): RedirectResponse
    {
        Session::forget('school_import_preview');

        return redirect()->route('admin.schools.import.index')->with('status', 'Pratinjau impor dibatalkan.');
    }
}
