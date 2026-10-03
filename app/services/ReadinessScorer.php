<?php

namespace App\Services;

use App\Models\ReadinessReport;
use App\Models\Upload;
use Illuminate\Support\Facades\DB;

class ReadinessScorer
{
    public const REQUIRED_FIELDS = ['full_name', 'date_of_birth', 'mobile_number'];

    public const TOTAL_STANDARD_FIELDS = 8;

    /**
     * Compute the readiness score for an upload and save the report.
     */
    public function score(Upload $upload): ReadinessReport
    {
        $records = $upload->cleanedRecords;
        $totalRecords = $records->count();

        if ($totalRecords === 0) {
            throw new \Exception('No cleaned records found. Please clean the data first.');
        }

        // ====== Factor 1: Column Coverage (30%) ======
        $matchedFields = $upload->matches()->pluck('med_field')->unique()->count();
        $coverageScore = ($matchedFields / self::TOTAL_STANDARD_FIELDS) * 100;

        // ====== Factor 2: Row Validity (40%) ======
        $validRows = $records->where('issues_count', 0)->count();
        $validityScore = ($validRows / $totalRecords) * 100;

        // ====== Factor 3: Data Quality (20%) ======
        // Higher when fewer fixes were needed (means original data was already clean)
        $totalChanges = 0;
        $totalFields = 0;
        foreach ($records as $record) {
            $totalChanges += count($record->changes ?? []);
            $totalFields += count($record->cleaned_data ?? []);
        }
        $qualityScore = $totalFields > 0
            ? (1 - ($totalChanges / $totalFields)) * 100
            : 100;

        // ====== Factor 4: Required Fields (10%) ======
        $requiredMapped = 0;
        $matchedFieldNames = $upload->matches()->pluck('med_field')->toArray();
        foreach (self::REQUIRED_FIELDS as $required) {
            if (in_array($required, $matchedFieldNames)) {
                $requiredMapped++;
            }
        }
        $requiredScore = ($requiredMapped / count(self::REQUIRED_FIELDS)) * 100;

        // ====== Weighted Total ======
        $totalScore = round(
            ($coverageScore * 0.30) +
            ($validityScore * 0.40) +
            ($qualityScore  * 0.20) +
            ($requiredScore * 0.10)
        );

        // Clamp 0-100
        $totalScore = max(0, min(100, $totalScore));

        // ====== Breakdown for display ======
        $breakdown = [
            'coverage' => [
                'label' => 'Column Coverage',
                'weight' => 30,
                'score' => round($coverageScore),
                'weighted' => round($coverageScore * 0.30, 1),
                'detail' => "{$matchedFields} of " . self::TOTAL_STANDARD_FIELDS . " standard fields matched",
            ],
            'validity' => [
                'label' => 'Row Validity',
                'weight' => 40,
                'score' => round($validityScore),
                'weighted' => round($validityScore * 0.40, 1),
                'detail' => "{$validRows} of {$totalRecords} rows with no issues",
            ],
            'quality' => [
                'label' => 'Data Quality',
                'weight' => 20,
                'score' => round($qualityScore),
                'weighted' => round($qualityScore * 0.20, 1),
                'detail' => "{$totalChanges} fields needed fixing",
            ],
            'required' => [
                'label' => 'Required Fields',
                'weight' => 10,
                'score' => round($requiredScore),
                'weighted' => round($requiredScore * 0.10, 1),
                'detail' => "{$requiredMapped} of " . count(self::REQUIRED_FIELDS) . " required fields matched",
            ],
        ];

        // ====== Summary text ======
        $summary = $this->buildSummary($totalScore);

        // ====== Save (upsert) ======
        $report = ReadinessReport::updateOrCreate(
            ['upload_id' => $upload->id],
            [
                'score' => $totalScore,
                'breakdown' => $breakdown,
                'details' => [
                    'total_records' => $totalRecords,
                    'valid_rows' => $validRows,
                    'matched_fields' => $matchedFields,
                    'total_changes' => $totalChanges,
                ],
                'summary' => $summary,
            ]
        );

        $upload->update(['status' => 'scored']);

        return $report;
    }

    protected function buildSummary(int $score): string
    {
        return match (true) {
            $score >= 90 => 'Excellent — this data is ready for import.',
            $score >= 75 => 'Good — minor issues to address before import.',
            $score >= 50 => 'Fair — several issues need fixing first.',
            $score >= 25 => 'Poor — significant work needed.',
            default      => 'Critical — this data is not ready.',
        };
    }
}