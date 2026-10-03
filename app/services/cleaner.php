<?php

namespace App\Services;

use App\Models\CleanedRecord;
use App\Models\Upload;

class Cleaner
{
    public function clean(Upload $upload): int
    {
        $matches = $upload->matches()
            ->orderBy('id')
            ->pluck('med_field', 'your_field')
            ->toArray();

        if (empty($matches)) {
            throw new \Exception('No column matches found. Please match columns first.');
        }

        $upload->cleanedRecords()->delete();

        $count = 0;

        foreach ($upload->rawRows as $row) {
            $original = $row->data;
            $cleaned = [];
            $changes = [];
            $issues = 0;

            // If the raw row is malformed, flag it as an issue but still process
            if ($row->is_malformed) {
                $issues++;
            }

            foreach ($matches as $yourField => $medField) {
                $rawValue = $original[$yourField] ?? '';
                $result = $this->cleanField($medField, $rawValue);

                $cleaned[$medField] = $result['value'];

                if ($result['changed']) {
                    $changes[$medField] = [
                        'from' => $rawValue,
                        'to' => $result['value'],
                        'reason' => $result['reason'] ?? null,
                    ];
                }

                if ($result['issue'] ?? false) {
                    $issues++;
                }
            }

            // Track malformed row reason
            if ($row->is_malformed) {
                $changes['__malformed'] = [
                    'from' => 'Malformed row',
                    'to' => $row->malformed_reason,
                    'reason' => 'Data structure issue',
                ];
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

    protected function cleanField(string $medField, $value): array
    {
        $value = trim((string) $value);

        return match ($medField) {
            'full_name'         => $this->cleanName($value),
            'date_of_birth'     => $this->cleanDate($value),
            'mobile_number'     => $this->cleanPhone($value),
            'sex'               => $this->cleanGender($value),
            'city'              => $this->cleanCity($value),
            'national_id'       => $this->cleanNumeric($value),
            'blood_type'        => $this->cleanBloodType($value),
            'emergency_contact' => $this->cleanPhone($value),
            default             => ['value' => $value, 'changed' => false],
        };
    }

    protected function cleanName(string $value): array
    {
        if ($value === '') {
            return ['value' => '', 'changed' => false, 'issue' => true, 'reason' => 'Empty name'];
        }

        $clean = preg_replace('/\s+/', ' ', trim($value));
        $clean = mb_strtolower($clean, 'UTF-8');
        $clean = preg_replace_callback(
            "/(^|[\s\-'])(\p{L})/u",
            fn($m) => $m[1] . mb_strtoupper($m[2], 'UTF-8'),
            $clean
        );

        return [
            'value' => $clean,
            'changed' => $clean !== $value,
            'reason' => $clean !== $value ? 'Normalized capitalization' : null,
        ];
    }

    protected function cleanDate(string $value): array
    {
        if ($value === '') {
            return ['value' => '', 'changed' => false, 'issue' => true, 'reason' => 'Empty date'];
        }

        $formats = ['Y-m-d', 'd/m/Y', 'd-m-Y', 'Y/m/d', 'm/d/Y', 'd.m.Y', 'd M Y', 'M d, Y', 'd F Y'];
        foreach ($formats as $format) {
            $date = \DateTime::createFromFormat($format, $value);
            if ($date && $date->format($format) === $value) {
                $standard = $date->format('Y-m-d');
                return [
                    'value' => $standard,
                    'changed' => $standard !== $value,
                    'reason' => $standard !== $value ? "Converted from {$format}" : null,
                ];
            }
        }

        $timestamp = strtotime($value);
        if ($timestamp !== false && $timestamp > 0) {
            $standard = date('Y-m-d', $timestamp);
            return [
                'value' => $standard,
                'changed' => $standard !== $value,
                'reason' => $standard !== $value ? 'Parsed with strtotime' : null,
            ];
        }

        return ['value' => $value, 'changed' => false, 'issue' => true, 'reason' => 'Unrecognized date format'];
    }

    protected function cleanPhone(string $value): array
    {
        if ($value === '') {
            return ['value' => '', 'changed' => false, 'issue' => true, 'reason' => 'Empty phone'];
        }

        $digits = preg_replace('/\D/', '', $value);

        if (strlen($digits) === 8) {
            $clean = '+973' . $digits;
            return ['value' => $clean, 'changed' => $clean !== $value, 'reason' => 'Normalized to +973 format'];
        }

        if (strlen($digits) === 11 && str_starts_with($digits, '973')) {
            $clean = '+' . $digits;
            return ['value' => $clean, 'changed' => $clean !== $value, 'reason' => 'Added + prefix'];
        }

        return ['value' => $value, 'changed' => false, 'issue' => true, 'reason' => 'Unrecognized phone format'];
    }

    protected function cleanGender(string $value): array
    {
        if ($value === '') {
            return ['value' => '', 'changed' => false, 'issue' => true, 'reason' => 'Empty gender'];
        }

        $lower = strtolower(trim($value));
        $map = [
            'm' => 'Male', 'male' => 'Male', 'man' => 'Male',
            'f' => 'Female', 'female' => 'Female', 'woman' => 'Female',
            'o' => 'Other', 'other' => 'Other',
            'unknown' => 'Other', 'n/a' => 'Other',
        ];

        if (isset($map[$lower])) {
            $clean = $map[$lower];
            return ['value' => $clean, 'changed' => $clean !== $value, 'reason' => $clean !== $value ? "Standardized to {$clean}" : null];
        }

        return ['value' => $value, 'changed' => false, 'issue' => true, 'reason' => 'Unrecognized gender value'];
    }

    protected function cleanCity(string $value): array
    {
        if ($value === '') {
            return ['value' => '', 'changed' => false, 'issue' => true, 'reason' => 'Empty city'];
        }

        $clean = ucwords(mb_strtolower(trim($value), 'UTF-8'));
        return ['value' => $clean, 'changed' => $clean !== $value, 'reason' => $clean !== $value ? 'Normalized capitalization' : null];
    }

    protected function cleanNumeric(string $value): array
    {
        if ($value === '') {
            return ['value' => '', 'changed' => false, 'issue' => true, 'reason' => 'Empty value'];
        }

        $clean = preg_replace('/\D/', '', $value);

        if (strlen($clean) > 0 && strlen($clean) < 9) {
            $clean = str_pad($clean, 9, '0', STR_PAD_LEFT);
        }

        return ['value' => $clean, 'changed' => $clean !== $value, 'reason' => $clean !== $value ? 'Stripped non-numeric characters' : null];
    }

    protected function cleanBloodType(string $value): array
    {
        if ($value === '') {
            return ['value' => '', 'changed' => false, 'issue' => true, 'reason' => 'Empty blood type'];
        }

        $clean = strtoupper(trim($value));
        $clean = str_replace(['POSITIVE', 'POS', 'NEGATIVE', 'NEG'], ['+', '+', '-', '-'], $clean);
        $clean = preg_replace('/[^ABO+\-]/', '', $clean);

        $valid = ['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-'];
        if (in_array($clean, $valid)) {
            return ['value' => $clean, 'changed' => $clean !== $value, 'reason' => $clean !== $value ? 'Standardized to uppercase' : null];
        }

        return ['value' => $value, 'changed' => false, 'issue' => true, 'reason' => 'Unrecognized blood type'];
    }
}