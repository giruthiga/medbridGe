<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('matches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('upload_id')->constrained()->cascadeOnDelete();
            $table->string('your_field');
            $table->string('med_field');
            $table->timestamps();

            $table->unique(['upload_id', 'your_field']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('matches');
    }
};