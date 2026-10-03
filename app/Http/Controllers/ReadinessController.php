<?php

namespace App\Http\Controllers;

use App\Models\Upload;
use App\Services\ReadinessScorer;
use Illuminate\Support\Facades\Auth;

class ReadinessController extends Controller
{
    public function run(Upload $upload, ReadinessScorer $scorer)
    {
        abort_if($upload->user_id !== Auth::id(), 403);

        try {
            $report = $scorer->score($upload);
        } catch (\Exception $e) {
            return redirect()
                ->route('uploads.show', $upload)
                ->with('error', 'Scoring failed: ' . $e->getMessage());
        }

        return redirect()
            ->route('readiness.show', $upload)
            ->with('success', "Readiness score computed: {$report->score}%");
    }

    public function show(Upload $upload)
    {
        abort_if($upload->user_id !== Auth::id(), 403);

        $report = $upload->readinessReport;

        if (!$report) {
            return redirect()
                ->route('uploads.show', $upload)
                ->with('error', 'No readiness report yet. Click "Score Readiness" first.');
        }

        return view('readiness.show', compact('upload', 'report'));
    }
}