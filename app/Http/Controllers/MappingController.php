<?php

namespace App\Http\Controllers;

use App\Models\MatchModel;
use App\Models\Upload;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class MappingController extends Controller
{
    public const MED_FIELDS = [
        'full_name'         => 'Full Name',
        'date_of_birth'     => 'Date of Birth',
        'mobile_number'     => 'Mobile Number',
        'sex'               => 'Sex (Male/Female)',
        'city'              => 'City',
        'national_id'       => 'National ID / CPR',
        'blood_type'        => 'Blood Type',
        'emergency_contact' => 'Emergency Contact',
    ];

    public function show(Upload $upload)
    {
        abort_if($upload->user_id !== Auth::id(), 403);

        $firstRow = $upload->rawRows()->first();
        $yourFields = $firstRow ? array_keys($firstRow->data) : [];

        $existing = $upload->matches()
            ->pluck('med_field', 'your_field')
            ->toArray();

        return view('mapping.show', [
            'upload' => $upload,
            'yourFields' => $yourFields,
            'medFields' => self::MED_FIELDS,
            'existing' => $existing,
        ]);
    }

    public function store(Request $request, Upload $upload)
    {
        abort_if($upload->user_id !== Auth::id(), 403);

        $request->validate([
            'mappings' => 'required|array',
            'mappings.*' => 'nullable|string|in:' . implode(',', array_keys(self::MED_FIELDS)),
        ]);

        $upload->matches()->delete();

        foreach ($request->mappings as $yourField => $medField) {
            if (empty($medField)) {
                continue;
            }

            MatchModel::create([
                'upload_id' => $upload->id,
                'your_field' => $yourField,
                'med_field' => $medField,
            ]);
        }

        $upload->update(['status' => 'mapped']);

        return redirect()
            ->route('uploads.show', $upload)
            ->with('success', 'Column matches saved successfully.');
    }
}