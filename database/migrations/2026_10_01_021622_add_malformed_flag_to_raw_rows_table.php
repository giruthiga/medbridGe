<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('raw_rows', function (Blueprint $table) {
            $table->boolean('is_malformed')->default(false)->after('data');
            $table->integer('field_count')->default(0)->after('is_malformed');
            $table->text('malformed_reason')->nullable()->after('field_count');
        });
    }

    public function down(): void
    {
        Schema::table('raw_rows', function (Blueprint $table) {
            $table->dropColumn(['is_malformed', 'field_count', 'malformed_reason']);
        });
    }
};