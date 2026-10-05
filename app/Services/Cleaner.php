<?php

namespace App\Services;

use App\Models\CleanedRecord;
use App\Models\Upload;
use Carbon\Carbon;

/**
 * Cleaner
 * -------
 * Normalizes raw CSV rows into canonical "med fields" the platform expects.
 *
 * Design goals:
 *  - Idempotent: running clean() twice produces the same result.
 *  - Explainable: every change is recorded in `changes[]` with a human reason.
 *  - Conservative: when unsure, flag as issue instead of inventing data.
 */
class Cleaner
{
    /** Canonical blood types accepted by the platform. */
    private const VALID_BLOOD_TYPES = ['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-'];

    /** Gender aliases mapped to canonical values. */
    private const GENDER_MAP = [
        'm' => 'Male', 'male' => 'Male', 'man' => 'Male', 'boy' => 'Male',
        'f' => 'Female', 'female' => 'Female', 'woman' => 'Female', 'girl' => 'Female',
        'o' => 'Other', 'other' => 'Other',
        'unknown' => 'Other', 'n/a' => 'Other', 'na' => 'Other', 'x' => 'Other',
    ];

    /** Date formats tried in order (before falling back to strtotime). */
    private const DATE_FORMATS = [
        'Y-m-d', 'Y/m/d', 'Y.m.d',
        'd/m/Y', 'd-m-Y', 'd.m.Y',
        'd/m/y', 'd-m-y', 'd.m.y',
        'm/d/Y', 'm-d-Y', 'm/d/y',
        'j/n/Y', 'j-n-Y', 'j/n/y',
        'd M Y', 'd-M-Y', 'd M, Y',
        'M d, Y', 'M d Y', 'F d, Y', 'd F Y',
    ];

    public function clean(Upload $upload): int
    {
        $matches = $upload->matches()
            ->orderBy('id')
            ->pluck('med_field', 'your_field')
            ->toArray();

        if (empty($matches)) {
            throw new \RuntimeException('No column matches found. Please match columns first.');
        }

        $upload->cleanedRecords()->delete();

        $count = 0;
        $seenFingerprints = []; // for duplicate detection

        foreach ($upload->rawRows as $row) {
            $original = $row->data ?? [];
            $cleaned = [];
            $changes = [];
            $issues = 0;

            if ($row->is_malformed) {
                $issues++;
                $changes['__malformed'] = [
                    'from' => 'Malformed row',
                    'to' => $row->malformed_reason ?? 'Unknown structure issue',
                    'reason' => 'Row could not be parsed as CSV',
                ];
            }

            foreach ($matches as $yourField => $medField) {
                $rawValue = $original[$yourField] ?? '';
                $result = $this->cleanField($medField, $rawValue);

                $cleaned[$medField] = $result['value'];

                if ($result['changed']) {
                    $changes[$medField] = [
                        'from' => $rawValue,
                        'to' => $result['value'],
                        'reason' => $result['reason'] ?? 'Normalized',
                    ];
                }

                if ($result['issue'] ?? false) {
                    $issues++;
                }
            }

            // Duplicate detection on the strongest identity triple available
            $fingerprint = $this->buildFingerprint($cleaned);
            if ($fingerprint !== null) {
                if (isset($seenFingerprints[$fingerprint])) {
                    $issues++;
                    $changes['__duplicate'] = [
                        'from' => "Row {$seenFingerprints[$fingerprint]}",
                        'to' => 'Duplicate of an earlier row',
                        'reason' => 'Duplicate detected on name + DOB + phone',
                    ];
                } else {
                    $seenFingerprints[$fingerprint] = $row->row_number;
                }
            }

            CleanedRecord::create([
                'upload_id' => $upload->id,
                'row_number' => $row->row_number,
                'original_data' => $original,
                'cleaned_data' => $cleaned,
                'changes' => $changes,
                'issues_count' => $issues,
            ]);

            $count++;
        }

        $upload->update(['status' => 'cleaned']);

        return $count;
    }

    /**
     * Build a stable fingerprint for duplicate detection.
     * Requires at least 2 of {name, dob, phone} to be non-empty, otherwise
     * it returns null (so we don't false-positive on sparse rows).
     */
    private function buildFingerprint(array $cleaned): ?string
    {
        $name = strtolower(trim((string) ($cleaned['full_name'] ?? '')));
        $dob = trim((string) ($cleaned['date_of_birth'] ?? ''));
        $phone = trim((string) ($cleaned['mobile_number'] ?? ''));

        $parts = array_filter([$name, $dob, $phone], fn($v) => $v !== '');
        if (count($parts) < 2) {
            return null;
        }

        return implode('|', [$name, $dob, $phone]);
    }

    /**
     * Route a single field value to the correct cleaner.
     */
    protected function cleanField(string $medField, $value): array
    {
        $value = is_scalar($value) ? trim((string) $value) : '';

        return match ($medField) {
            'full_name' => $this->cleanName($value),
            'date_of_birth' => $this->cleanDate($value),
            'mobile_number' => $this->cleanPhone($value),
            'sex' => $this->cleanGender($value),
            'city' => $this->cleanCity($value),
            'national_id' => $this->cleanNationalId($value),
            'blood_type' => $this->cleanBloodType($value),
            'emergency_contact' => $this->cleanPhone($value),
            default => ['value' => $value, 'changed' => false],
        };
    }

    protected function cleanName(string $value): array
    {
        if ($value === '') {
            return ['value' => '', 'changed' => false, 'issue' => true, 'reason' => 'Missing name'];
        }

        // Collapse whitespace and strip control chars
        $clean = preg_replace('/[\x00-\x1F\x7F]+/u', '', $value);
        $clean = preg_replace('/\s+/u', ' ', trim($clean));

        // Title case, but keep Mc/Mac/Apostrophe names sane
        $clean = mb_strtolower($clean, 'UTF-8');
        $clean = preg_replace_callback(
            "/(^|[\s\-'])(\p{L})/u",
            fn($m) => $m[1] . mb_strtoupper($m[2], 'UTF-8'),
            $clean
        );

        $changed = $clean !== $value;

        return [
            'value' => $clean,
            'changed' => $changed,
            'reason' => $changed ? 'Normalized capitalization and whitespace' : null,
        ];
    }

    protected function cleanDate(string $value): array
    {
        if ($value === '') {
            return ['value' => '', 'changed' => false, 'issue' => true, 'reason' => 'Missing date of birth'];
        }

        $normalized = $this->normalizeDateString($value);

        foreach (self::DATE_FORMATS as $format) {
            try {
                $date = Carbon::createFromFormat('!' . $format, $normalized);
            } catch (\Throwable $e) {
                continue;
            }

            if ($date && $date->format($format) === $normalized) {
                return $this->finalizeDate($date, $value, $format);
            }
        }

        // Fallback: strtotime (handles "November 8 1992")
        $timestamp = strtotime($normalized);
        if ($timestamp !== false && $timestamp > 0) {
            $date = Carbon::createFromTimestamp($timestamp);
            return $this->finalizeDate($date, $value, 'strtotime');
        }

        return ['value' => $value, 'changed' => false, 'issue' => true, 'reason' => 'Unrecognized date format'];
    }

    /**
     * Pre-normalize separators, whitespace, and 2-digit years.
     */
    private function normalizeDateString(string $value): string
    {
        $v = trim($value);
        $v = str_replace(['.', ','], ['-', ''], $v);
        $v = preg_replace('/\s+/', ' ', $v);

        // 2-digit year: 14-06-95 -> 14-06-1995 (pivot at 30)
        $v = preg_replace_callback(
            '#^(\d{1,2})[\/\-](\d{1,2})[\/\-](\d{2})$#',
            function ($m) {
                $y = (int) $m[3];
                $fullYear = $y <= 30 ? 2000 + $y : 1900 + $y;
                return sprintf('%02d-%02d-%04d', $m[1], $m[2], $fullYear);
            },
            $v
        );

        return $v;
    }

    private function finalizeDate(Carbon $date, string $originalValue, string $via): array
    {
        $standard = $date->format('Y-m-d');
        $year = (int) $date->format('Y');

        // Sanity check: birth years must be plausible
        if ($year < 1900 || $year > (int) date('Y')) {
            return [
                'value' => $originalValue,
                'changed' => false,
                'issue' => true,
                'reason' => "Implausible year: {$year}",
            ];
        }

        return [
            'value' => $standard,
            'changed' => $standard !== $originalValue,
            'reason' => $standard !== $originalValue ? "Converted from {$via}" : null,
        ];
    }

    protected function cleanPhone(string $value): array
    {
        if ($value === '') {
            return ['value' => '', 'changed' => false, 'issue' => true, 'reason' => 'Missing phone number'];
        }

        $digits = preg_replace('/\D/', '', $value);

        // Reject obvious garbage — very few digits or absurdly long
        if (strlen($digits) < 7 || strlen($digits) > 15) {
            return [
                'value' => '',
                'changed' => true,
                'issue' => true,
                'reason' => 'Invalid phone (unusable digit count)',
            ];
        }

        // Indian 10-digit
        if (strlen($digits) === 10) {
            return [
                'value' => $digits,
                'changed' => $digits !== $value,
                'reason' => $digits !== $value ? 'Normalized to 10 digits' : null,
            ];
        }

        // Indian with country code 91XXXXXXXXXX
        if (strlen($digits) === 12 && str_starts_with($digits, '91')) {
            return [
                'value' => substr($digits, 2),
                'changed' => true,
                'reason' => 'Stripped +91 country code',
            ];
        }

        // Bahrain 8-digit
        if (strlen($digits) === 8) {
            $clean = '+973' . $digits;
            return [
                'value' => $clean,
                'changed' => $clean !== $value,
                'reason' => 'Normalized to +973 format',
            ];
        }

        // Bahrain with country code 973XXXXXXXX
        if (strlen($digits) === 11 && str_starts_with($digits, '973')) {
            $clean = '+' . $digits;
            return [
                'value' => $clean,
                'changed' => $clean !== $value,
                'reason' => 'Added + prefix to Bahrain number',
            ];
        }

        // Unknown but valid length — keep digits, flag for review
        return [
            'value' => $digits,
            'changed' => $digits !== $value,
            'issue' => true,
            'reason' => 'Unrecognized phone format — kept digits only',
        ];
    }

    protected function cleanGender(string $value): array
    {
        if ($value === '') {
            return ['value' => '', 'changed' => false, 'issue' => true, 'reason' => 'Missing gender'];
        }

        $lower = mb_strtolower(trim($value), 'UTF-8');

        if (isset(self::GENDER_MAP[$lower])) {
            $clean = self::GENDER_MAP[$lower];
            return [
                'value' => $clean,
                'changed' => $clean !== $value,
                'reason' => $clean !== $value ? "Standardized to {$clean}" : null,
            ];
        }

        return [
            'value' => $value,
            'changed' => false,
            'issue' => true,
            'reason' => 'Unrecognized gender value',
        ];
    }

    protected function cleanCity(string $value): array
    {
        if ($value === '') {
            return ['value' => '', 'changed' => false, 'issue' => true, 'reason' => 'Missing city'];
        }

        $clean = ucwords(mb_strtolower(trim($value), 'UTF-8'));

        return [
            'value' => $clean,
            'changed' => $clean !== $value,
            'reason' => $clean !== $value ? 'Normalized capitalization' : null,
        ];
    }

    protected function cleanNationalId(string $value): array
    {
        if ($value === '') {
            return ['value' => '', 'changed' => false, 'issue' => true, 'reason' => 'Missing national ID'];
        }

        $digits = preg_replace('/\D/', '', $value);

        if ($digits === '') {
            return [
                'value' => '',
                'changed' => true,
                'issue' => true,
                'reason' => 'Invalid national ID (no digits found)',
            ];
        }

        // Bahrain CPR is 9 digits
        if (strlen($digits) < 9) {
            $digits = str_pad($digits, 9, '0', STR_PAD_LEFT);
        }

        return [
            'value' => $digits,
            'changed' => $digits !== $value,
            'reason' => $digits !== $value ? 'Normalized to 9-digit CPR' : null,
        ];
    }

    protected function cleanBloodType(string $value): array
    {
        if ($value === '') {
            return ['value' => '', 'changed' => false, 'issue' => true, 'reason' => 'Missing blood type'];
        }

        $clean = strtoupper(trim($value));
        $clean = str_replace(
            ['POSITIVE', 'POS', 'NEGATIVE', 'NEG', ' '],
            ['+', '+', '-', '-', ''],
            $clean
        );
        $clean = preg_replace('/[^ABO+\-]/', '', $clean);

        if (in_array($clean, self::VALID_BLOOD_TYPES, true)) {
            return [
                'value' => $clean,
                'changed' => $clean !== $value,
                'reason' => $clean !== $value ? 'Standardized blood type' : null,
            ];
        }

        return [
            'value' => $value,
            'changed' => false,
            'issue' => true,
            'reason' => 'Unrecognized blood type',
        ];
    }
}