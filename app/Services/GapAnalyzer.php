<?php

namespace App\Services;

use App\Models\Upload;

class GapAnalyzer
{
    /**
     * The full MedPro module set — each module needs certain fields.
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

    /**
     * Analyze the gaps in an upload.
     */
    public function analyze(Upload $upload): array
    {
        $matchedFields = $upload->matches()->pluck('med_field')->unique()->toArray();
        $allFields = array_keys(self::MODULES['patient_management']['required_fields']);
        $standardFields = ['full_name', 'date_of_birth', 'mobile_number', 'sex', 'city', 'national_id', 'blood_type', 'emergency_contact'];

        $missingFields = array_diff($standardFields, $matchedFields);
        $matchedCount = count(array_intersect($standardFields, $matchedFields));
        $coveragePct = round(($matchedCount / count($standardFields)) * 100);

        // Analyze each module
        $moduleStatus = [];
        $readyModules = 0;
        $partialModules = 0;
        $lockedModules = 0;

        foreach (self::MODULES as $key => $module) {
            $satisfied = array_intersect($module['required_fields'], $matchedFields);
            $missing = array_diff($module['required_fields'], $matchedFields);

            $status = 'locked';
            if (count($missing) === 0) {
                $status = 'ready';
                $readyModules++;
            } elseif (count($satisfied) > 0) {
                $status = 'partial';
                $partialModules++;
            } else {
                $lockedModules++;
            }

            $moduleStatus[$key] = [
                'label' => $module['label'],
                'description' => $module['description'],
                'required_fields' => $module['required_fields'],
                'missing_fields' => array_values($missing),
                'satisfied_fields' => array_values($satisfied),
                'status' => $status,
                'completion' => round((count($satisfied) / count($module['required_fields'])) * 100),
                'icon' => $module['icon'],
            ];
        }

        // Priority: what to add next to unlock the most modules
        $fieldImpact = [];
        foreach ($missingFields as $field) {
            $impact = 0;
            foreach (self::MODULES as $module) {
                if (in_array($field, $module['required_fields'])) $impact++;
            }
            $fieldImpact[$field] = $impact;
        }
        arsort($fieldImpact);

        $recommendations = [];
        foreach (array_slice($fieldImpact, 0, 3, true) as $field => $impact) {
            $recommendations[] = [
                'field' => $field,
                'impact' => $impact,
                'label' => $this->formatFieldLabel($field),
            ];
        }

        return [
            'matched_fields' => $matchedFields,
            'missing_fields' => array_values($missingFields),
            'coverage_pct' => $coveragePct,
            'total_fields' => count($standardFields),
            'matched_count' => $matchedCount,
            'modules' => $moduleStatus,
            'ready_modules' => $readyModules,
            'partial_modules' => $partialModules,
            'locked_modules' => $lockedModules,
            'total_modules' => count(self::MODULES),
            'recommendations' => $recommendations,
        ];
    }

    protected function formatFieldLabel(string $field): string
    {
        return match($field) {
            'full_name' => 'Full Name',
            'date_of_birth' => 'Date of Birth',
            'mobile_number' => 'Mobile Number',
            'sex' => 'Sex',
            'city' => 'City',
            'national_id' => 'National ID / CPR',
            'blood_type' => 'Blood Type',
            'emergency_contact' => 'Emergency Contact',
            default => ucwords(str_replace('_', ' ', $field)),
        };
    }
}