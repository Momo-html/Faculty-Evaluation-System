<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('course_creations', function (Blueprint $table) {
            $table->id();
            $table->string('course_id')->unique();
            $table->string('short_name');
            $table->string('long_name');
            $table->string('status')->default('active')->index();
            $table->string('term_id')->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('course_creations');
    }
};
