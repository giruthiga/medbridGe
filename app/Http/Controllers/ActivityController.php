<?php

namespace App\Http\Controllers;

use App\Models\Upload;
use Illuminate\Support\Facades\Auth;

class ActivityController extends Controller
{
    public function index()
    {
        $uploads = Upload::where('user_id', Auth::id())
            ->with(['readinessReport', 'cleanedRecords', 'matches', 'rawRows'])
            ->get();

        // Build activity feed
        $activities = collect();

        foreach ($uploads as $upload) {
            $activities->push([
                'type' => 'upload',
                'at' => $upload->created_at,
                'title' => 'Uploaded ' . $upload->original_filename,
                'desc' => $upload->row_count . ' rows',
                'url' => '/uploads/' . $upload->id,
                'color' => '#60a5fa',
                'icon' => 'upload',
            ]);

            if ($upload->rawRows->isNotEmpty()) {
                $activities->push([
                    'type' => 'parse',
                    'at' => $upload->updated_at,
                    'title' => 'Parsed ' . $upload->original_filename,
                    'desc' => $upload->rawRows->count() . ' rows extracted',
                    'url' => '/uploads/' . $upload->id,
                    'color' => '#34d399',
                    'icon' => 'file',
                ]);
            }

            if ($upload->matches->isNotEmpty()) {
                $activities->push([
                    'type' => 'map',
                    'at' => $upload->matches->first()->created_at,
                    'title' => 'Matched ' . $upload->matches->count() . ' columns',
                    'desc' => $upload->original_filename,
                    'url' => '/uploads/' . $upload->id . '/map',
                    'color' => '#c084fc',
                    'icon' => 'link',
                ]);
            }

            if ($upload->cleanedRecords->isNotEmpty()) {
                $activities->push([
                    'type' => 'clean',
                    'at' => $upload->cleanedRecords->first()->created_at,
                    'title' => 'Cleaned ' . $upload->cleanedRecords->count() . ' records',
                    'desc' => $upload->original_filename,
                    'url' => '/uploads/' . $upload->id . '/cleaned',
                    'color' => '#22d3ee',
                    'icon' => 'sparkle',
                ]);
            }

            if ($upload->readinessReport) {
                $activities->push([
                    'type' => 'score',
                    'at' => $upload->readinessReport->created_at,
                    'title' => 'Scored ' . $upload->readinessReport->score . '%',
                    'desc' => $upload->original_filename,
                    'url' => '/uploads/' . $upload->id . '/readiness',
                    'color' => '#fbbf24',
                    'icon' => 'chart',
                ]);
            }
        }

        // Sort by time, newest first
        $activities = $activities->sortByDesc('at')->values();

        // Group by day
        $grouped = $activities->groupBy(fn($a) => $a['at']->format('Y-m-d'));

        // Today count
        $todayCount = $activities->filter(fn($a) => $a['at']->isToday())->count();
        $weekCount = $activities->filter(fn($a) => $a['at']->isCurrentWeek())->count();

        return view('activity.index', compact('activities', 'grouped', 'todayCount', 'weekCount'));
    }
}