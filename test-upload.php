<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Upload;
use App\Models\User;
use App\Services\CsvParser;
use Illuminate\Support\Facades\Storage;

$user = User::first();
echo "User: {$user->name} (ID: {$user->id})\n";

$csvContent = "name,dob,phone,gender,address\nAhmed Ali,1985-04-12,39123456,Male,Manama\nFatima Hassan,1990-08-23,36123457,Female,Muharraq\nJohn Smith,1978-01-05,35123458,Male,Riffa\n";
Storage::disk('public')->put('uploads/test-manual.csv', $csvContent);

echo "File stored at: uploads/test-manual.csv\n";

$upload = Upload::create([
    'user_id' => $user->id,
    'original_filename' => 'test-manual.csv',
    'stored_path' => 'uploads/test-manual.csv',
    'row_count' => 0,
    'status' => 'uploaded',
]);

echo "Upload record created: ID {$upload->id}\n";

try {
    $parser = new CsvParser();
    $count = $parser->parse($upload);
    echo "Parsed {$count} rows.\n";
} catch (\Exception $e) {
    echo "PARSER ERROR: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
}

echo "\n--- Verification ---\n";
echo "Uploads in DB: " . Upload::count() . "\n";
echo "RawRows in DB: " . \App\Models\RawRow::count() . "\n";