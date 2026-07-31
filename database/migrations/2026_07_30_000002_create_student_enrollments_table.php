<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('student_enrollments', function (Blueprint $table) {
            $table->id();
            $table->string('course_id')->index();
            $table->string('section_id')->nullable()->index();
            $table->string('user_id')->index();
            $table->foreignId('department_id')->nullable()->constrained()->nullOnDelete();
            $table->string('status')->default('active')->index();
            $table->string('role')->default('student')->index();
            $table->timestamps();
            $table->unique(['course_id', 'section_id', 'user_id', 'role'], 'student_enrollment_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_enrollments');
    }
};
