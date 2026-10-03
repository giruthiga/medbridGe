<?php

namespace App\Http\Controllers;

use App\Models\Upload;
use Illuminate\Support\Facades\Auth;

class InsightsController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        $uploads = Upload::where('user_id', $user->id)
            ->with(['readinessReport', 'cleanedRecords', 'matches', 'rawRows'])
            ->get();

        $scored = $uploads->filter(fn($u) => $u->readinessReport);

        // Common issues across all cleaned records
        $issueTypes = ['phone' => 0, 'date' => 0, 'gender' => 0, 'name' => 0, 'city' => 0, 'other' => 0];
        $totalChanges = 0;
        foreach ($uploads as $u) {
            foreach ($u->cleanedRecords as $cr) {
                foreach (($cr->changes ?? []) as $field => $change) {
                    $totalChanges++;
                    if (str_contains($field, 'phone') || str_contains($field, 'mobile') || str_contains($field, 'contact')) $issueTypes['phone']++;
                    elseif (str_contains($field, 'date') || str_contains($field, 'dob') || str_contains($field, 'birth')) $issueTypes['date']++;
                    elseif (str_contains($field, 'sex') || str_contains($field, 'gender')) $issueTypes['gender']++;
                    elseif (str_contains($field, 'name')) $issueTypes['name']++;
                    elseif (str_contains($field, 'city') || str_contains($field, 'address')) $issueTypes['city']++;
                    else $issueTypes['other']++;
                }
            }
        }

        // Readiness distribution
        $buckets = ['excellent' => 0, 'good' => 0, 'fair' => 0, 'poor' => 0];
        foreach ($scored as $u) {
            $s = $u->readinessReport->score;
            if ($s >= 90) $buckets['excellent']++;
            elseif ($s >= 75) $buckets['good']++;
            elseif ($s >= 50) $buckets['fair']++;
            else $buckets['poor']++;
        }

        // 30-day upload trend
        $uploadTrend = collect(range(29, 0))->map(function ($d) use ($uploads) {
            $date = now()->subDays($d)->format('Y-m-d');
            return $uploads->filter(fn($u) => $u->created_at->format('Y-m-d') === $date)->count();
        })->toArray();

        // 30-day readiness trend
        $readinessTrend = collect(range(29, 0))->map(function ($d) use ($scored) {
            $date = now()->subDays($d)->format('Y-m-d');
            $dayScores = $scored->filter(fn($u) => $u->readinessReport->created_at->format('Y-m-d') === $date);
            return $dayScores->count() > 0 ? round($dayScores->avg(fn($u) => $u->readinessReport->score)) : null;
        })->toArray();

        // Stats
        $totalUploads = $uploads->count();
        $totalRows = $uploads->sum('row_count');
        $totalMatches = $uploads->sum(fn($u) => $u->matches->count());
        $totalCleaned = $uploads->sum(fn($u) => $u->cleanedRecords->count());
        $avgScore = $scored->count() > 0 ? round($scored->avg(fn($u) => $u->readinessReport->score)) : 0;

        // Top/bottom performers
        $topPerformer = $scored->sortByDesc(fn($u) => $u->readinessReport->score)->first();
        $worstPerformer = $scored->sortBy(fn($u) => $u->readinessReport->score)->first();

        return view('insights.index', compact(
            'uploads', 'totalUploads', 'totalRows', 'totalMatches', 'totalCleaned',
            'avgScore', 'issueTypes', 'totalChanges', 'buckets',
            'uploadTrend', 'readinessTrend', 'topPerformer', 'worstPerformer', 'scored'
        ));
    }
}