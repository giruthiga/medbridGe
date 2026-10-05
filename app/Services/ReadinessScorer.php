<?php

namespace App\Services;

use App\Models\ReadinessReport;
use App\Models\Upload;

/**
 * ReadinessScorer
 * ---------------
 * Produces a 0-100 readiness score with a transparent, weighted breakdown.
 *
 * The score answers a single question: "Can this hospital safely import
 * this CSV into MedPro today?" — not "Is this CSV clean?" Those are
 * different questions, and the biggest mistake you can make is rewarding
 * a clean file that is missing half of the required schema.
 *
 * The score is therefore a weighted blend of four factors, then capped
 * by a coverage ceiling to prevent a small but clean file from scoring
 * artificially high.
 */
class ReadinessScorer
{
    /** Fields that every upload should have, at minimum. */
    public const REQUIRED_FIELDS = ['full_name', 'date_of_birth', 'mobile_number'];

    /** Total schema size we normalize against. */
    public const TOTAL_STANDARD_FIELDS = 8;

    /** Weights must sum to 1.0 */
    private const WEIGHTS = [
        'coverage' => 0.30,
        'validity' => 0.40,
        'quality'  => 0.20,
        'required' => 0.10,
    ];

    /**
     * Coverage ceilings: a file missing too many columns cannot be
     * declared "ready" no matter how clean the rows are.
     */
    private const COVERAGE_CAPS = [
        ['threshold' => 40,  'cap' => 50],
        ['threshold' => 60,  'cap' => 65],
        ['threshold' => 75,  'cap' => 80],
        ['threshold' => 90,  'cap' => 92],
    ];

    public function score(Upload $upload): ReadinessReport
    {
        $records = $upload->cleanedRecords;
        $totalRecords = $records->count();

        if ($totalRecords === 0) {
            throw new \RuntimeException('No cleaned records found. Please clean the data first.');
        }

        // ---- Factor 1: Column Coverage ----
        $matchedFields = $upload->matches()->pluck('med_field')->unique()->values();
        $matchedFieldCount = $matchedFields->count();
        $coverageScore = ($matchedFieldCount / self::TOTAL_STANDARD_FIELDS) * 100;

        // ---- Factor 2: Row Validity ----
        $validRows = $records->where('issues_count', 0)->count();
        $validityScore = ($validRows / $totalRecords) * 100;

        // ---- Factor 3: Data Quality ----
        // Lower if the original data was already clean (few changes needed).
        $totalChanges = 0;
        $totalPopulatedFields = 0;
        foreach ($records as $record) {
            $totalChanges += is_array($record->changes) ? count($record->changes) : 0;
            $totalPopulatedFields += is_array($record->cleaned_data) ? count($record->cleaned_data) : 0;
        }
        $qualityScore = $totalPopulatedFields > 0
            ? max(0, (1 - ($totalChanges / $totalPopulatedFields)) * 100)
            : 100;

        // ---- Factor 4: Required Fields ----
        $matchedFieldNames = $matchedFields->all();
        $requiredMapped = count(array_intersect(self::REQUIRED_FIELDS, $matchedFieldNames));
        $requiredScore = ($requiredMapped / count(self::REQUIRED_FIELDS)) * 100;

        // ---- Weighted Base Score ----
        $rawScore =
            ($coverageScore * self::WEIGHTS['coverage']) +
            ($validityScore * self::WEIGHTS['validity']) +
            ($qualityScore  * self::WEIGHTS['quality']) +
            ($requiredScore * self::WEIGHTS['required']);

        $rawScore = round($rawScore);

        // ---- Coverage Cap ----
        $coverageCap = $this->resolveCoverageCap($coverageScore);
        $finalScore = max(0, min(100, min($rawScore, $coverageCap)));

        // ---- Breakdown ----
        $breakdown = [
            'coverage' => [
                'label'    => 'Column Coverage',
                'weight'   => (int) (self::WEIGHTS['coverage'] * 100),
                'score'    => round($coverageScore),
                'weighted' => round($coverageScore * self::WEIGHTS['coverage'], 1),
                'detail'   => "{$matchedFieldCount} of " . self::TOTAL_STANDARD_FIELDS . ' standard fields matched',
            ],
            'validity' => [
                'label'    => 'Row Validity',
                'weight'   => (int) (self::WEIGHTS['validity'] * 100),
                'score'    => round($validityScore),
                'weighted' => round($validityScore * self::WEIGHTS['validity'], 1),
                'detail'   => "{$validRows} of {$totalRecords} rows with no issues",
            ],
            'quality' => [
                'label'    => 'Data Quality',
                'weight'   => (int) (self::WEIGHTS['quality'] * 100),
                'score'    => round($qualityScore),
                'weighted' => round($qualityScore * self::WEIGHTS['quality'], 1),
                'detail'   => "{$totalChanges} fields needed fixing",
            ],
            'required' => [
                'label'    => 'Required Fields',
                'weight'   => (int) (self::WEIGHTS['required'] * 100),
                'score'    => round($requiredScore),
                'weighted' => round($requiredScore * self::WEIGHTS['required'], 1),
                'detail'   => "{$requiredMapped} of " . count(self::REQUIRED_FIELDS) . ' required fields matched',
            ],
            'coverage_cap' => [
                'label'   => 'Coverage Cap',
                'score'   => $coverageCap,
                'applied' => $coverageCap < 100,
                'detail'  => $coverageCap < 100
                    ? "Final score capped at {$coverageCap} due to incomplete columns"
                    : 'No cap applied',
            ],
        ];

        $summary = $this->buildSummary($finalScore);

        $report = ReadinessReport::updateOrCreate(
            ['upload_id' => $upload->id],
            [
                'score'     => $finalScore,
                'breakdown' => $breakdown,
                'details'   => [
                    'total_records'      => $totalRecords,
                    'valid_rows'         => $validRows,
                    'matched_fields'     => $matchedFieldCount,
                    'total_changes'      => $totalChanges,
                    'raw_score'          => $rawScore,
                    'coverage_cap'       => $coverageCap,
                    'final_score'        => $finalScore,
                ],
                'summary' => $summary,
            ]
        );

        $upload->update(['status' => 'scored']);

        return $report;
    }

    /**
     * Map a coverage percentage to the maximum allowed final score.
     */
    private function resolveCoverageCap(float $coveragePercent): int
    {
        foreach (self::COVERAGE_CAPS as $entry) {
            if ($coveragePercent < $entry['threshold']) {
                return $entry['cap'];
            }
        }
        return 100;
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