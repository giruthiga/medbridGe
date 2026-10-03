<?php

namespace App\Http\Controllers;

use App\Models\Upload;
use App\Services\Cleaner;
use Illuminate\Support\Facades\Auth;

class CleaningController extends Controller
{
    public function run(Upload $upload, Cleaner $cleaner)
    {
        abort_if($upload->user_id !== Auth::id(), 403);

        try {
            $count = $cleaner->clean($upload);
        } catch (\Exception $e) {
            return redirect()
                ->route('uploads.show', $upload)
                ->with('error', 'Cleaning failed: ' . $e->getMessage());
        }

        return redirect()
            ->route('uploads.show', $upload)
            ->with('success', "Cleaned {$count} records successfully.");
    }

    public function preview(Upload $upload)
    {
        abort_if($upload->user_id !== Auth::id(), 403);

        $records = $upload->cleanedRecords()->orderBy('row_number')->limit(50)->get();

        $total = $upload->cleanedRecords()->count();
        $changed = 0;
        $issues = 0;

        foreach ($records as $r) {
            if (!empty($r->changes)) $changed++;
            $issues += $r->issues_count;
        }

        $stats = [
            'total' => $total,
            'changed' => $changed,
            'issues' => $issues,
        ];

        return view('cleaning.preview', compact('upload', 'records', 'stats'));
    }
}