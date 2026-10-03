<?php

namespace App\Services;

use App\Models\RawRow;
use App\Models\Upload;
use Illuminate\Support\Facades\Storage;

class CsvParser
{
    public function parse(Upload $upload): int
    {
        $fullPath = Storage::disk('public')->path($upload->stored_path);

        if (!file_exists($fullPath)) {
            throw new \Exception("File not found: {$upload->stored_path}");
        }

        $handle = fopen($fullPath, 'r');
        if ($handle === false) {
            throw new \Exception('Could not open file.');
        }

        $headers = fgetcsv($handle);
        if ($headers === false) {
            fclose($handle);
            return 0;
        }

        // Clean headers
        $headers = array_map(function ($h) {
            $h = (string) $h;
            $h = preg_replace('/^\xEF\xBB\xBF/', '', $h);
            return strtolower(trim(str_replace([' ', '-'], '_', $h)));
        }, $headers);

        $headerCount = count($headers);
        $rowNumber = 0;
        $count = 0;
        $malformedCount = 0;

        $upload->rawRows()->delete();

        while (($row = fgetcsv($handle)) !== false) {
            $rowNumber++;

            // Get original field count BEFORE padding
            $originalFieldCount = count($row);

            // Trim whitespace
            $row = array_map(function ($v) {
                return $v === null ? '' : trim((string) $v);
            }, $row);

            // Skip fully empty rows
            if (count(array_filter($row, fn($v) => $v !== '')) === 0) {
                continue;
            }

            // Determine if malformed
            $isMalformed = false;
            $reason = null;

            if ($originalFieldCount > $headerCount) {
                $isMalformed = true;
                $reason = "Row has {$originalFieldCount} fields, expected {$headerCount} (extra columns)";
            } elseif ($originalFieldCount < $headerCount) {
                $isMalformed = true;
                $reason = "Row has {$originalFieldCount} fields, expected {$headerCount} (missing columns)";
            }

            // Pad/truncate to header count
            $row = array_pad($row, $headerCount, '');
            $row = array_slice($row, 0, $headerCount);

            // Build associative array
            $data = [];
            foreach ($headers as $index => $header) {
                $data[$header] = $row[$index] ?? '';
            }

            // Add validation for empty required fields
            $emptyRequired = [];
            foreach (['patient_name', 'full_name', 'name'] as $nameField) {
                if (array_key_exists($nameField, $data) && empty($data[$nameField])) {
                    $emptyRequired[] = $nameField;
                }
            }
            if (!empty($emptyRequired)) {
                $isMalformed = true;
                $reason = ($reason ? $reason . ' | ' : '') . 'Missing required field: ' . implode(', ', $emptyRequired);
            }

            RawRow::create([
                'upload_id' => $upload->id,
                'row_number' => $rowNumber,
                'data' => $data,
                'is_malformed' => $isMalformed,
                'field_count' => $originalFieldCount,
                'malformed_reason' => $reason,
            ]);

            if ($isMalformed) {
                $malformedCount++;
            }

            $count++;
        }

        fclose($handle);

        $upload->update([
            'row_count' => $count,
            'status' => 'parsed',
        ]);

        return $count;
    }
}