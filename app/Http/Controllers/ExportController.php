<?php

namespace App\Http\Controllers;

use App\Models\Upload;
use App\Services\DataExporter;
use App\Services\GapAnalyzer;
use Illuminate\Support\Facades\Auth;

class ExportController extends Controller
{
    public function index(Upload $upload, GapAnalyzer $analyzer)
    {
        abort_if($upload->user_id !== Auth::id(), 403);

        if ($upload->cleanedRecords->isEmpty()) {
            return redirect("/uploads/{$upload->id}")
                ->with('error', 'Clean the data first before exporting.');
        }

        $gapAnalysis = $analyzer->analyze($upload);

        return view('export.index', compact('upload', 'gapAnalysis'));
    }

    public function downloadCsv(Upload $upload, DataExporter $exporter)
    {
        abort_if($upload->user_id !== Auth::id(), 403);

        try {
            $csv = $exporter->toCsv($upload);
        } catch (\Exception $e) {
            return redirect("/uploads/{$upload->id}")->with('error', $e->getMessage());
        }

        $filename = 'medbridge-cleaned-' . pathinfo($upload->original_filename, PATHINFO_FILENAME) . '-' . now()->format('Y-m-d') . '.csv';

        return response($csv, 200)
            ->header('Content-Type', 'text/csv')
            ->header('Content-Disposition', 'attachment; filename="' . $filename . '"');
    }

    public function downloadMedProCsv(Upload $upload, DataExporter $exporter)
    {
        abort_if($upload->user_id !== Auth::id(), 403);

        try {
            $csv = $exporter->toMedProCsv($upload);
        } catch (\Exception $e) {
            return redirect("/uploads/{$upload->id}")->with('error', $e->getMessage());
        }

        $filename = 'medpro-import-' . pathinfo($upload->original_filename, PATHINFO_FILENAME) . '-' . now()->format('Y-m-d') . '.csv';

        return response($csv, 200)
            ->header('Content-Type', 'text/csv')
            ->header('Content-Disposition', 'attachment; filename="' . $filename . '"');
    }

    public function downloadJson(Upload $upload, DataExporter $exporter)
    {
        abort_if($upload->user_id !== Auth::id(), 403);

        try {
            $json = $exporter->toJson($upload);
        } catch (\Exception $e) {
            return redirect("/uploads/{$upload->id}")->with('error', $e->getMessage());
        }

        $filename = 'medbridge-export-' . pathinfo($upload->original_filename, PATHINFO_FILENAME) . '-' . now()->format('Y-m-d') . '.json';

        return response($json, 200)
            ->header('Content-Type', 'application/json')
            ->header('Content-Disposition', 'attachment; filename="' . $filename . '"');
    }

    public function downloadReport(Upload $upload, DataExporter $exporter, GapAnalyzer $analyzer)
    {
        abort_if($upload->user_id !== Auth::id(), 403);

        $gapAnalysis = $analyzer->analyze($upload);
        $report = $exporter->toReport($upload, $gapAnalysis);

        $filename = 'medbridge-report-' . pathinfo($upload->original_filename, PATHINFO_FILENAME) . '-' . now()->format('Y-m-d') . '.txt';

        return response($report, 200)
            ->header('Content-Type', 'text/plain')
            ->header('Content-Disposition', 'attachment; filename="' . $filename . '"');
    }
}