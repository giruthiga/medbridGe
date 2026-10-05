<?php

namespace App\Services;

use App\Models\Upload;

/**
 * GapAnalyzer
 * -----------
 * Answers two questions for the user:
 *   1. Which MedPro modules can this file unlock today?
 *   2. Which single field, if added, would unlock the most modules?
 *
 * A module is only "ready" if every required field is present in the mapping.
 * Partial means some but not all. Locked means none.
 */
class GapAnalyzer
{
    /**
     * Every MedPro module and the fields it needs to function.
     * Order here determines display order in the UI.
     */
    public const MODULES = [
        'patient_management' => [
            'label' => 'Patient Management',
            'description' => 'Register and manage patient records',
            'required_fields' => ['full_name', 'date_of_birth', 'sex'],
            'icon' => 'user',
        ],
        'appointment_scheduling' => [
            'label' => 'Appointment Scheduling',
            'description' => 'Book and manage appointments',
            'required_fields' => ['full_name', 'mobile_number'],
            'icon' => 'calendar',
        ],
        'electronic_medical_records' => [
            'label' => 'Electronic Medical Records',
            'description' => 'Full patient history and clinical notes',
            'required_fields' => ['full_name', 'date_of_birth', 'sex'],
            'icon' => 'file',
        ],
        'billing_insurance' => [
            'label' => 'Billing & Insurance',
            'description' => 'Invoices, payments, and insurance claims',
            'required_fields' => ['full_name', 'national_id'],
            'icon' => 'dollar',
        ],
        'pharmacy' => [
            'label' => 'Pharmacy Management',
            'description' => 'Prescriptions and medicine inventory',
            'required_fields' => ['full_name', 'date_of_birth'],
            'icon' => 'pill',
        ],
        'laboratory' => [
            'label' => 'Laboratory (LIS)',
            'description' => 'Lab tests and results tracking',
            'required_fields' => ['full_name', 'national_id'],
            'icon' => 'flask',
        ],
        'emergency_contacts' => [
            'label' => 'Emergency Contacts',
            'description' => 'Next-of-kin and emergency records',
            'required_fields' => ['full_name', 'emergency_contact'],
            'icon' => 'alert',
        ],
        'blood_bank' => [
            'label' => 'Blood Bank',
            'description' => 'Blood type and donation tracking',
            'required_fields' => ['full_name', 'blood_type'],
            'icon' => 'drop',
        ],
    ];

    /** The full canonical schema we normalize against. */
    public const STANDARD_FIELDS = [
        'full_name',
        'date_of_birth',
        'mobile_number',
        'sex',
        'city',
        'national_id',
        'blood_type',
        'emergency_contact',
    ];

    public function analyze(Upload $upload): array
    {
        $matchedFields = $upload->matches()->pluck('med_field')->unique()->values()->all();

        $missingFields = array_values(array_diff(self::STANDARD_FIELDS, $matchedFields));
        $matchedCount = count(array_intersect(self::STANDARD_FIELDS, $matchedFields));
        $coveragePct = round(($matchedCount / count(self::STANDARD_FIELDS)) * 100);

        // ---- Module readiness ----
        $modules = [];
        $readyModules = $partialModules = $lockedModules = 0;

        foreach (self::MODULES as $key => $module) {
            $required = $module['required_fields'];
            $satisfied = array_values(array_intersect($required, $matchedFields));
            $missing = array_values(array_diff($required, $matchedFields));

            if (empty($missing)) {
                $status = 'ready';
                $readyModules++;
            } elseif (!empty($satisfied)) {
                $status = 'partial';
                $partialModules++;
            } else {
                $status = 'locked';
                $lockedModules++;
            }

            $modules[$key] = [
                'label'            => $module['label'],
                'description'      => $module['description'],
                'icon'             => $module['icon'],
                'required_fields'  => $required,
                'satisfied_fields' => $satisfied,
                'missing_fields'   => $missing,
                'status'           => $status,
                'completion'       => (int) round((count($satisfied) / count($required)) * 100),
            ];
        }

        // ---- Recommendations: which missing field unlocks the most modules? ----
        $impact = [];
        foreach ($missingFields as $field) {
            $count = 0;
            foreach (self::MODULES as $module) {
                if (in_array($field, $module['required_fields'], true)) {
                    $count++;
                }
            }
            $impact[$field] = $count;
        }
        arsort($impact);

        $recommendations = [];
        foreach (array_slice($impact, 0, 3, true) as $field => $count) {
            if ($count === 0) {
                continue; // don't recommend fields that unlock nothing
            }
            $recommendations[] = [
                'field'  => $field,
                'label'  => $this->formatFieldLabel($field),
                'impact' => $count,
            ];
        }

        // ---- High-level warning for very incomplete files ----
        $missingCount = count($missingFields);
        $completenessWarning = match (true) {
            $missingCount >= 6 => 'This file is missing most standard fields. Only the most basic modules will be available.',
            $missingCount >= 4 => "This file is missing {$missingCount} standard fields. Several modules will remain locked.",
            $missingCount >= 2 => "This file is missing {$missingCount} standard fields. Some modules will be limited.",
            $missingCount === 1 => 'This file is missing 1 standard field.',
            default => null,
        };

        return [
            // Coverage
            'matched_fields'        => $matchedFields,
            'missing_fields'        => $missingFields,
            'missing_fields_count'  => $missingCount,
            'matched_count'         => $matchedCount,
            'total_fields'          => count(self::STANDARD_FIELDS),
            'coverage_pct'          => $coveragePct,

            // Modules
            'modules'               => $modules,
            'ready_modules'         => $readyModules,
            'partial_modules'       => $partialModules,
            'locked_modules'        => $lockedModules,
            'total_modules'         => count(self::MODULES),

            // Advice
            'recommendations'       => $recommendations,
            'completeness_warning'  => $completenessWarning,
        ];
    }

    protected function formatFieldLabel(string $field): string
    {
        return match ($field) {
            'full_name'         => 'Full Name',
            'date_of_birth'     => 'Date of Birth',
            'mobile_number'     => 'Mobile Number',
            'sex'               => 'Sex',
            'city'              => 'City',
            'national_id'       => 'National ID / CPR',
            'blood_type'        => 'Blood Type',
            'emergency_contact' => 'Emergency Contact',
            default             => ucwords(str_replace('_', ' ', $field)),
        };
    }
}