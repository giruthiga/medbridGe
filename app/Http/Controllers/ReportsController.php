<?php

namespace App\Http\Controllers;

use App\Models\Upload;
use Illuminate\Support\Facades\Auth;

class ReportsController extends Controller
{
    public function index()
    {
        $uploads = Upload::where('user_id', Auth::id())
            ->with(['readinessReport', 'cleanedRecords', 'matches', 'rawRows'])
            ->whereHas('readinessReport')
            ->get()
            ->sortByDesc(fn($u) => $u->readinessReport->score);

        $allScored = $uploads->map(function ($u) {
            return [
                'upload' => $u,
                'score' => $u->readinessReport->score,
                'summary' => $u->readinessReport->summary,
                'scored_at' => $u->readinessReport->created_at,
            ];
        });

        $avgScore = $uploads->count() > 0 ? round($uploads->avg(fn($u) => $u->readinessReport->score)) : 0;

        $buckets = [
            'excellent' => $uploads->filter(fn($u) => $u->readinessReport->score >= 90)->count(),
            'good' => $uploads->filter(fn($u) => $u->readinessReport->score >= 75 && $u->readinessReport->score < 90)->count(),
            'fair' => $uploads->filter(fn($u) => $u->readinessReport->score >= 50 && $u->readinessReport->score < 75)->count(),
            'poor' => $uploads->filter(fn($u) => $u->readinessReport->score < 50)->count(),
        ];

        return view('reports.index', compact('allScored', 'avgScore', 'buckets'));
    }
}