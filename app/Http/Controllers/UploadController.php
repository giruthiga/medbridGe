<?php

namespace App\Http\Controllers;

use App\Models\Upload;
use App\Services\CsvParser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class UploadController extends Controller
{
    public function index()
    {
        $uploads = Upload::where('user_id', Auth::id())
            ->latest()
            ->get();

        return view('uploads.index', compact('uploads'));
    }

    public function create()
    {
        return view('uploads.create');
    }

    public function store(Request $request, CsvParser $parser)
    {
        $request->validate([
            'csv_file' => 'required|file|mimes:csv,txt|max:10240',
        ]);

        $file = $request->file('csv_file');
        $originalName = $file->getClientOriginalName();
        $path = $file->store('uploads', 'public');

        $upload = Upload::create([
            'user_id' => Auth::id(),
            'original_filename' => $originalName,
            'stored_path' => $path,
            'row_count' => 0,
            'status' => 'uploaded',
        ]);

        try {
            $parser->parse($upload);
        } catch (\Exception $e) {
            return redirect()
                ->route('uploads.show', $upload)
                ->with('error', 'Upload succeeded but parsing failed: ' . $e->getMessage());
        }

        return redirect()
            ->route('uploads.show', $upload)
            ->with('success', 'File uploaded and parsed successfully.');
    }

    public function show(Upload $upload)
    {
        abort_if($upload->user_id !== Auth::id(), 403);

        $upload->load(['rawRows', 'matches', 'cleanedRecords', 'readinessReport']);

        return view('uploads.show', compact('upload'));
    }

    public function generateSample()
    {
        $samples = [
            ['name' => 'Ahmed Ali',       'dob' => '12/04/1985', 'phone' => '973-39123456', 'gender' => 'M',      'address' => 'Manama'],
            ['name' => 'Fatima Hassan',   'dob' => '1990-08-23', 'phone' => '36123457',     'gender' => 'F',      'address' => 'Muharraq'],
            ['name' => 'John Smith',      'dob' => '05-01-1978', 'phone' => '35123458',     'gender' => 'Male',   'address' => 'Riffa'],
            ['name' => 'Aisha Khalifa',   'dob' => '1992/03/15', 'phone' => '973-36123459', 'gender' => 'F',      'address' => 'Isa Town'],
            ['name' => 'Mohammed Rahman', 'dob' => '1988-11-02', 'phone' => '39123460',     'gender' => 'M',      'address' => 'Sitra'],
            ['name' => '',                'dob' => '1975-06-20', 'phone' => '35123461',     'gender' => 'M',      'address' => 'Hamad Town'],
            ['name' => 'A. Ali',          'dob' => '12/04/1985', 'phone' => '973-39123456', 'gender' => 'M',      'address' => 'Manama'],
            ['name' => 'Sara Ahmed',      'dob' => '1995-09-10', 'phone' => 'BAD_NUMBER',   'gender' => 'F',      'address' => 'Budaiya'],
            ['name' => 'Khalid Yusuf',    'dob' => '1983-02-28', 'phone' => '36123462',     'gender' => 'male',   'address' => 'Juffair'],
            ['name' => 'Mariam Saleh',    'dob' => '01/01/2000', 'phone' => '39123463',     'gender' => 'female', 'address' => 'Seef'],
        ];

        $csv = "name,dob,phone,gender,address\n";
        foreach ($samples as $row) {
            $csv .= implode(',', array_map(
                fn($v) => '"' . str_replace('"', '""', $v) . '"',
                $row
            )) . "\n";
        }

        $filename = 'sample-hospital-data-' . now()->format('Y-m-d-His') . '.csv';
        $path = 'uploads/' . $filename;

        Storage::disk('public')->put($path, $csv);

        $upload = Upload::create([
            'user_id' => Auth::id(),
            'original_filename' => $filename,
            'stored_path' => $path,
            'row_count' => 0,
            'status' => 'uploaded',
        ]);

        try {
            $parser = app(CsvParser::class);
            $parser->parse($upload);
        } catch (\Exception $e) {
            return redirect()->route('uploads.index')
                ->with('error', 'Sample generated but parsing failed: ' . $e->getMessage());
        }

        return redirect()->route('uploads.show', $upload)
            ->with('success', '✨ Sample hospital data generated!');
    }
}